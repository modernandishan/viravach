<?php

namespace App\Console\Commands;

use App\Enums\CompanyReviewStatus;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only health check for companies marked Approved that have no
 * CompanyPublication behind them.
 *
 * Such a record is invisible in the admin panel: approveAction() hides itself
 * once the status leaves PendingReview, and republishAction() hides itself
 * while no publication exists, so neither recovery button renders and the
 * company can never go live. It arose two ways, both now closed —
 * approveAction() stamping the status before publish() could throw, and
 * CompanyForm's review_status Select offering Approved directly.
 *
 * A command rather than a migration on purpose: this is a recurring check, not
 * a one-shot data change, and it must stay safe to run at any time. It never
 * writes — repairing a listed company is a judgement call (republish it, or
 * put it back in the queue) and stays manual.
 */
class FindStrandedCompanies extends Command
{
    protected $signature = 'app:find-stranded-companies';

    protected $description = 'List approved companies that have no published snapshot (read-only)';

    public function handle(): int
    {
        $stranded = $this->stranded()->get();

        if ($stranded->isEmpty()) {
            $this->info('No stranded companies: every approved company has a publication.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Slug', 'User', 'Reviewed at', 'Reached approveAction()'],
            $stranded->map(fn (Company $company): array => [
                $company->id,
                $company->slug,
                $company->user_id,
                $company->reviewed_at?->toDateTimeString() ?? '—',
                // Only approveAction() writes reviewed_at alongside Approved;
                // CompanyForm could not (its picker is dehydrated(false)), so a
                // null here means the status was set straight from the form.
                $company->reviewed_at !== null ? 'yes' : 'no (set via the edit form)',
            ])->all(),
        );

        $this->warn($stranded->count().' company(ies) are approved with no live publication.');

        // Non-zero so a scheduler or CI step treats a hit as actionable.
        return self::FAILURE;
    }

    /** Includes soft-deleted drafts: a trashed company can still hold a stale approved status. */
    private function stranded(): Builder
    {
        return Company::query()
            ->withTrashed()
            ->where('review_status', CompanyReviewStatus::Approved)
            ->whereDoesntHave('publication')
            ->orderBy('id');
    }
}
