@props(['company', 'views' => 0])

@php
    $category = $company->categories->first();
    $state = $company->state;
    $excerpt = \Illuminate\Support\Str::limit(
        strip_tags($company->description ?: $company->summary ?: ''),
        120
    );
@endphp

<a href="{{ route('companies.show', ['slug' => $company->slug]) }}" class="card border-hover-primary h-100 d-flex flex-column">
    <div class="card-header border-0 pt-9">
        <div class="card-title m-0">
            <div class="symbol symbol-50px w-50px bg-light">
                @if ($company->hasMedia('logo'))
                    <img src="{{ $company->getFirstMediaUrl('logo', 'webp') }}" alt="{{ $company->name }}" class="p-3">
                @else
                    <span class="symbol-label bg-light-primary text-primary fw-bold fs-3">
                        {{ \Illuminate\Support\Str::substr($company->name, 0, 1) }}
                    </span>
                @endif
            </div>
        </div>
        @if ($company->is_verified)
            <div class="card-toolbar">
                <span class="badge badge-light-success fw-bold">{{ __('companies.verified') }}</span>
            </div>
        @endif
    </div>
    <div class="card-body pt-0 d-flex flex-column flex-grow-1">
        @if ($category)
            <span class="badge badge-light-primary fw-bold mb-2 align-self-start">{{ $category->title }}</span>
        @endif

        <div class="fs-4 fw-bold text-gray-900 mb-1">{{ $company->name }}</div>

        @if ($state)
            <div class="text-gray-500 fs-7 mb-2 d-flex align-items-center gap-1">
                <i class="ki-duotone ki-geolocation fs-6">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                {{ $state->name }}
            </div>
        @endif

        @if ($excerpt)
            <p class="text-gray-500 fw-semibold fs-6 mb-3">{{ $excerpt }}</p>
        @endif

        <div class="d-flex align-items-center text-gray-500 fs-7 mt-auto pt-2">
            <i class="ki-duotone ki-eye fs-6 me-1">
                <span class="path1"></span>
                <span class="path2"></span>
                <span class="path3"></span>
            </i>
            {{ __('companies.views_count', ['count' => number_format($views)]) }}
        </div>
    </div>
</a>
