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

    /**
     * The card's CSS, assembled here and raw-echoed (never written as a
     * literal <style> tag in the template) — Livewire's SFC compiler
     * statically scans the .blade.php SOURCE for a literal <style> block
     * and extracts it into a "styleModule" asset that is only ever
     * delivered by fetching it client-side; this app registers no route
     * to serve that asset, so a real <style> tag here is silently never
     * applied at all (confirmed: zero bytes of it reach the response).
     * Building the tag as a string and echoing it with {!! !!} is
     * invisible to that static scan, so it renders as plain, ordinary
     * HTML instead — guaranteed present regardless of any Livewire
     * asset pipeline. @once in the caller keeps it to one copy per page
     * no matter how many cards render.
     *
     * DESIGN.md's tokens (§2/§5) are not yet CSS custom properties
     * anywhere in this project, so the literal values from DESIGN.md are
     * used directly below, named via locally scoped --vv-* custom
     * properties so this block reads the same as the eventual tokens
     * file and only the :root declaration will need to move once one
     * exists.
     */
    public function cardStyles(): string
    {
        return <<<'CSS'
            <style>
            .vv-card {
                --vv-ink-900: #0F1720;
                --vv-ink-700: #33414F;
                --vv-ink-500: #64748B;
                --vv-ink-300: #CBD5E1;
                --vv-ink-100: #E9EEF3;
                --vv-ink-050: #F6F8FA;
                --vv-primary-700: #0F4C81;
                --vv-success-700: #157F4E;
                --vv-success-050: #E9F7F0; /* approximated -050 tint of success; DESIGN.md gives no exact value */

                display: flex;
                flex-direction: column;
                height: 100%;
                background: #FFFFFF;
                border: 1px solid var(--vv-ink-100);
                border-radius: 12px;
                box-shadow: 0 1px 2px rgba(15, 23, 32, .06);
                overflow: hidden;
                color: var(--vv-ink-700);
                text-decoration: none;
                transition: box-shadow .2s ease-out;
            }
            .vv-card:hover {
                box-shadow: 0 4px 12px rgba(15, 23, 32, .08);
                text-decoration: none;
                color: var(--vv-ink-700);
            }

            .vv-media {
                position: relative;
                width: 100%;
                aspect-ratio: 16 / 9;
                background: var(--vv-ink-100);
            }
            .vv-media img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
                transition: transform .2s ease-out, filter .2s ease-out;
            }
            .vv-card:hover .vv-media img {
                transform: scale(1.04);
                filter: saturate(.85);
            }
            .vv-media-placeholder {
                background: var(--vv-ink-050);
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .vv-media-initial {
                font-size: 24px;
                font-weight: 700;
                color: var(--vv-ink-300);
            }

            .vv-logo-plate {
                position: absolute;
                inset-inline-start: 12px;
                inset-block-end: 12px;
                inline-size: 44px;
                block-size: 44px;
                background: #FFFFFF;
                border: 1px solid var(--vv-ink-100);
                border-radius: 8px;
                padding: 4px;
            }
            .vv-logo-plate img {
                width: 100%;
                height: 100%;
                object-fit: contain;
                display: block;
            }

            .vv-body {
                display: flex;
                flex-direction: column;
                flex-grow: 1;
                padding: 20px;
            }

            .vv-eyebrow {
                font-size: 13px;
                line-height: 1.5;
                color: var(--vv-ink-500);
                margin-bottom: 4px;
                letter-spacing: .02em;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 100%;
            }
            .vv-eyebrow-uppercase {
                text-transform: uppercase;
            }
            .vv-title {
                font-size: 19px;
                font-weight: 600;
                line-height: 1.4;
                color: var(--vv-ink-900);
                margin: 0 0 8px;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            .vv-excerpt {
                font-size: 15px;
                line-height: 1.65;
                color: var(--vv-ink-700);
                margin: 0;
                display: -webkit-box;
                -webkit-line-clamp: 3;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            .vv-spacer {
                flex: 1 1 auto;
            }
            .vv-meta {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 16px;
                min-height: 20px;
                margin-top: 12px;
                font-size: 13px;
                color: var(--vv-ink-500);
            }
            .vv-meta-item {
                display: flex;
                align-items: center;
                gap: 4px;
                color: inherit;
            }
            .vv-divider {
                margin-top: 12px;
                height: 1px;
                background: var(--vv-ink-100);
            }
            .vv-footer {
                margin-top: 12px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
            }
            .vv-badge-verified {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                background: var(--vv-success-050);
                color: var(--vv-success-700);
                border-radius: 999px;
                font-size: 12px;
                font-weight: 600;
                padding: 4px 8px;
                white-space: nowrap;
            }
            .vv-action {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                color: var(--vv-primary-700);
                font-weight: 600;
                font-size: 13px;
                white-space: nowrap;
            }
            [dir="rtl"] .vv-chevron {
                transform: scaleX(-1);
            }

            @media (prefers-reduced-motion: reduce) {
                .vv-media img {
                    transition: none;
                }
                .vv-card:hover .vv-media img {
                    transform: none;
                    filter: none;
                }
            }
            </style>
            CSS;
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

        <div class="vv-footer">
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

@once
    {!! $this->cardStyles() !!}
@endonce
