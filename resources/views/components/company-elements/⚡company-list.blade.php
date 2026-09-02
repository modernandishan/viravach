<?php

use App\Models\CompanyCategory;
use App\Models\CompanyPublication;
use App\Models\State;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Reusable published-company listing with filters, sorting and crawlable
 * pagination. Shared by the country, state and category pages.
 *
 * Scoping is deliberately nullable ids rather than a scope object or a
 * closure: the host page passes what it already has, and the component stays
 * free of any route or page knowledge. Hosts set one; none means "every
 * published company". A filter whose dimension is already pinned by the
 * scope hides itself rather than offering a redundant choice.
 */
new class extends Component
{
    use WithPagination;

    /**
     * Four rows of three on desktop (the grid is col-lg-4), two of six on
     * tablet. Matches the page size the old ListsCompanies concern used, so
     * the migration did not change how deep the paginated set runs.
     */
    private const PER_PAGE = 12;

    private const SORT_NEWEST = 'newest';

    private const SORT_MOST_VIEWED = 'most_viewed';

    /** @var array<int, string> */
    private const SORTS = [self::SORT_NEWEST, self::SORT_MOST_VIEWED];

    public ?int $countryId = null;

    public ?int $stateId = null;

    public ?int $categoryId = null;

    /**
     * Filter/sort state lives in the query string so a filtered view is
     * shareable and bookmarkable. Keys are stable English strings and never
     * locale-dependent; `except` keeps default values out of the URL.
     * Values are slugs (also English by project rule), not ids, so the URL
     * stays readable and survives a reseed.
     */
    #[Url(as: 'category', except: '')]
    public string $category = '';

    #[Url(as: 'state', except: '')]
    public string $state = '';

    #[Url(as: 'verified', except: false)]
    public bool $verified = false;

    #[Url(as: 'sort', except: self::SORT_NEWEST)]
    public string $sort = self::SORT_NEWEST;

    /**
     * Per-request memo for the category subtree. baseQuery() runs three
     * times per render (listing + two option lists) and the descendant
     * lookup is a recursive CTE; it must not run three times. Private, so
     * Livewire never tries to serialise it — it is rebuilt each request.
     *
     * @var array<int, int>|null
     */
    private ?array $resolvedCategoryIds = null;

    /**
     * Any filter or sort change puts the visitor back on page 1 — page 7 of
     * the unfiltered set is meaningless once the set changes.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['category', 'state', 'verified', 'sort'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('category', 'state', 'verified');
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->category !== '' || $this->state !== '' || $this->verified;
    }

    /**
     * The scoped category and every descendant. Mirrors exactly what
     * CompanyCategory::publishedCompaniesCount() counts, so the number in
     * the page's SEO fallback description and the number of results listed
     * here can never disagree.
     *
     * @return array<int, int>
     */
    private function scopedCategoryIds(): array
    {
        if ($this->resolvedCategoryIds !== null) {
            return $this->resolvedCategoryIds;
        }

        $category = CompanyCategory::find($this->categoryId);

        // A missing category yields an empty id list, so the listing comes
        // back empty rather than silently widening to every company.
        return $this->resolvedCategoryIds = $category === null
            ? []
            : $category->descendantsAndSelf()->pluck('id')->all();
    }

    /**
     * Everything in scope before the visitor's own filters are applied.
     * Returns a fresh builder each call — the option-count queries and the
     * listing query must not share (and mutate) one instance.
     */
    private function baseQuery(): Builder
    {
        $query = CompanyPublication::query()->active();

        if ($this->stateId !== null) {
            $query->where('state_id', $this->stateId);
        } elseif ($this->countryId !== null) {
            // An exists subquery rather than plucking state ids first: one
            // round trip, and it stays correct as states are added.
            $query->whereHas('state', fn (Builder $state) => $state->where('country_id', $this->countryId));
        }

        if ($this->categoryId !== null) {
            $query->whereHas(
                'categories',
                fn (Builder $categories) => $categories->whereIn('company_categories.id', $this->scopedCategoryIds()),
            );
        }

        return $query;
    }

    /**
     * The listing itself. Deliberately not cached: it varies by scope,
     * filters, sort and page, and it must reflect a publication the moment
     * it goes live.
     */
    #[Computed]
    public function companies(): LengthAwarePaginator
    {
        $query = $this->baseQuery()->with(['media', 'categories', 'state']);

        // Only meaningful when the scope has not already pinned a category.
        if ($this->category !== '' && $this->categoryId === null) {
            $query->whereHas(
                'categories',
                fn (Builder $categories) => $categories->where('company_categories.slug', $this->category),
            );
        }

        // Likewise: on a state page the scope already pins the state.
        if ($this->state !== '' && $this->stateId === null) {
            $query->whereHas('state', fn (Builder $state) => $state->where('states.slug', $this->state));
        }

        if ($this->verified) {
            $query->where('is_verified', true);
        }

        // $this->sort arrives from the query string, so it is matched against
        // the whitelist rather than trusted.
        match ($this->activeSort()) {
            self::SORT_MOST_VIEWED => $query->orderByViews('desc')->orderByDesc('published_at'),
            default => $query->orderByDesc('published_at'),
        };

        return $query
            ->paginate(self::PER_PAGE)
            // Built from component state, not request()->query(): during a
            // Livewire XHR the request carries the update endpoint's query
            // string, not the page's, which would drop filters from the
            // re-rendered page links.
            ->appends($this->appendedQuery());
    }

    public function activeSort(): string
    {
        return in_array($this->sort, self::SORTS, true) ? $this->sort : self::SORT_NEWEST;
    }

    /**
     * @return array<string, string|int>
     */
    private function appendedQuery(): array
    {
        return array_filter([
            'category' => $this->category !== '' ? $this->category : null,
            'state' => $this->state !== '' ? $this->state : null,
            'verified' => $this->verified ? 1 : null,
            'sort' => $this->activeSort() !== self::SORT_NEWEST ? $this->activeSort() : null,
        ], fn ($value): bool => $value !== null);
    }

    /**
     * Categories that actually have published companies inside this scope —
     * never the full category tree, so the dropdown can't offer a filter that
     * returns nothing. Same join shape home's ⚡advance-search uses.
     *
     * Empty under a category scope: the category page already is the
     * category, and a second category picker there would just be confusing.
     *
     * Not cached: it is one grouped count over an already-narrow scope, and
     * caching it per scope+locale would add more forever-keys to a codebase
     * that has no busting strategy, so a newly published company would be
     * missing from the filter until a manual flush.
     *
     * @return array<int, array{slug: string, label: string, count: int}>
     */
    #[Computed]
    public function categoryOptions(): array
    {
        if ($this->categoryId !== null) {
            return [];
        }

        $locale = app()->getLocale();

        $counts = $this->baseQuery()
            ->join(
                'company_category_company_publication',
                'company_publications.id',
                '=',
                'company_category_company_publication.company_publication_id'
            )
            ->selectRaw('company_category_id, COUNT(*) AS aggregate')
            ->groupBy('company_category_id')
            ->pluck('aggregate', 'company_category_id');

        if ($counts->isEmpty()) {
            return [];
        }

        return CompanyCategory::query()
            ->whereIn('id', $counts->keys())
            ->where('is_active', true)
            ->get()
            ->map(fn (CompanyCategory $category): array => [
                'slug' => $category->slug,
                'label' => $category->getTranslation('title', $locale),
                'count' => (int) ($counts[$category->id] ?? 0),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * Provinces that have published companies inside this scope: those in
     * the country on a country page, those carrying this category on a
     * category page. Empty on a state page — the scope already pins it — so
     * the select simply does not render.
     *
     * @return array<int, array{slug: string, label: string, count: int}>
     */
    #[Computed]
    public function stateOptions(): array
    {
        if ($this->stateId !== null) {
            return [];
        }

        $locale = app()->getLocale();

        $counts = $this->baseQuery()
            ->whereNotNull('state_id')
            ->selectRaw('state_id, COUNT(*) AS aggregate')
            ->groupBy('state_id')
            ->pluck('aggregate', 'state_id');

        if ($counts->isEmpty()) {
            return [];
        }

        return State::query()
            ->whereIn('id', $counts->keys())
            ->where('is_active', true)
            ->get()
            ->map(fn (State $state): array => [
                'slug' => $state->slug,
                'label' => $state->getTranslation('name', $locale),
                'count' => (int) ($counts[$state->id] ?? 0),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function sortOptions(): array
    {
        return [
            self::SORT_NEWEST => __('companies.listing.sort_newest'),
            self::SORT_MOST_VIEWED => __('companies.listing.sort_most_viewed'),
        ];
    }
};
?>

@php
    $companies = $this->companies;
    $categoryOptions = $this->categoryOptions;
    $stateOptions = $this->stateOptions;
@endphp

<div class="vv-list">
    {{-- Toolbar. Filters on the inline-start, sort on the inline-end; the
         Bootstrap row handles RTL mirroring on its own. --}}
    <div class="card mb-6">
        <div class="card-body py-5">
            <div class="row g-4 align-items-end">
                @if ($categoryOptions !== [])
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="vv-list-category-{{ $this->getId() }}" class="vv-list-toolbar-label">
                            {{ __('companies.listing.filter_category') }}
                        </label>
                        <select
                            id="vv-list-category-{{ $this->getId() }}"
                            class="form-select form-select-solid"
                            wire:model.live="category"
                        >
                            <option value="">{{ __('companies.listing.filter_all') }}</option>
                            @foreach ($categoryOptions as $option)
                                <option value="{{ $option['slug'] }}">
                                    {{ $option['label'] }} ({{ number_format($option['count']) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if ($stateOptions !== [])
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="vv-list-state-{{ $this->getId() }}" class="vv-list-toolbar-label">
                            {{ __('companies.listing.filter_state') }}
                        </label>
                        <select
                            id="vv-list-state-{{ $this->getId() }}"
                            class="form-select form-select-solid"
                            wire:model.live="state"
                        >
                            <option value="">{{ __('companies.listing.filter_all') }}</option>
                            @foreach ($stateOptions as $option)
                                <option value="{{ $option['slug'] }}">
                                    {{ $option['label'] }} ({{ number_format($option['count']) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-12 col-md-4 col-lg-3">
                    <span class="vv-list-toolbar-label">{{ __('companies.listing.filter_verified') }}</span>
                    <div class="form-check form-switch form-check-custom form-check-solid">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="vv-list-verified-{{ $this->getId() }}"
                            wire:model.live="verified"
                        >
                        <label class="form-check-label" for="vv-list-verified-{{ $this->getId() }}">
                            {{ __('companies.listing.filter_verified_hint') }}
                        </label>
                    </div>
                </div>

                <div class="col-12 col-md-4 col-lg-3">
                    <label for="vv-list-sort-{{ $this->getId() }}" class="vv-list-toolbar-label">
                        {{ __('companies.listing.sort_label') }}
                    </label>
                    <select
                        id="vv-list-sort-{{ $this->getId() }}"
                        class="form-select form-select-solid"
                        wire:model.live="sort"
                    >
                        @foreach ($this->sortOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-3 pt-4">
                <span class="vv-list-count">
                    {{ __('companies.listing.results_count', ['count' => number_format($companies->total())]) }}
                </span>

                @if ($this->hasActiveFilters())
                    <button type="button" class="btn btn-sm btn-light" wire:click="clearFilters">
                        <i class="ki-duotone ki-cross-circle fs-6"><span class="path1"></span><span class="path2"></span></i>
                        {{ __('companies.listing.clear_filters') }}
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- The results keep their place while a filter change is in flight.
         Swapping the grid out for a fixed number of skeleton cards collapsed
         the container's height, which pulled the footer up and then pushed it
         back down when the results landed — a visible jump on every filter
         change. Dimming the real grid in place is the loading affordance and
         guarantees zero layout shift. Only opacity animates (DESIGN.md §9),
         and pointer events are suppressed so a half-faded card cannot be
         clicked mid-update. --}}
    <div
        class="vv-list-results"
        wire:loading.class="vv-list-results-loading"
        wire:loading.attr="aria-busy"
        aria-live="polite"
    >
        <div class="row g-4">
            @forelse ($companies as $company)
                <div class="col-12 col-sm-6 col-lg-4" wire:key="company-{{ $company->id }}">
                    <livewire:company-elements.company-card :company="$company" :wire:key="'card-'.$company->id" />
                </div>
            @empty
                <div class="col-12">
                    <div class="vv-list-empty">
                        <i class="ki-duotone ki-search-list vv-list-empty-icon">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                        <p class="vv-list-empty-text">
                            {{ $this->hasActiveFilters()
                                ? __('companies.listing.empty_filtered')
                                : __('companies.listing.empty') }}
                        </p>
                        @if ($this->hasActiveFilters())
                            <button type="button" class="btn btn-sm btn-primary" wire:click="clearFilters">
                                {{ __('companies.listing.clear_filters') }}
                            </button>
                        @endif
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Laravel's own Bootstrap 5 view, not livewire::bootstrap: it emits
             real <a href="?page=N"> links, so the pages are crawlable and work
             with JavaScript off. livewire::bootstrap renders href="#" plus
             wire:click, which neither crawlers nor a no-JS visitor can follow. --}}
        @if ($companies->hasPages())
            <div class="d-flex flex-stack flex-wrap pt-10">
                {{ $companies->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
