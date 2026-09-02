<?php

use App\Livewire\Concerns\RecordsPageView;
use App\Models\CompanyCategory;
use App\Models\CompanyPublication;
use App\Models\Page;
use App\Models\State;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts::landing')]
class extends Component {

    use RecordsPageView;

    public Page $page;

    public function mount(): void
    {
        $this->page = Page::where('slug', '/')->firstOrFail();

        $this->recordPageView($this->page);

        // Pushes the SeoMeta title/description/canonical (and OG/Twitter/
        // JSON-LD when the row exists) into SEOTools; the landing layout
        // renders them via SEOTools::generate().
        $this->page->applySeoTags();
    }

    /**
     * Root categories that have published companies (directly or via a
     * descendant — the category page lists the whole subtree). Cached per
     * locale with the same #[Computed(persist)] + Cache::rememberForever
     * convention as home.advance-search.
     *
     * @return array<int, array{slug: string, title: string, icon: ?string, count: int}>
     */
    #[Computed(persist: true)]
    public function homeCategories(): array
    {
        $locale = app()->getLocale();

        // Cache key must be flushed whenever active categories or published
        // companies change.
        return Cache::rememberForever("home.sections.categories.v2.{$locale}", function () use ($locale): array {
            $directCounts = CompanyPublication::query()
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

            $tree = CompanyCategory::query()
                ->tree()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->toTree();

            $sumSubtree = function (EloquentCollection $nodes) use (&$sumSubtree, $directCounts, $locale): array {
                $total = 0;
                $roots = [];

                foreach ($nodes as $node) {
                    [$childTotal] = $sumSubtree($node->children);

                    $nodeTotal = (int) ($directCounts[$node->id] ?? 0) + $childTotal;

                    // Only the root level is listed; descendants just feed
                    // the root's subtree count.
                    if ($node->parent_id === null && $nodeTotal > 0) {
                        $roots[] = [
                            'slug' => $node->slug,
                            'title' => $node->getTranslation('title', $locale),
                            'icon' => $node->getFirstMediaUrl('logo', 'webp') ?: $node->getFirstMediaUrl('logo'),
                            'count' => $nodeTotal,
                        ];
                    }

                    $total += $nodeTotal;
                }

                return [$total, $roots];
            };

            return $sumSubtree($tree)[1];
        });
    }

    /**
     * The most recently published companies. Only the id list is cached
     * (Cache::rememberForever, the same convention as home.advance-search —
     * the id list is locale-independent); the models are re-fetched so the
     * cards resolve translations at render time and never go stale.
     *
     * @return array<int, CompanyPublication>
     */
    #[Computed]
    public function homeRecentCompanies(): array
    {
        // Cache key must be flushed whenever publications appear or expire.
        $ids = Cache::rememberForever('home.sections.recent-companies.v1', function (): array {
            return CompanyPublication::query()
                ->active()
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(8)
                ->pluck('id')
                ->all();
        });

        if ($ids === []) {
            return [];
        }

        return CompanyPublication::query()
            ->active()
            ->with(['media', 'categories', 'state'])
            ->whereIn('id', $ids)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            ->all();
    }

    /**
     * Provinces that have published companies. Cached per locale with the
     * same #[Computed(persist)] + Cache::rememberForever convention as
     * home.advance-search.
     *
     * @return array<int, array{slug: string, name: string, count: int}>
     */
    #[Computed(persist: true)]
    public function homeStates(): array
    {
        $locale = app()->getLocale();

        // Cache key must be flushed whenever active states or published
        // companies change. v2: carries the parent country slug for the
        // /countries/{country}/{state} URLs.
        return Cache::rememberForever("home.sections.states.v2.{$locale}", function () use ($locale): array {
            $counts = CompanyPublication::query()
                ->active()
                ->whereNotNull('state_id')
                ->selectRaw('state_id, COUNT(*) AS aggregate')
                ->groupBy('state_id')
                ->pluck('aggregate', 'state_id');

            return State::query()
                ->active()
                ->with('country:id,slug')
                ->get()
                ->map(fn (State $state): array => [
                    'slug' => $state->slug,
                    'country' => $state->country?->slug,
                    'name' => $state->getTranslation('name', $locale),
                    'count' => (int) ($counts[$state->id] ?? 0),
                ])
                ->filter(fn (array $state): bool => $state['count'] > 0 && $state['country'] !== null)
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();
        });
    }

    /**
     * Real counts over published data, cached per locale with the same
     * #[Computed(persist)] + Cache::rememberForever convention as
     * home.advance-search.
     *
     * @return array{companies: int, categories: int, states: int, countries: int}
     */
    #[Computed(persist: true)]
    public function homeNumbers(): array
    {
        $locale = app()->getLocale();

        // Cache key must be flushed whenever published data changes.
        return Cache::rememberForever("home.sections.key-numbers.v1.{$locale}", function (): array {
            return [
                'companies' => (int) CompanyPublication::query()->active()->count(),
                'categories' => (int) DB::table('company_category_company_publication')
                    ->whereIn('company_publication_id', CompanyPublication::query()->active()->select('id'))
                    ->distinct()
                    ->count('company_category_id'),
                'states' => (int) CompanyPublication::query()
                    ->active()
                    ->whereNotNull('state_id')
                    ->distinct()
                    ->count('state_id'),
                'countries' => (int) DB::table('company_export_countries')
                    ->whereIn('company_id', CompanyPublication::query()->active()->select('company_id'))
                    ->distinct()
                    ->count('country_id'),
            ];
        });
    }

    public function render()
    {
        return $this->view();
    }

};
?>

<div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">

    <div class="content flex-row-fluid" id="kt_content">

        {{-- A. Hero — the single <h1> comes from the Page row; the toolbar
             heading is demoted to a span on this route (⚡heading1's
             SELF_HEADING_ROUTES). --}}
        <livewire:home.hero :page="$this->page"/>

        {{-- B. Category grid --}}
        @if ($this->homeCategories !== [])
            <livewire:home.category-grid :categories="$this->homeCategories"/>
        @endif

        {{-- C. Recent / featured companies --}}
        @if ($this->homeRecentCompanies !== [])
            <livewire:home.recent-companies :companies="$this->homeRecentCompanies"/>
        @endif

        {{-- D. Browse by province --}}
        @if ($this->homeStates !== [])
            <livewire:home.state-grid :states="$this->homeStates"/>
        @endif

        {{-- E. Key numbers --}}
        @if ($this->homeNumbers['companies'] > 0)
            <livewire:home.key-numbers :numbers="$this->homeNumbers"/>
        @endif

        {{-- F. Global coverage — own section copy so the homepage never
             repeats the /countries page heading semantics (h2 here). --}}
        <livewire:maps.world-globe :heading="__('home.globe_title')" :lead="__('home.globe_subtitle')"/>

        {{-- PLACEHOLDER (part 2): the Page row's `intro_body` renders here,
             above the sections, once the intro section is designed. --}}
        {{-- PLACEHOLDER (part 3): FAQ section + FAQPage JSON-LD. --}}
        {{-- PLACEHOLDER (parts 2–3): WebSite / Organization JSON-LD. --}}

    </div>

</div>
