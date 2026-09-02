<?php

use App\Livewire\Concerns\RecordsPageView;
use App\Models\CompanyCategory;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts::landing')]
class extends Component {
    // ListsCompanies is gone: the listing (with its filters, sorting and
    // crawlable pagination) now lives in ⚡company-list, and the category
    // subtree is expressed as that component's category scope.
    // RecordsPageView was reached through that trait, so it is used directly.
    use RecordsPageView;

    public CompanyCategory $category;

    public function mount(string $slug): void
    {
        $this->category = CompanyCategory::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $this->recordPageView($this->category);

        // Pushes the category's SeoMeta (title/description/canonical, OG,
        // Twitter, JSON-LD) into SEOTools, falling back to the translated
        // title/company-count description when no SeoMeta row exists (see
        // CompanyCategory::getSeoFallbackDescription()). Same call as
        // ⚡country.blade.php and ⚡company-state.blade.php — do not replace
        // with direct SEOTools::set*() calls, see HasSeo::applySeoTags()'s
        // own comment on why those shortcuts leak into OG/Twitter/JSON-LD
        // even without a SeoMeta row.
        $this->category->applySeoTags();
    }

    #[Computed]
    public function categoryTree(): Collection
    {
        return CompanyCategory::query()
            ->tree()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->toTree();
    }

    #[Computed]
    public function activeCategoryIds(): array
    {
        return $this->category->ancestorsAndSelf()
            ->orderBy('depth')
            ->pluck('id')
            ->all();
    }

    public function render()
    {
        return $this->view()->title($this->category->title.' | '.__('globals.viravach'));
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">
        <div class="d-flex flex-column flex-lg-row">

            <!--begin::Sidebar-->
            <div class="flex-column flex-lg-row-auto w-lg-300px mb-10 mb-lg-0 me-lg-7 me-xl-10">
                <div class="card card-flush">
                    <div class="card-header pt-7">
                        <h3 class="card-title">{{ __('companies.categories') }}</h3>
                    </div>
                    <div class="card-body pt-3">
                        <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6" data-kt-menu="true">
                            <x-company-elements.category-tree :nodes="$this->categoryTree" :active-ids="$this->activeCategoryIds" />
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Sidebar-->

            <!--begin::Main-->
            <div class="flex-lg-row-fluid">

                <!--begin::Category header card-->
                <div class="card mb-6 mb-xl-9">
                    <div class="card-body">
                        <h1 class="fs-2 fw-bold text-gray-900 mb-4">{{ $category->title }}</h1>
                        @if ($category->description)
                            <div class="fs-6 text-gray-700">
                                {!! $category->description !!}
                            </div>
                        @endif
                    </div>
                </div>
                <!--end::Category header card-->

                {{-- Company listing, scoped to this category and its
                     descendants — the same subtree
                     CompanyCategory::publishedCompaniesCount() counts for the
                     SEO fallback description, so the two never disagree. The
                     category filter hides itself under a category scope. --}}
                <livewire:company-elements.company-list :category-id="$category->id" />

            </div>
            <!--end::Main-->

        </div>
    </div>
</div>
