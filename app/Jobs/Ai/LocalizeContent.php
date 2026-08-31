<?php

namespace App\Jobs\Ai;

use App\Events\Ai\ContentGenerationProgressed;
use App\Models\CompanyContent;
use App\Services\Ai\ContentGenerationService;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

/**
 * Step 4: fan the non-source locales out to LocalizeContentLocale, running
 * them concurrently in one Bus::batch instead of one after another — a
 * serial run of four locales measured 14m46s for a single company.
 *
 * This job's own handle() only builds the batch and dispatches it; it
 * returns as soon as the batch is queued, well before any locale finishes.
 * FinalizeContent is therefore NOT part of the outer Bus::chain (see
 * ContentGenerationService) — it is dispatched from the batch's finally()
 * callback instead, once every locale job has run. A single locale failing
 * must not abort the others: LocalizeContentLocale catches its own
 * failures internally (so the batch always finishes) and records its
 * reason onto failure_reason via AbstractAiContentJob::recordLocaleFailure.
 * ->allowFailures() additionally keeps the batch from cancelling itself if
 * something outside that internal handling throws. Only when every target
 * locale failed does this step fail the whole run.
 */
class LocalizeContent extends AbstractAiContentJob
{
    public const STEP = 4;

    public $timeout = 120;

    public function handle(): void
    {
        $content = $this->contentRow();

        if ($content->step >= static::STEP) {
            return;
        }

        $this->advanceStep($content);

        $companyId = $this->companyId;
        $targets = static::targetLocales();

        if ($targets === []) {
            FinalizeContent::dispatch($companyId)->onQueue('ai-content');

            return;
        }

        $jobs = array_map(
            fn (string $locale): LocalizeContentLocale => new LocalizeContentLocale($companyId, $locale),
            $targets,
        );

        Bus::batch($jobs)
            ->allowFailures()
            ->onQueue('ai-content')
            ->finally(static function (Batch $batch) use ($companyId, $targets): void {
                $content = CompanyContent::query()->where('company_id', $companyId)->firstOrFail();

                $succeeded = array_intersect($targets, array_keys((array) ($content->ai_payload ?? [])));

                // Every locale failed: nothing to finalize, escalate like
                // the old single-job loop did when count(failures) matched
                // count(targets).
                if ($succeeded === []) {
                    app(ContentGenerationService::class)->markFailed(
                        $content,
                        str_replace(
                            'Partial localization — failed locale(s): ',
                            'All locale localizations failed: ',
                            (string) $content->failure_reason,
                        ),
                    );

                    return;
                }

                // Fires once here for the batch's completion; advanceStep()
                // already fired once when the batch was dispatched above.
                ContentGenerationProgressed::dispatch($content);

                FinalizeContent::dispatch($companyId)->onQueue('ai-content');
            })
            ->dispatch();
    }
}
