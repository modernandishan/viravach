<?php

use Livewire\Component;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

new class extends Component {
    /**
     * Maps supported locale codes to a flag icon from `theme/1/media/flags`.
     *
     * @var array<string, string>
     */
    protected array $flags = [
        "en" => "united-states",
        "fa" => "iran",
        "ar" => "saudi-arabia",
        "ru" => "russia",
        "tr" => "turkey",
    ];

    /**
     * @return array<int, array{code: string, native: string, flag: string, url: string, active: bool}>
     */
    public function locales(): array
    {
        $supportedLocales = LaravelLocalization::getSupportedLocales();
        $currentLocale = LaravelLocalization::getCurrentLocale();

        // Note: `config('app.locale')` gets overwritten at runtime by
        // LaravelLocalization::setLocale() to the *current* locale, so it
        // can't be used to find the site's actual default locale here.
        return collect([
            LaravelLocalization::getDefaultLocale(),
            "fa",
            "ar",
            "ru",
            "tr",
        ])
            ->unique()
            ->filter(
                fn(string $localeCode): bool => array_key_exists(
                    $localeCode,
                    $supportedLocales,
                ),
            )
            ->map(
                fn(string $localeCode): array => [
                    "code" => $localeCode,
                    "native" => $supportedLocales[$localeCode]["native"],
                    "flag" => $this->flags[$localeCode] ?? "flag",
                    "url" => route("lang.switch", $localeCode),
                    "active" => $localeCode === $currentLocale,
                ],
            )
            ->values()
            ->all();
    }

    public function currentFlag(): string
    {
        return $this->flags[LaravelLocalization::getCurrentLocale()] ?? "flag";
    }

    public function currentLocaleNative(): string
    {
        return LaravelLocalization::getCurrentLocaleNative();
    }
};
?>

<div class="d-flex align-items-center ms-1 ms-lg-3">
    <!--begin::Menu toggle-->
    <a href="#" class="btn btn-icon btn-active-light-primary btn-custom w-30px h-30px w-md-40px h-md-40px" data-kt-menu-trigger="{default:'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'bottom-end' : 'bottom-start' }}" title="{{ $this->currentLocaleNative() }}">
        <img class="w-20px h-20px rounded-1" src="theme/1/media/flags/{{ $this->currentFlag() }}.svg" alt="{{ $this->currentLocaleNative() }}" />
    </a>
    <!--end::Menu toggle-->
    <!--begin::Menu-->
    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-200px" data-kt-menu="true">
        @foreach ($this->locales() as $locale)
            <!--begin::Menu item-->
            <div class="menu-item px-3">
                <a href="{{ $locale['url'] }}" class="menu-link d-flex px-5 @if ($locale['active']) active @endif">
                    <span class="symbol symbol-20px me-4">
                        <img class="rounded-1" src="theme/1/media/flags/{{ $locale['flag'] }}.svg" alt="{{ $locale['native'] }}" />
                    </span>
                    {{ $locale['native'] }}
                </a>
            </div>
            <!--end::Menu item-->
        @endforeach
    </div>
    <!--end::Menu-->
</div>
