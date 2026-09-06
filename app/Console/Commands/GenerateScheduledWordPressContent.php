<?php

namespace App\Console\Commands;

use App\Enums\WordPressPostStatus;
use App\Models\Company;
use App\Services\WordPress\WordPressContentGenerationService;
use App\Settings\ContentSettings;
use App\Support\WordPressContentQuota;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class GenerateScheduledWordPressContent extends Command
{
    protected $signature = 'app:generate-scheduled-wordpress-content';

    protected $description = 'Queue one automatic WordPress article for every eligible company on its 3-day cadence';

    /**
     * How long to wait after the company's most recent generation attempt —
     * successful or failed — before the scheduler tries again. Failures are
     * quota-free (WordPressContentQuota excludes Failed rows), but they are
     * mostly environmental (trends unreachable, no topic, publish error), so
     * retrying them faster than this would hammer a broken dependency daily.
     */
    public const CADENCE_DAYS = 3;

    public function handle(WordPressContentGenerationService $service): int
    {
        if (! app(ContentSettings::class)->enabled) {
            $this->info('WordPress content generation is globally disabled; nothing scheduled.');

            return self::SUCCESS;
        }

        $eligibleBefore = now()->subDays(self::CADENCE_DAYS);

        $companies = Company::query()
            ->where('wp_connection_status', 'connected')
            ->whereNotNull('seo_plugin')
            ->whereNotNull('content_generation_mode')
            ->whereDoesntHave('wordPressContentPosts', fn (Builder $query) => $query->whereIn('status', [
                WordPressPostStatus::Queued,
                WordPressPostStatus::Generating,
            ]))
            // Cadence gate: the company's latest attempt of ANY status must be
            // older than the cadence. A company with no rows at all qualifies.
            ->whereDoesntHave('wordPressContentPosts', fn (Builder $query) => $query->where('created_at', '>=', $eligibleBefore))
            ->get();

        $queued = 0;
        $skipped = 0;
        $errored = 0;

        foreach ($companies as $company) {
            try {
                // Re-check the quota at dispatch time: the query above cannot
                // express the live plan-feature lookup, and the service's own
                // gate would only turn the attempt into a wasted Result.
                if (! WordPressContentQuota::for($company)->canGenerate()) {
                    $skipped++;

                    continue;
                }

                $result = $service->request(
                    $company,
                    $company->content_language,
                    $company->content_generation_mode,
                );

                $result->successful ? $queued++ : $skipped++;
            } catch (Throwable $exception) {
                // One company's fault (e.g. the trends HTTP call) must never
                // block the rest of the schedule.
                report($exception);
                $errored++;
            }
        }

        $this->info("Scheduled WordPress generation: {$queued} queued, {$skipped} skipped, {$errored} errored.");

        return self::SUCCESS;
    }
}
