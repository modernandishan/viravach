<?php

use App\Livewire\Concerns\RecordsPageView;
use Artesaos\SEOTools\Facades\SEOTools;
use App\Models\CompanyPublication;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

new
#[Layout('layouts::landing')]
class extends Component {
    use RecordsPageView;

    public CompanyPublication $publication;

    public ?string $featuredImageUrl = null;

    public ?string $featuredImageAlt = null;

    public ?string $featuredImageTitle = null;

    public ?string $featuredImageCaption = null;

    public ?string $introVideoUrl = null;

    public ?string $introVideoTitle = null;

    public ?string $introVideoPosterUrl = null;

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

        // Extend the page's existing JSON-LD output (SEOTools jsonLdMulti,
        // driven by applySeoTags) with content-derived blocks. The specs go
        // onto the default Organization block as additionalProperty; the
        // FAQ becomes its own FAQPage block, only when there is a real FAQ.
        $content = $this->publication->contentFor(app()->getLocale());

        if ($content !== null && ! empty($content['specs'])) {
            SEOTools::jsonLdMulti()->addValue('additionalProperty', collect($content['specs'])
                ->map(fn (array $spec): array => [
                    '@type' => 'AdditionalProperty',
                    'name' => (string) ($spec['label'] ?? ''),
                    'value' => (string) ($spec['value'] ?? ''),
                ])
                ->all());
        }

        if ($content !== null && count($content['faq'] ?? []) >= 2) {
            $jsonLd = SEOTools::jsonLdMulti();

            $jsonLd->newJsonLd();
            $jsonLd->setType('FAQPage');
            $jsonLd->addValue('mainEntity', collect($content['faq'])
                ->map(fn (array $item): array => [
                    '@type' => 'Question',
                    'name' => (string) ($item['q'] ?? ''),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => (string) ($item['a'] ?? ''),
                    ],
                ])
                ->values()
                ->all());
        }

        $this->loadFeaturedImage();

        // Must run after loadFeaturedImage(): the video's poster falls back
        // to the featured image URL that call resolves.
        $this->loadIntroVideo();
    }

    /**
     * The featured_image media item (1024x576, 16:9), its localized
     * alt/title/caption/description, and an ImageObject added onto the
     * SAME default JSON-LD block the specs/additionalProperty above uses
     * — not a second jsonLdMulti() group. Nothing is set when there is no
     * featured image, so the page renders no image element at all.
     */
    private function loadFeaturedImage(): void
    {
        $featuredImage = $this->publication->getFirstMedia('featured_image');

        if ($featuredImage === null) {
            return;
        }

        $locale = app()->getLocale();

        $this->featuredImageUrl = $featuredImage->getUrl('webp');
        $this->featuredImageAlt = $this->localizedMediaProperty($featuredImage, 'alt', $locale)
            ?? (string) $this->publication->name;
        $this->featuredImageTitle = $this->localizedMediaProperty($featuredImage, 'title', $locale)
            ?? (string) $this->publication->name;
        $this->featuredImageCaption = $this->localizedMediaProperty($featuredImage, 'caption', $locale);

        SEOTools::jsonLdMulti()->addValue('image', array_filter([
            '@type' => 'ImageObject',
            'contentUrl' => $this->featuredImageUrl,
            'caption' => $this->featuredImageCaption,
            'description' => $this->localizedMediaProperty($featuredImage, 'description', $locale),
        ]));
    }

    /**
     * The intro_video media item, its localized title, and a VideoObject
     * added onto the SAME default JSON-LD block loadFeaturedImage() and the
     * specs use — not a second jsonLdMulti() group. Nothing is set when the
     * snapshot carries no video, so the page renders no player at all.
     *
     * 'duration' is deliberately absent: it is validated at upload time but
     * never persisted, and recovering it here would mean re-reading the file
     * from S3 on every page render.
     */
    private function loadIntroVideo(): void
    {
        $introVideo = $this->publication->getFirstMedia('intro_video');

        if ($introVideo === null) {
            return;
        }

        $locale = app()->getLocale();

        $this->introVideoUrl = $introVideo->getUrl();
        $this->introVideoTitle = $this->localizedMediaProperty($introVideo, 'title', $locale)
            ?? (string) $this->publication->name;
        // No video-specific thumbnail conversion exists in this project, so
        // the featured image is the only poster candidate; omitted entirely
        // when the company has no featured image either.
        $this->introVideoPosterUrl = $this->featuredImageUrl;

        SEOTools::jsonLdMulti()->addValue('video', array_filter([
            '@type' => 'VideoObject',
            'name' => $this->introVideoTitle,
            'description' => $this->localizedMediaProperty($introVideo, 'description', $locale),
            'contentUrl' => $this->introVideoUrl,
            'thumbnailUrl' => $this->introVideoPosterUrl,
            'uploadDate' => $introVideo->created_at?->toIso8601String(),
        ]));
    }

    /**
     * $property's value for $locale, falling back to the site's fallback
     * locale, then null — the shared two-step chain every featured_image
     * custom property (alt/title/caption/description) starts from. alt
     * and title fall back further to the company name (see
     * loadFeaturedImage()); caption/description are simply omitted when
     * both steps come up empty.
     */
    private function localizedMediaProperty(Media $media, string $property, string $locale): ?string
    {
        $values = (array) $media->getCustomProperty($property, []);

        $value = trim((string) ($values[$locale] ?? ''));

        if ($value !== '') {
            return $value;
        }

        $fallbackLocale = (string) config('app.fallback_locale');
        $fallbackValue = trim((string) ($values[$fallbackLocale] ?? ''));

        return $fallbackValue !== '' ? $fallbackValue : null;
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

                {{-- Featured image: the LCP element, so eager/high-priority
                     rather than lazy. Independent of $content — gated only
                     on whether an image actually exists. --}}
                @if ($featuredImageUrl)
                    <div class="mb-6 mb-xl-9">
                        <img
                            src="{{ $featuredImageUrl }}"
                            alt="{{ $featuredImageAlt }}"
                            @if ($featuredImageTitle) title="{{ $featuredImageTitle }}" @endif
                            class="rounded w-100"
                            style="aspect-ratio: 16 / 9; object-fit: cover;"
                            loading="eager"
                            fetchpriority="high"
                        >
                        @if ($featuredImageCaption)
                            <div class="text-muted fs-7 mt-2">{{ $featuredImageCaption }}</div>
                        @endif
                    </div>
                @endif

                @if (($content = $publication->contentFor(app()->getLocale())) !== null)
                    <x-company-content.hero :data="$content['hero'] ?? []" />
                    <x-company-content.about :data="$content['about'] ?? []" />
                    <x-company-content.offerings :data="$content['offerings'] ?? []" />
                    <x-company-content.strengths :data="$content['strengths'] ?? []" />
                    <x-company-content.markets :data="$content['markets'] ?? []" />
                    <x-company-content.specs :data="$content['specs'] ?? []" />
                    <x-company-content.faq :data="$content['faq'] ?? []" />
                    <x-company-content.cta :data="$content['cta'] ?? []" />
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

                {{-- Read from the publication snapshot, not $publication->company:
                     the previous read-through exposed unapproved draft edits on
                     a public page. --}}
                @if ($publication->exportCountries->isNotEmpty())
                    <div class="card mb-6 mb-xl-9">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title">
                                <h2>{{ __('companies.profile_export_countries') }}</h2>
                            </div>
                        </div>
                        <div class="card-body pt-0 d-flex flex-wrap gap-2">
                            @foreach ($publication->exportCountries as $country)
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
                        <livewire:chat-elements.chat-modal
                            :company-id="$publication->company->id"
                            :company-name="(string) $publication->name"
                        />
                    </div>
                @endif

                {{-- Intro video, rendered only when the snapshot actually
                     carries one. The player bundle is pushed from inside this
                     @if, so pages without a video never load Vidstack at all.

                     One @vite() call, pushed to the head 'styles' stack rather
                     than the end-of-body 'scripts' stack: @vite emits BOTH the
                     <link> and the <script> for an entry, so calling it twice
                     to separate them would duplicate the stylesheet and run the
                     module twice. Head placement costs nothing — @vite emits a
                     type="module" script, which the HTML spec defers by
                     default, exactly as the layout's own base @vite does. --}}
                @if ($introVideoUrl)
                    @push('styles')
                        @vite(['resources/js/video-player.js'])
                    @endpush
                    <div class="card mb-6">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title">
                                <h2>{{ __('companies.profile_intro_video') }}</h2>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            {{-- aria-label, not title: media-player declares the
                                 former and not the latter. crossorigin is left
                                 off deliberately — the MinIO bucket serves no
                                 CORS headers for this origin, and requesting a
                                 CORS fetch would break playback outright. --}}
                            <media-player
                                class="w-100"
                                aria-label="{{ $introVideoTitle }}"
                                src="{{ $introVideoUrl }}"
                                playsinline
                            >
                                <media-provider>
                                    @if ($introVideoPosterUrl)
                                        <media-poster
                                            class="vds-poster"
                                            src="{{ $introVideoPosterUrl }}"
                                            alt="{{ $introVideoTitle }}"
                                        ></media-poster>
                                    @endif
                                </media-provider>
                                <media-video-layout></media-video-layout>
                            </media-player>
                        </div>
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

                        {{-- Business address from the snapshot, rendered only when
                             a street line was actually published. This is the
                             company's public business address — never the owner's
                             personal profile address, which lives on `profiles`
                             and is never published. --}}
                        @php
                            $addressLine = trim((string) $publication->address_line);
                            $addressParts = array_filter([
                                $publication->city?->name,
                                $publication->state?->name,
                                $publication->country?->name,
                            ]);
                        @endphp
                        @if ($addressLine !== '')
                            <div class="mb-5">
                                <div class="fw-bold text-gray-800">{{ __('companies.field_address_line') }}</div>
                                <div class="text-gray-600">{{ $addressLine }}</div>
                                @if ($addressParts !== [])
                                    <div class="text-gray-500 fs-7">{{ implode(', ', $addressParts) }}</div>
                                @endif
                                @if ($publication->postal_code)
                                    <div class="text-gray-500 fs-7" dir="ltr">
                                        {{ __('companies.field_postal_code') }}: {{ $publication->postal_code }}
                                    </div>
                                @endif
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

                        {{-- $addressLine is part of this block now, so it counts
                             towards "has contact info" — otherwise a company with
                             only an address would show the address and the
                             "nothing provided" notice at the same time. --}}
                        @if (! $publication->website && ! $publication->email && ! $publication->phones && ! $publication->social_links && $addressLine === '')
                            <div class="text-muted">{{ __('companies.profile_no_contact_info') }}</div>
                        @endif
                    </div>
                </div>
            </div>
            <!--end::Sidebar-->

        </div>
    </div>
</div>
