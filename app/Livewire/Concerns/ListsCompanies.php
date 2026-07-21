<?php

namespace App\Livewire\Concerns;

use App\Models\CompanyPublication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

trait ListsCompanies
{
    use AggregatesCompanyViews;
    use RecordsPageView;
    use WithPagination;

    /**
     * Narrow the base publication query to whatever the consuming component
     * is listing companies for (e.g. a category subtree or a state).
     */
    abstract protected function filterCompanies(Builder $query): Builder;

    #[Computed]
    public function companies(): LengthAwarePaginator
    {
        return $this->filterCompanies(
            CompanyPublication::query()->active()->with(['media', 'categories', 'state'])
        )->paginate(12);
    }

    /**
     * @return Collection<int, int> view counts keyed by publication id, for the current page only
     */
    #[Computed]
    public function companyViewCounts(): Collection
    {
        return $this->viewCountsPerPublication($this->companies->pluck('id'));
    }
}
