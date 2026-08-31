<?php

namespace App\Livewire\CompanyElements;

use App\Models\CompanyPublication;
use Livewire\Component;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

new class extends Component
{
    public CompanyPublication $company;

    /**
     * Featured-image media item with its localized alt.
     *
     * @var array{url: ?string, alt: string}|null
     */
    public ?array $featured = null;

    /** @var array{url: string, alt: string}|null */
    public ?array $logo = null;

    public function mount(CompanyPublication $company): void
    {
        $this->company = $company;

        $featuredImage = $company->getFirstMedia('featured_image');
        $this->featured = $featuredImage !== null
            ? [
                'url' => $featuredImage->getUrl('webp'),
                'alt' => $this->localizedMediaProperty($featuredImage, 'alt') ?? (string) $company->name,
            ]
            : null;

        $logoMedia = $company->getFirstMedia('logo');
        $this->logo = $logoMedia !== null
            ? [
                'url' => $logoMedia->getUrl('webp'),
                'alt' => (string) $company->name,
            ]
            : null;
    }

    public function localizedMediaProperty(Media $media, string $property): ?string
    {
        $values = (array) $media->getCustomProperty($property, []);
        $locale = app()->getLocale();

        $value = trim((string) ($values[$locale] ?? ''));

        if ($value !== '') {
            return $value;
        }

        $fallback = trim((string) ($values[(string) config('app.fallback_locale')] ?? ''));

        return $fallback !== '' ? $fallback : null;
    }

    /**
     * DESIGN.md §3: never all-caps for Persian/Arabic.
     */
    public function eyebrowIsUppercase(): bool
    {
        return ! in_array(app()->getLocale(), ['fa', 'ar'], true);
    }

};
?>

@php
    $category = $company->categories->first();
    $state = $company->state;
    $excerpt = $company->excerpt();
    $initial = \Illuminate\Support\Str::substr($company->name, 0, 1);
    $href = route('companies.show', ['slug' => $company->slug]);
    $establishedYear = $company->established_at
        ? \App\Support\LocalizedDate::format($company->established_at, \App\Support\LocalizedDate::FORMAT_YEAR)
        : null;
@endphp

<a href="{{ $href }}" class="vv-card">
    {{-- MEDIA BLOCK — 16:9, full-bleed, clipped by the card's own
         overflow:hidden + radius (no radius of its own needed). --}}
    @if ($featured)
        <div class="vv-media">
            <img src="{{ $featured['url'] }}" alt="{{ $featured['alt'] }}" loading="lazy">
            @if ($logo)
                <div class="vv-logo-plate">
                    <img src="{{ $logo['url'] }}" alt="{{ $logo['alt'] }}">
                </div>
            @endif
        </div>
    @else
        <div class="vv-media vv-media-placeholder">
            <span class="vv-media-initial">{{ $initial }}</span>
            @if ($logo)
                <div class="vv-logo-plate">
                    <img src="{{ $logo['url'] }}" alt="{{ $logo['alt'] }}">
                </div>
            @endif
        </div>
    @endif

    <div class="vv-body">
        @if ($category)
            <div class="vv-eyebrow {{ $this->eyebrowIsUppercase() ? 'vv-eyebrow-uppercase' : '' }}">{{ $category->title }}</div>
        @endif

        <h3 class="vv-title">{{ $company->name }}</h3>

        @if ($excerpt)
            <p class="vv-excerpt">{{ $excerpt }}</p>
        @endif

        <div class="vv-spacer"></div>

        <div class="vv-meta">
            @if ($state)
                <span class="vv-meta-item">
                    <i class="ki-duotone ki-geolocation fs-6"><span class="path1"></span><span class="path2"></span></i>
                    {{ $state->name }}
                </span>
            @endif
            @if ($establishedYear)
                <span class="vv-meta-item">
                    <i class="ki-duotone ki-calendar fs-6"><span class="path1"></span><span class="path2"></span></i>
                    {{ $establishedYear }}
                </span>
            @endif
        </div>

        <div class="vv-divider"></div>

        <div class="vv-card-footer">
            @if ($company->is_verified)
                <span class="vv-badge-verified">
                    <i class="ki-duotone ki-shield-tick fs-6"><span class="path1"></span><span class="path2"></span></i>
                    {{ __('companies.verified') }}
                </span>
            @else
                <span></span>
            @endif
            <span class="vv-action">
                {{ __('companies.view_profile') }}
                <i class="ki-duotone ki-arrow-right vv-chevron fs-6"><span class="path1"></span><span class="path2"></span></i>
            </span>
        </div>
    </div>
</a>

