<?php

namespace App\Services\Ai;

use App\Ai\Input\CompanyInputCollector;
use App\Enums\CompanyContentStatus;
use App\Events\Ai\ContentGenerationProgressed;
use App\Jobs\Ai\GenerateSeoBlock;
use App\Jobs\Ai\GenerateSourceContent;
use App\Jobs\Ai\LocalizeContent;
use App\Jobs\Ai\ReserveKeyword;
use App\Models\Company;
use App\Models\CompanyContent;
use App\Settings\ContentSettings;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

/**
 * ORCHESTRATION NOTE — DEDICATED WORKER REQUIRED:
 * every job in this pipeline is dispatched on the 'ai-content' queue, and a
 * dedicated worker for that queue MUST be run separately (e.g.
 * `php artisan queue:work --queue=ai-content,default` or a second worker
 * container), so that long-running content generation can never starve the
 * default queue that ViraBot chat replies use. Do not fold this queue into
 * the single default worker.
 */
class ContentGenerationService
{
    public function __construct(
        protected CompanyInputCollector $collector,
    ) {}

    /**
     * Single entry point for requesting (re)generation. Returns true only
     * when this call actually claimed the row and dispatched the chain.
     */
    public function request(Company $company): bool
    {
        if (! app(ContentSettings::class)->enabled) {
            return false;
        }

        $this->loadOrCreateRow($company);

        $content = $this->rowFor($company);

        // A row that is queued or generating belongs to a live run; a
        // second request must not disturb it.
        if ($content->status->isProcessing()) {
            return false;
        }

        $hash = $this->collector->hash($this->collector->collect($company));

        // Ready with an unchanged hash means the stored content was generated
        // from exactly this input — nothing to regenerate.
        if ($content->status === CompanyContentStatus::Ready && $content->input_hash === $hash) {
            return false;
        }

        // ATOMIC CLAIM: this is a single conditional UPDATE, never a
        // read-then-write. Only the one request whose UPDATE matches a
        // claimable row (draft/failed/ready) flips it to queued — a
        // double-click or two tabs hitting request() concurrently cannot
        // both win, because the second UPDATE sees the status already
        // 'queued' and affects zero rows.
        $claimed = CompanyContent::where('company_id', $company->id)
            ->whereIn('status', ['draft', 'failed', 'ready'])
            ->update([
                'status' => CompanyContentStatus::Queued->value,
                'locked_at' => now(),
                'input_hash' => $hash,
                'step' => 0,
                'failure_reason' => null,
            ]);

        if ($claimed !== 1) {
            return false;
        }

        // No transaction wraps the claim (it is a single conditional
        // UPDATE), so the broadcast goes out immediately.
        $this->broadcastProgress($this->rowFor($company));

        // FinalizeContent is deliberately NOT chained here: LocalizeContent
        // (step 4) fans its work out to a Bus::batch of LocalizeContentLocale
        // jobs and dispatches FinalizeContent itself once that batch
        // completes — see LocalizeContent for why it cannot be a plain
        // chain member any more.
        Bus::chain([
            new GenerateSourceContent($company->id),
            new GenerateSeoBlock($company->id),
            new ReserveKeyword($company->id),
            new LocalizeContent($company->id),
        ])
            ->onQueue('ai-content')
            ->dispatch();

        return true;
    }

    public function markFailed(CompanyContent $content, string $reason): void
    {
        $content->forceFill([
            'status' => CompanyContentStatus::Failed,
            'failure_reason' => $reason,
            'locked_at' => null,
        ])->save();

        Log::warning('AI content generation failed.', [
            'company_id' => $content->company_id,
            'reason' => $reason,
        ]);

        $this->broadcastProgress($content);
    }

    public function markReady(CompanyContent $content): void
    {
        $content->forceFill([
            'status' => CompanyContentStatus::Ready,
            'locked_at' => null,
        ])->save();

        $this->broadcastProgress($content);
    }

    /**
     * Push one progress event to the company's private ai-content channel.
     */
    protected function broadcastProgress(CompanyContent $content): void
    {
        ContentGenerationProgressed::dispatch($content);
    }

    protected function loadOrCreateRow(Company $company): CompanyContent
    {
        return CompanyContent::firstOrCreate(['company_id' => $company->id]);
    }

    protected function rowFor(Company $company): CompanyContent
    {
        return CompanyContent::query()->where('company_id', $company->id)->firstOrFail();
    }
}
