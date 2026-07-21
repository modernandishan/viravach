<?php

namespace App\Livewire\Concerns;

use App\Models\CompanyPublication;
use CyrildeWit\EloquentViewable\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Public visits are recorded against CompanyPublication (the approved
 * snapshot), so all aggregates here read the underlying `views` table via
 * its polymorphic viewable_type/viewable_id columns filtered to
 * publications.
 */
trait AggregatesCompanyViews
{
    /**
     * @param  iterable<int, int>  $publicationIds
     */
    protected function totalViewsForPublications(iterable $publicationIds): int
    {
        return $this->publicationViewsQuery($publicationIds)->count();
    }

    /**
     * @param  iterable<int, int>  $publicationIds
     */
    protected function viewsSinceForPublications(iterable $publicationIds, Carbon $since): int
    {
        return $this->publicationViewsQuery($publicationIds)
            ->where('viewed_at', '>=', $since)
            ->count();
    }

    /**
     * @param  iterable<int, int>  $publicationIds
     * @return Collection<int, int> view counts keyed by publication id
     */
    protected function viewCountsPerPublication(iterable $publicationIds, ?Carbon $since = null): Collection
    {
        return $this->publicationViewsQuery($publicationIds)
            ->when($since, fn ($query) => $query->where('viewed_at', '>=', $since))
            ->selectRaw('viewable_id, count(*) as views_count')
            ->groupBy('viewable_id')
            ->pluck('views_count', 'viewable_id');
    }

    /**
     * Zero-filled daily view counts from $since through today.
     *
     * @param  iterable<int, int>  $publicationIds
     * @return Collection<string, int> keyed by Y-m-d
     */
    protected function dailyViewCountsForPublications(iterable $publicationIds, Carbon $since): Collection
    {
        $countsByDay = $this->publicationViewsQuery($publicationIds)
            ->where('viewed_at', '>=', $since)
            ->selectRaw('DATE(viewed_at) as day, count(*) as views_count')
            ->groupBy('day')
            ->pluck('views_count', 'day');

        $days = collect();

        for ($date = $since->copy()->startOfDay(); $date->lte(now()); $date->addDay()) {
            $key = $date->format('Y-m-d');
            $days->put($key, (int) ($countsByDay[$key] ?? 0));
        }

        return $days;
    }

    /**
     * @param  iterable<int, int>  $companyIds
     * @return Collection<int, int> publication id keyed by source company id
     */
    protected function publicationIdsByCompany(iterable $companyIds): Collection
    {
        return CompanyPublication::query()
            ->whereIn('company_id', $companyIds)
            ->pluck('id', 'company_id');
    }

    /**
     * @param  iterable<int, int>  $publicationIds
     */
    protected function publicationViewsQuery(iterable $publicationIds): Builder
    {
        return View::query()
            ->where('viewable_type', CompanyPublication::class)
            ->whereIn('viewable_id', $publicationIds);
    }
}
