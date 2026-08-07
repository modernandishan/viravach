<?php

use App\Livewire\Concerns\RecordsPageView;
use App\Models\CompanyPublication;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts::landing')]
class extends Component {
    use RecordsPageView;

    public CompanyPublication $publication;

    public function mount(string $slug): void
    {
        // The public site only ever serves the approved snapshot, never the
        // live Company draft.
        $this->publication = CompanyPublication::query()
            ->active()
            ->where('slug', $slug)
            ->with(['media', 'categories', 'state', 'company.brands', 'company.exportCountries', 'seo', 'seo.media'])
            ->firstOrFail();

        $this->recordPageView($this->publication);

        $this->publication->applySeoTags();
    }

    public function render()
    {
        return $this->view()->title($this->publication->name.' | '.__('globals.viravach'));
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">

        <!--begin::Header card-->
        <div class="card mb-6 mb-xl-9">
            <div class="card-body pt-9 pb-0">
                <div class="d-flex flex-wrap flex-sm-nowrap mb-6">
                    <div class="d-flex flex-center flex-shrink-0 bg-light rounded w-100px h-100px w-lg-150px h-lg-150px me-7 mb-4">
                        @if ($publication->hasMedia('logo'))
                            <img class="mw-75px mw-lg-125px" src="{{ $publication->getFirstMediaUrl('logo', 'webp') }}" alt="{{ $publication->name }}">
                        @else
                            <span class="symbol-label bg-light-primary text-primary fw-bold fs-1 w-100 h-100 d-flex flex-center rounded">
                                {{ \Illuminate\Support\Str::substr($publication->name, 0, 1) }}
                            </span>
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                            <div class="d-flex flex-column">
                                <div class="d-flex align-items-center mb-1">
                                    <h1 class="text-gray-800 fs-2 fw-bold me-3 mb-0">{{ $publication->name }}</h1>
                                    @if ($publication->is_verified)
                                        <span class="badge badge-light-success fw-bold">{{ __('companies.verified') }}</span>
                                    @endif
                                </div>
                                @if ($publication->legal_name)
                                    <div class="text-gray-500 fw-semibold fs-6 mb-2">{{ $publication->legal_name }}</div>
                                @endif
                                <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2 gap-3">
                                    @if ($publication->state)
                                        <span class="d-flex align-items-center text-gray-500">
                                            <i class="ki-duotone ki-geolocation fs-4 me-1">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            {{ $publication->state->name }}
                                        </span>
                                    @endif
                                    @if ($publication->established_at)
                                        <span class="d-flex align-items-center text-gray-500">
                                            <i class="ki-duotone ki-calendar fs-4 me-1">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            {{ \App\Support\LocalizedDate::format($publication->established_at, \App\Support\LocalizedDate::FORMAT_YEAR) }}
                                        </span>
                                    @endif
                                    <span class="d-flex align-items-center text-gray-500">
                                        <i class="ki-duotone ki-eye fs-3 me-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                        {{ number_format(views($publication)->count()) }}
                                    </span>
                                    @foreach ($publication->categories as $category)
                                        <a href="{{ route('companies.category', ['slug' => $category->slug]) }}" class="badge badge-light-primary fw-bold">
                                            {{ $category->title }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        @if ($publication->summary)
                            <p class="text-gray-600 fw-semibold fs-6 mb-0">{{ $publication->summary }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <!--end::Header card-->

        <div class="d-flex flex-column flex-lg-row">

            <!--begin::Main column-->
            <div class="flex-lg-row-fluid me-lg-7 me-xl-10">

                @if ($publication->description)
                    <div class="card mb-6 mb-xl-9">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title">
                                <h2>{{ __('companies.profile_about') }}</h2>
                            </div>
                        </div>
                        <div class="card-body pt-0 fs-6 text-gray-700">
                            {!! $publication->description !!}
                        </div>
                    </div>
                @endif

                @if ($publication->company?->brands->isNotEmpty())
                    <div class="card mb-6 mb-xl-9">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title">
                                <h2>{{ __('companies.profile_brands') }}</h2>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row g-4">
                                @foreach ($publication->company->brands as $brand)
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="d-flex flex-column align-items-center text-center bg-light rounded p-4 h-100">
                                            <div class="symbol symbol-60px bg-white mb-3">
                                                @if ($brand->hasMedia('logo'))
                                                    <img src="{{ $brand->getFirstMediaUrl('logo', 'webp') }}" alt="{{ $brand->name }}" class="p-2">
                                                @else
                                                    <span class="symbol-label bg-light-primary text-primary fw-bold fs-3">
                                                        {{ \Illuminate\Support\Str::substr($brand->name, 0, 1) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="fw-bold text-gray-800">{{ $brand->name }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                @if ($publication->company?->exportCountries->isNotEmpty())
                    <div class="card mb-6 mb-xl-9">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title">
                                <h2>{{ __('companies.profile_export_countries') }}</h2>
                            </div>
                        </div>
                        <div class="card-body pt-0 d-flex flex-wrap gap-2">
                            @foreach ($publication->company->exportCountries as $country)
                                <span class="badge badge-light-primary fw-bold">{{ $country->name }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>
            <!--end::Main column-->

            <!--begin::Sidebar-->
            <div class="flex-column flex-lg-row-auto w-lg-300px w-xl-350px mb-10">
                {{-- Chattable only while the source company still exists: the
                     publication snapshot survives deletion of its draft. --}}
                @if ($publication->company)
                    <div class="mb-6">
                        <livewire:company-elements.company-chat
                            :company-id="$publication->company->id"
                            :company-name="(string) $publication->name"
                        />
                    </div>
                @endif
                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <h2>{{ __('companies.profile_contact_info') }}</h2>
                        </div>
                    </div>
                    <div class="card-body pt-0 fs-6">
                        @if ($publication->website)
                            <div class="mb-5">
                                <div class="fw-bold text-gray-800">{{ __('companies.field_website') }}</div>
                                <a href="{{ $publication->website }}" target="_blank" rel="noopener nofollow" class="text-gray-600 text-hover-primary">
                                    {{ $publication->website }}
                                </a>
                            </div>
                        @endif

                        @if ($publication->email)
                            <div class="mb-5">
                                <div class="fw-bold text-gray-800">{{ __('companies.field_email') }}</div>
                                <a href="mailto:{{ $publication->email }}" class="text-gray-600 text-hover-primary">{{ $publication->email }}</a>
                            </div>
                        @endif

                        @if ($publication->phones)
                            <div class="mb-5">
                                <div class="fw-bold text-gray-800">{{ __('companies.field_phones') }}</div>
                                @foreach ($publication->phones as $phone)
                                    <div class="text-gray-600" dir="ltr">{{ $phone }}</div>
                                @endforeach
                            </div>
                        @endif

                        @if ($publication->social_links)
                            <div class="mb-0">
                                <div class="fw-bold text-gray-800 mb-2">{{ __('companies.profile_social_links') }}</div>
                                <div class="d-flex flex-column gap-1">
                                    @foreach ($publication->social_links as $platform => $url)
                                        <a href="{{ $url }}" target="_blank" rel="noopener nofollow" class="text-gray-600 text-hover-primary">
                                            {{ __("companies.field_social_{$platform}") !== "companies.field_social_{$platform}" ? __("companies.field_social_{$platform}") : \Illuminate\Support\Str::headline($platform) }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (! $publication->website && ! $publication->email && ! $publication->phones && ! $publication->social_links)
                            <div class="text-muted">{{ __('companies.profile_no_contact_info') }}</div>
                        @endif
                    </div>
                </div>
            </div>
            <!--end::Sidebar-->

        </div>
    </div>
</div>
