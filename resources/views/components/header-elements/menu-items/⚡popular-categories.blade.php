<?php

use App\Models\CompanyCategory;
use App\Models\CompanyPublication;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    /**
     * Ids of the ~10 most-viewed categories, cached with the same
     * rememberForever + versioned-key convention as the homepage/globe
     * sections (home.filters.*, maps.world-globe.*). v1: ranked id list.
     * Bump the suffix to invalidate stale payloads.
     *
     * Ranked by cyrildewit/eloquent-viewable totals — the same views the
     * category page records through RecordsPageView (60-minute visitor
     * cooldown), so only categories people actually visit can rank. View
     * counts are locale-independent, so the key carries no locale; only the
     * rendered titles are translated per request below.
     *
     * @return array<int, int|string>
     */
    #[Computed(persist: true)]
    public function popularCategoryIds(): array
    {
        return Cache::rememberForever('header.popular-categories.v1', function (): array {
            // Only categories with at least one active published company can
            // appear: the pivot join yields the ids, the ordering comes from
            // recorded views.
            $categorizedIds = CompanyPublication::query()
                ->active()
                ->join(
                    'company_category_company_publication',
                    'company_publications.id',
                    '=',
                    'company_category_company_publication.company_publication_id'
                )
                ->select('company_category_id')
                ->distinct()
                ->pluck('company_category_id');

            return CompanyCategory::query()
                ->where('is_active', true)
                ->whereIn('id', $categorizedIds)
                ->orderByViews()
                ->limit(10)
                ->pluck('id')
                ->all();
        });
    }

    /**
     * Freshly hydrated (NOT persisted — media URLs and translations must not
     * outlive a logo replacement) rows for the cached id ranking, preserving
     * the ranked order.
     */
    #[Computed]
    public function popularCategories(): Collection
    {
        $ids = $this->popularCategoryIds;

        if ($ids === []) {
            return Collection::empty();
        }

        $ranked = CompanyCategory::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $ordered = new Collection;

        foreach ($ids as $id) {
            if ($category = $ranked->get($id)) {
                $ordered->push($category);
            }
        }

        return $ordered;
    }
};
?>
<div class="menu-more bg-light col-lg-4 py-3 px-3 py-lg-6 px-lg-6 rounded-end">
    <!--begin:Heading-->
    <h4 class="fs-6 fs-lg-4 text-gray-800 fw-bold mt-3 mb-3 ms-4">{{ __('menu.popular_categories') }}</h4>
    <!--end:Heading-->

    @forelse ($this->popularCategories as $category)
        <!--begin:Menu item-->
        <div class="menu-item p-0 m-0">
            <!--begin:Menu link-->
            <a href="{{ route('companies.category', ['slug' => $category->slug]) }}" class="menu-link py-2">
                <span class="menu-custom-icon d-flex flex-center flex-shrink-0 rounded w-40px h-40px me-3">
                    @if ($category->hasMedia('logo'))
                        <img src="{{ $category->getFirstMediaUrl('logo', 'webp') }}" alt="{{ $category->title }}" class="w-100 h-100 rounded object-fit-cover" />
                    @else
                        <i class="ki-duotone ki-element-11 text-primary fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                            <span class="path4"></span>
                        </i>
                    @endif
                </span>
                <span class="menu-title">{{ $category->title }}</span>
            </a>
            <!--end:Menu link-->
        </div>
        <!--end:Menu item-->
    @empty
        {{-- No viewed categories yet: the heading alone renders, no dead
             placeholder links. --}}
    @endforelse
</div>
