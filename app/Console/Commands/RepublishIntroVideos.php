<?php

namespace App\Console\Commands;

use App\Enums\CompanyReviewStatus;
use App\Models\Company;
use App\Services\CompanyPublicationService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * One-off backfill: intro_video was only ever registered on the Company
 * draft, so CompanyPublicationService::copyMedia() — which iterates the
 * PUBLICATION's registered collections — never carried it into existing
 * snapshots. Now that CompanyPublication registers the collection, future
 * approvals copy it automatically; this command re-runs the same publish
 * flow for approved companies whose snapshot predates that fix.
 *
 * Deliberately NOT scheduled: it is a migration of existing data, not an
 * ongoing job.
 */
class RepublishIntroVideos extends Command
{
    protected $signature = 'app:republish-intro-videos {--dry-run}';

    protected $description = 'Backfill intro videos into published company snapshots that are missing them';

    /**
     * Kept small: each publish copies a video (up to 256 MB) on S3 inside a
     * transaction, so a wide chunk would hold many objects in flight.
     */
    private const CHUNK_SIZE = 50;

    public function handle(CompanyPublicationService $publicationService): int
    {
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        // chunkById, not chunk(): publishing removes a company from this
        // result set, and an offset-based cursor would then skip the row that
        // shifted into the consumed page. Ordering by id makes the cursor
        // immune to rows dropping out mid-run.
        $this->candidates()->chunkById(
            self::CHUNK_SIZE,
            function (iterable $companies) use ($publicationService, &$processed, &$skipped, &$failed): void {
                foreach ($companies as $company) {
                    try {
                        // Re-check against live state rather than trusting the
                        // chunk's snapshot: an owner can remove the video, or
                        // an admin can approve the company, between the query
                        // and this iteration.
                        if ($company->getFirstMedia('intro_video') === null) {
                            $skipped++;

                            continue;
                        }

                        if ($this->option('dry-run')) {
                            $this->line("Would republish company #{$company->id} ({$company->slug}).");
                            $processed++;

                            continue;
                        }

                        $publicationService->publish($company);
                        $processed++;
                    } catch (Throwable $exception) {
                        // One company's S3 copy failing must not abort the
                        // whole backfill.
                        report($exception);
                        $this->error("Company #{$company->id} failed: {$exception->getMessage()}");
                        $failed++;
                    }
                }
            },
        );

        $this->info("Intro video backfill: {$processed} processed, {$skipped} skipped, {$failed} failed."
            .($this->option('dry-run') ? ' (dry run — nothing written.)' : ''));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Approved companies that hold an intro video their existing publication
     * does not. Both media checks go through the polymorphic media relation
     * medialibrary's InteractsWithMedia provides on each model.
     */
    private function candidates(): Builder
    {
        return Company::query()
            ->where('review_status', CompanyReviewStatus::Approved)
            ->whereHas('media', fn (Builder $query) => $query->where('collection_name', 'intro_video'))
            ->whereHas('publication', fn (Builder $query) => $query->whereDoesntHave(
                'media',
                fn (Builder $media) => $media->where('collection_name', 'intro_video'),
            ));
    }
}
