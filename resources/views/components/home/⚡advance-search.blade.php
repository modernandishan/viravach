<?php

use App\Models\CompanyCategory;
use App\Models\CompanyPublication;
use App\Models\State;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    // Primary keyword: company name / product / service.
    public string $keyword = '';

    public ?int $categoryId = null;

    public ?int $stateId = null;

    public bool $verifiedOnly = false;

    /** @var array<string, mixed> assembled query parameters from the last search() call */
    public array $appliedFilters = [];

    /**
     * Active category tree flattened into a single option list: roots first,
     * children indented underneath via `depth`.
     *
     * NOTE: the cache key must be flushed whenever active categories change.
     *
     * @return array<int, array{id: int, slug: string, label: string, depth: int, count: int}>
     */
    #[Computed(persist: true)]
    public function categoryOptions(): array
    {
        $locale = app()->getLocale();

        // Cache key must be flushed when active categories change. The v2
        // suffix invalidates payloads cached before empty branches were
        // filtered out.
        return Cache::rememberForever("home.filters.categories.v2.{$locale}", function () use ($locale): array {
            $counts = CompanyPublication::query()
                ->active()
                ->join(
                    'company_category_company_publication',
                    'company_publications.id',
                    '=',
                    'company_category_company_publication.company_publication_id'
                )
                ->selectRaw('company_category_id, COUNT(*) AS aggregate')
                ->groupBy('company_category_id')
                ->pluck('aggregate', 'company_category_id');

            // Post-order walk: a branch is kept only when at least one
            // publication is attached to it directly or via a descendant.
            $flatten = function (EloquentCollection $nodes, int $depth) use (&$flatten, $counts, $locale): array {
                $options = [];

                foreach ($nodes as $node) {
                    $childOptions = $flatten($node->children, $depth + 1);
                    $count = (int) ($counts[$node->id] ?? 0);

                    if ($count === 0 && $childOptions === []) {
                        continue;
                    }

                    $options[] = [
                        'id' => $node->id,
                        'slug' => $node->slug,
                        'label' => $node->getTranslation('title', $locale),
                        'depth' => $depth,
                        'count' => $count,
                    ];

                    foreach ($childOptions as $childOption) {
                        $options[] = $childOption;
                    }
                }

                return $options;
            };

            return $flatten(
                CompanyCategory::query()
                    ->tree()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get()
                    ->toTree(),
                0
            );
        });
    }

    /**
     * Active states ordered by their translated name.
     *
     * NOTE: the cache key must be flushed whenever active states change.
     *
     * @return array<int, array{id: int, slug: string, label: string, depth: int, count: int}>
     */
    #[Computed(persist: true)]
    public function stateOptions(): array
    {
        $locale = app()->getLocale();

        // Cache key must be flushed when active states change. The v3 suffix
        // invalidates payloads cached before empty states were filtered out.
        return Cache::rememberForever("home.filters.states.v3.{$locale}", function () use ($locale): array {
            $counts = CompanyPublication::query()
                ->active()
                ->whereNotNull('state_id')
                ->selectRaw('state_id, COUNT(*) AS aggregate')
                ->groupBy('state_id')
                ->pluck('aggregate', 'state_id');

            return State::query()
                ->active()
                ->get()
                ->map(fn (State $state): array => [
                    'id' => $state->id,
                    'slug' => $state->slug,
                    'label' => $state->getTranslation('name', $locale),
                    // States are flat; kept so both option lists share one shape.
                    'depth' => 0,
                    'count' => (int) ($counts[$state->id] ?? 0),
                ])
                ->filter(fn (array $option): bool => $option['count'] > 0)
                ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();
        });
    }

    /**
     * Data-driven filter definitions rendered via a loop in the template.
     * Adding a new filter is a one-line entry here; the markup needs no
     * change. `type` is one of: 'select' | 'toggle'. `options` is only
     * meaningful for 'select'.
     *
     * @return array<int, array{
     *     key: string,
     *     label: string,
     *     type: string,
     *     icon: string,
     *     count_key: string,
     *     options: array<int, array<string, mixed>>,
     * }>
     */
    public function filters(): array
    {
        return [
            [
                'key' => 'categoryId',
                'label' => __('home.filters.category'),
                'type' => 'select',
                'icon' => 'ki-abstract-26',
                'count_key' => 'home.categories_count',
                'options' => $this->categoryOptions,
            ],
            [
                'key' => 'stateId',
                'label' => __('home.filters.state'),
                'type' => 'select',
                'icon' => 'ki-map',
                'count_key' => 'home.states_count',
                'options' => $this->stateOptions,
            ],
            [
                'key' => 'verifiedOnly',
                'label' => __('home.filters.verified'),
                'type' => 'toggle',
                'icon' => 'ki-shield-tick',
                'count_key' => '',
                'options' => [],
            ],
        ];
    }

    /**
     * Assemble the non-empty filter values into query parameters for the
     * future search results page.
     */
    public function search(): void
    {
        $this->appliedFilters = array_filter([
            'keyword' => $this->keyword !== '' ? $this->keyword : null,
            'category' => $this->categoryId,
            'state' => $this->stateId,
            'verified_only' => $this->verifiedOnly ? true : null,
        ]);

        // TODO: redirect to the search results route once the page exists.
    }

    /**
     * Clear every filter back to its default state.
     */
    public function resetFilters(): void
    {
        $this->reset('keyword', 'categoryId', 'stateId', 'verifiedOnly', 'appliedFilters');
    }

    /**
     * Number of advanced filters currently set (keyword is excluded because
     * it is always visible in its own input).
     */
    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->categoryId, $this->stateId])
            ->filter(fn (?int $value): bool => $value !== null)
            ->when($this->verifiedOnly, fn (Collection $count) => $count->push(1))
            ->count();
    }
};
?>

{{-- Scoped rule so the advanced panel and dropdowns never flash open before
     Alpine initializes. Kept local to this component; the landing layout has
     no global [x-cloak] rule and does not load resources/css/app.css. --}}
<style>
    [x-cloak] { display: none !important; }
</style>

<form
    x-data="{ advancedOpen: false }"
    x-on:submit.prevent="search()"
    x-on:keydown.escape.window="advancedOpen = false"
>
    <!--begin::Card-->
    <div class="card mb-7">
        <!--begin::Card body-->
        <div class="card-body py-7">
            {{-- Primary keyword row --}}
            <div class="d-flex flex-column flex-md-row align-items-stretch gap-3">
                <!--begin::Keyword input-->
                <div class="position-relative flex-grow-1">
                    <i class="ki-duotone ki-magnifier fs-2 text-gray-500 position-absolute top-50 translate-middle-y end-0 me-6">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <input
                        type="text"
                        class="form-control form-control-solid w-100 pe-10"
                        wire:model.live.debounce.0ms="keyword"
                        placeholder="{{ __('home.hero_search_placeholder') }}"
                    >
                </div>
                <!--end::Keyword input-->

                <!--begin::Actions-->
                <div class="d-flex align-items-center gap-3 flex-shrink-0">
                    <button type="submit" class="btn btn-primary w-100 w-md-auto">
                        <i class="ki-duotone ki-magnifier fs-2 me-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        {{ __('home.hero_search_button') }}
                    </button>

                    <button
                        type="button"
                        class="btn btn-light btn-active-light-primary w-100 w-md-auto position-relative"
                        x-on:click="advancedOpen = !advancedOpen"
                        :aria-expanded="advancedOpen"
                    >
                        <i class="ki-duotone ki-filter fs-3 me-1"></i>
                        <span>{{ __('home.filters.toggle') }}</span>
                        @if ($this->activeFiltersCount > 0)
                            <span class="badge badge-light-primary ms-1">{{ $this->activeFiltersCount }}</span>
                        @endif
                        <i
                            class="ki-duotone ki-down fs-3 ms-2"
                            style="transition: transform 0.2s ease"
                            :style="advancedOpen ? 'transform: rotate(180deg)' : ''"
                        ></i>
                    </button>
                </div>
                <!--end::Actions-->
            </div>

            {{-- Advanced filter panel --}}
            <div
                x-show="advancedOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                x-cloak
            >
                <!--begin::Separator-->
                <div class="separator separator-dashed mt-6 mb-6"></div>
                <!--end::Separator-->

                <!--begin::Filter row-->
                <div class="row g-6">
                    @foreach ($this->filters() as $filter)
                        @if ($filter['type'] === 'toggle')
                            {{-- Verified-only toggle --}}
                            <div class="col-xl-4 col-lg-6 col-md-6">
                                <label class="fs-7 fw-semibold text-gray-600 mb-2 d-block">
                                    <i class="ki-duotone {{ $filter['icon'] }} fs-3 me-1 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    {{ $filter['label'] }}
                                </label>
                                <div class="form-check form-switch form-check-custom form-check-solid form-check-lg">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="advance-search-{{ $filter['key'] }}"
                                        wire:model="{{ $filter['key'] }}"
                                    >
                                    <label class="form-check-label" for="advance-search-{{ $filter['key'] }}">
                                        {{ __('home.filters.verified_hint') }}
                                    </label>
                                </div>
                            </div>
                        @else
                            {{-- Custom Alpine select dropdown (no Select2) --}}
                            <div class="col-xl-4 col-lg-6 col-md-6">
                                <label class="fs-7 fw-semibold text-gray-600 mb-2 d-block">
                                    <i class="ki-duotone {{ $filter['icon'] }} fs-3 me-1 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    {{ $filter['label'] }}
                                </label>

                                <div
                                    class="position-relative"
                                    x-data="{
                                        open: false,
                                        current: @entangle($filter['key']),
                                        labels: @js(collect($filter['options'])->mapWithKeys(fn (array $option) => [$option['id'] => $option['label']])->all()),
                                        get currentValue() {
                                            if (Array.isArray(this.current)) {
                                                return this.current.length ? this.current[0] : null;
                                            }
                                            return this.current;
                                        },
                                        get display() {
                                            var v = this.currentValue;
                                            if (v === null || v === '' || v === undefined) {
                                                return {{ \Illuminate\Support\Js::from(__('home.filters.any')) }};
                                            }
                                            return this.labels[v] ?? v;
                                        },
                                        select(value) {
                                            this.current = Array.isArray(this.current) ? [value] : value;
                                            this.open = false;
                                        }
                                    }"
                                    x-on:keydown.escape="open = false"
                                >
                                    <!--begin::Trigger-->
                                    <button
                                        type="button"
                                        class="form-select form-select-solid form-select-lg w-100 text-start d-flex align-items-center justify-content-between ps-4 pe-4"
                                        x-on:click="open = !open"
                                        :aria-expanded="open"
                                    >
                                        <span class="text-truncate" x-text="display"></span>
                                        <i
                                            class="ki-duotone ki-down fs-3 ms-2 flex-shrink-0"
                                            style="transition: transform 0.2s ease"
                                            :style="open ? 'transform: rotate(180deg)' : ''"
                                        ></i>
                                    </button>
                                    <!--end::Trigger-->

                                    <!--begin::Dropdown-->
                                    <div
                                        class="dropdown-menu w-100 p-3 show mt-2 border overflow-y-auto"
                                        style="max-height: 320px;"
                                        x-show="open"
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 -translate-y-1"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        x-transition:leave="transition ease-in duration-100"
                                        x-transition:leave-start="opacity-100 translate-y-0"
                                        x-transition:leave-end="opacity-0 -translate-y-1"
                                        x-on:click.away="open = false"
                                        x-cloak
                                    >
                                        {{-- "Any" reset option --}}
                                        <div
                                            class="d-flex align-items-center px-2 py-1 rounded menu-link cursor-pointer"
                                            role="button"
                                            x-on:click="current = Array.isArray(current) ? [] : null; open = false"
                                            :class="currentValue === null || currentValue === '' ? 'active' : ''"
                                        >
                                            <i class="ki-duotone ki-cross-square fs-2 me-2 text-gray-500">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            <span class="fw-semibold text-gray-700">{{ __('home.filters.any') }}</span>
                                        </div>
                                        <div class="separator separator-dashed my-2"></div>

                                        @foreach ($filter['options'] as $option)
                                            <div
                                                class="d-flex align-items-center justify-content-between px-2 py-1 rounded menu-link cursor-pointer"
                                                role="button"
                                                style="padding-inline-start: {{ 8 + ($option['depth'] ?? 0) * 16 }}px;"
                                                x-on:click="select({{ $option['id'] }})"
                                                :class="Number(currentValue) === {{ $option['id'] }} ? 'active' : ''"
                                            >
                                                <span class="fw-semibold text-gray-700 text-truncate">{{ $option['label'] }}</span>
                                                <span class="d-flex align-items-center gap-2 flex-shrink-0">
                                                    @if ($option['count'] > 0)
                                                        <span class="badge badge-light-secondary">{{ __($filter['count_key'], ['count' => number_format($option['count'])]) }}</span>
                                                    @endif
                                                    <i class="ki-duotone ki-check-circle fs-3 text-primary" x-show="Number(currentValue) === {{ $option['id'] }}">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                    <!--end::Dropdown-->
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
                <!--end::Filter row-->

                <!--begin::Panel footer-->
                <div class="d-flex justify-content-end mt-6">
                    <button type="button" class="btn btn-sm btn-light-danger" wire:click="resetFilters">
                        <i class="ki-duotone ki-cross-circle fs-2 me-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        {{ __('home.filters.reset') }}
                    </button>
                </div>
                <!--end::Panel footer-->
            </div>
            <!--end::Advanced filter panel-->
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->
</form>
