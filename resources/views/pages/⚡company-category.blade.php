<?php

use App\Livewire\Concerns\ListsCompanies;
use App\Models\CompanyCategory;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts::landing')]
class extends Component {
    use ListsCompanies;

    public CompanyCategory $category;

    public function mount(string $slug): void
    {
        $this->category = CompanyCategory::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $this->recordPageView($this->category);

        SEOTools::setTitle($this->category->seoTitle());

        if ($description = $this->category->seoDescription()) {
            SEOTools::setDescription($description);
        }

        SEOTools::setCanonical(url()->current());
    }

    protected function filterCompanies(Builder $query): Builder
    {
        $categoryIds = $this->category->descendantsAndSelf()->pluck('id');

        return $query->whereHas(
            'categories',
            fn (Builder $q) => $q->whereIn('company_categories.id', $categoryIds),
        );
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

                <!--begin::Companies grid-->
                <div class="row g-4">
                    @forelse ($this->companies as $company)
                        <div class="col-12 col-sm-6 col-lg-4" wire:key="company-{{ $company->id }}">
                            <livewire:company-elements.company-card :company="$company" />
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-center text-muted py-20">{{ __('companies.no_companies_found') }}</div>
                        </div>
                    @endforelse
                </div>
                <!--end::Companies grid-->

                <div class="d-flex flex-stack flex-wrap pt-10">
                    {{ $this->companies->links('livewire::bootstrap') }}
                </div>

            </div>
            <!--end::Main-->

        </div>
    </div>
</div>
