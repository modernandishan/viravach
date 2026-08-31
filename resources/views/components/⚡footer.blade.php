<?php

use App\Models\CompanyCategory;
use App\Models\CompanyPublication;
use App\Models\GeneralSetting;
use App\Models\State;
use App\Support\LocaleSwitchUrl;
use App\Support\TrustBadgeSanitizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

new class extends Component
{
    public GeneralSetting $gs;

    public function mount(): void
    {
        $this->gs = GeneralSetting::current();
    }

    /**
     * The 8 root categories with the most published companies, cached
     * forever per locale — same pattern as home's advance-search filters
     * (⚡advance-search.blade.php). Categories with zero published
     * companies are dropped rather than shown with a "(0)" count.
     *
     * @return array<int, array{slug: string, label: string, count: int}>
     */
    public function topCategories(): array
    {
        $locale = app()->getLocale();

        return Cache::rememberForever("footer.top_categories.v1.{$locale}", function () use ($locale): array {
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

            return CompanyCategory::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->get()
                ->map(fn (CompanyCategory $category): array => [
                    'slug' => $category->slug,
                    'label' => $category->getTranslation('title', $locale),
                    'count' => (int) ($counts[$category->id] ?? 0),
                ])
                ->filter(fn (array $category): bool => $category['count'] > 0)
                ->sortByDesc('count')
                ->take(8)
                ->values()
                ->all();
        });
    }

    /**
     * The 10 states with the most published companies, cached forever per
     * locale, same treatment as {@see topCategories()}.
     *
     * @return array<int, array{slug: string, label: string, count: int}>
     */
    public function topStates(): array
    {
        $locale = app()->getLocale();

        return Cache::rememberForever("footer.top_states.v1.{$locale}", function () use ($locale): array {
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
                    'slug' => $state->slug,
                    'label' => $state->getTranslation('name', $locale),
                    'count' => (int) ($counts[$state->id] ?? 0),
                ])
                ->filter(fn (array $state): bool => $state['count'] > 0)
                ->sortByDesc('count')
                ->take(10)
                ->values()
                ->all();
        });
    }

    public function brandLogoUrl(): ?string
    {
        $path = $this->gs->logo_wide_dark ?: $this->gs->logo_wide_light;

        return $path ? Storage::disk('s3')->url($path) : null;
    }

    public function brandAbout(): ?string
    {
        $about = trim((string) $this->gs->getTranslation('footer_about', app()->getLocale()));

        return $about !== '' ? $about : null;
    }

    /**
     * Only the social links an admin has actually filled in.
     *
     * @return array<int, array{key: string, icon: string, url: string, label: string}>
     */
    public function socialLinks(): array
    {
        return collect([
            'facebook' => ['icon' => 'bi-facebook', 'url' => $this->gs->social_facebook],
            'instagram' => ['icon' => 'bi-instagram', 'url' => $this->gs->social_instagram],
            'twitter' => ['icon' => 'bi-twitter-x', 'url' => $this->gs->social_twitter],
            'linkedin' => ['icon' => 'bi-linkedin', 'url' => $this->gs->social_linkedin],
            'telegram' => ['icon' => 'bi-telegram', 'url' => $this->gs->social_telegram],
            'whatsapp' => ['icon' => 'bi-whatsapp', 'url' => $this->gs->social_whatsapp],
        ])
            ->filter(fn (array $social): bool => filled($social['url']))
            ->map(fn (array $social, string $key): array => [
                'key' => $key,
                'icon' => $social['icon'],
                'url' => $social['url'],
                'label' => __("footer.social.{$key}"),
            ])
            ->values()
            ->all();
    }

    /**
     * Trust badges rendered as a fixed white plate. Only Enamad exists
     * today; the array shape is what a future badge would slot into. The
     * raw setting is sanitised here — the only place its HTML is trusted
     * enough to be echoed unescaped — via {@see TrustBadgeSanitizer}.
     *
     * @return array<int, array{key: string, html: string}>
     */
    public function trustBadges(): array
    {
        $enamad = trim((string) $this->gs->enamad_html);

        if ($enamad === '') {
            return [];
        }

        $sanitized = TrustBadgeSanitizer::sanitize($enamad);

        return $sanitized !== '' ? [['key' => 'enamad', 'html' => $sanitized]] : [];
    }

    /**
     * @return array{address: ?string, phone: ?string, email: ?string}
     */
    public function contact(): array
    {
        return [
            'address' => filled($this->gs->contact_address) ? $this->gs->contact_address : null,
            'phone' => filled($this->gs->contact_phone) ? $this->gs->contact_phone : null,
            'email' => filled($this->gs->contact_email) ? $this->gs->contact_email : null,
        ];
    }

    /**
     * Compared against app()->getLocale() rather than
     * LaravelLocalization::getCurrentLocale(): the latter caches its own
     * "currentLocale" property the first time it's resolved (normally by
     * the localized route group's middleware) and does not pick up a later
     * app()->setLocale() call, which breaks under Livewire::test() where
     * that middleware never runs.
     *
     * @return array<int, array{code: string, native: string, url: string, active: bool}>
     */
    public function locales(): array
    {
        $currentLocale = app()->getLocale();

        return collect(config('laravellocalization.supportedLocales'))
            ->map(fn (array $meta, string $code): array => [
                'code' => $code,
                'native' => $meta['native'],
                'url' => LocaleSwitchUrl::for($code),
                'active' => $code === $currentLocale,
            ])
            ->values()
            ->all();
    }

    public function currentYear(): int
    {
        return (int) now()->year;
    }

};
?>

@php
    $categories = $this->topCategories();
    $states = $this->topStates();
    $social = $this->socialLinks();
    $contact = $this->contact();
    $trustBadges = $this->trustBadges();
    $locales = $this->locales();
    $logoUrl = $this->brandLogoUrl();
    $about = $this->brandAbout();
@endphp

<footer class="vv-footer">
    <div class="container-xxl">
        <div class="vv-footer-grid">
            {{-- COLUMN 1 — Brand --}}
            <div>
                <img
                    src="{{ $logoUrl ?? asset('theme/1/media/logos/ViraVach-logo-2.png') }}"
                    alt="{{ __('globals.viravach') }}"
                    class="vv-footer-brand-logo"
                    loading="lazy"
                >

                @if ($about)
                    <p class="vv-footer-about">{{ $about }}</p>
                @endif

                @if ($social !== [])
                    <div class="vv-footer-social">
                        @foreach ($social as $item)
                            <a
                                href="{{ $item['url'] }}"
                                class="vv-footer-social-btn"
                                target="_blank"
                                rel="noopener"
                                aria-label="{{ $item['label'] }}"
                            >
                                <i class="bi {{ $item['icon'] }}"></i>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- COLUMN 2 — Top categories --}}
            @if ($categories !== [])
                <div>
                    <h3 class="vv-footer-heading">{{ __('footer.categories_heading') }}</h3>
                    <ul class="vv-footer-links">
                        @foreach ($categories as $category)
                            <li>
                                <a href="{{ route('companies.category', ['slug' => $category['slug']]) }}">
                                    {{ $category['label'] }}
                                    <span class="vv-footer-link-count">({{ number_format($category['count']) }})</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- COLUMN 3 — Provinces --}}
            @if ($states !== [])
                <div>
                    <h3 class="vv-footer-heading">{{ __('footer.states_heading') }}</h3>
                    <ul class="vv-footer-links">
                        @foreach ($states as $state)
                            <li>
                                <a href="{{ route('companies.state', ['slug' => $state['slug']]) }}">
                                    {{ $state['label'] }}
                                    <span class="vv-footer-link-count">({{ number_format($state['count']) }})</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- COLUMN 4 — Company & contact --}}
            <div>
                <h3 class="vv-footer-heading">{{ __('footer.company_heading') }}</h3>
                <ul class="vv-footer-links">
                    <li><a href="{{ route('pricing') }}">{{ __('footer.links.pricing') }}</a></li>
                    <li><a href="{{ route('terms-and-conditions') }}">{{ __('footer.links.terms') }}</a></li>
                    <li><a href="{{ route('auth.sign-up') }}">{{ __('footer.links.register_business') }}</a></li>
                </ul>

                @if ($contact['address'] || $contact['phone'] || $contact['email'])
                    <div class="vv-footer-contact">
                        @if ($contact['address'])
                            <div class="vv-footer-contact-item">
                                <i class="ki-duotone ki-geolocation fs-6"><span class="path1"></span><span class="path2"></span></i>
                                <span>{{ $contact['address'] }}</span>
                            </div>
                        @endif
                        @if ($contact['phone'])
                            <div class="vv-footer-contact-item">
                                <i class="ki-duotone ki-phone fs-6"><span class="path1"></span><span class="path2"></span></i>
                                <span dir="ltr">{{ $contact['phone'] }}</span>
                            </div>
                        @endif
                        @if ($contact['email'])
                            <div class="vv-footer-contact-item">
                                <i class="ki-duotone ki-sms fs-6"><span class="path1"></span><span class="path2"></span></i>
                                <span dir="ltr">{{ $contact['email'] }}</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        @if ($trustBadges !== [])
            <div class="vv-footer-divider"></div>
            <div class="vv-footer-trust">
                @foreach ($trustBadges as $badge)
                    <div class="vv-footer-trust-plate">
                        {{-- Escape hatch: $badge['html'] has already been run
                             through TrustBadgeSanitizer::sanitize() in
                             trustBadges() above, which allow-lists only
                             <a>/<img> and a handful of attributes and drops
                             everything else (scripts included). Never echo
                             raw settings HTML anywhere else this way. --}}
                        {!! $badge['html'] !!}
                    </div>
                @endforeach
            </div>
        @endif

        <div class="vv-footer-divider-bottom"></div>

        <nav class="vv-footer-locales" aria-label="{{ __('footer.language_switcher_label') }}">
            @foreach ($locales as $locale)
                <a
                    href="{{ $locale['url'] }}"
                    class="vv-footer-locale {{ $locale['active'] ? 'is-active' : '' }}"
                    @if ($locale['active']) aria-current="true" @endif
                >
                    {{ $locale['native'] }}
                </a>
            @endforeach
        </nav>

        <div class="vv-footer-bottom">
            <span>{{ __('footer.copyright', ['year' => $this->currentYear()]) }}</span>
            <a href="https://hktp.ir" target="_blank" rel="noopener">{{ __('footer.credit') }}</a>
        </div>
    </div>
</footer>

