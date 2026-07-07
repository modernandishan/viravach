<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About This Project (Viravach)

This section documents the project-specific setup that has been added on top of a stock Laravel + Filament installation. It is kept up to date as changes are made so future contributors (human or AI) have a single source of truth.

### Stack Overview

- **Admin panel:** `filament/filament` (v5) at `/admin`, provider: `app/Providers/Filament/AdminPanelProvider.php`.
- **Roles & permissions:** `bezhansalleh/filament-shield`.
- **Multi-language content:** `spatie/laravel-translatable` (for Eloquent model attributes) and `filament/spatie-laravel-settings-plugin` + `spatie/laravel-settings` (for app-wide settings).
- **Localized routing:** `mcamara/laravel-localization`.
- **Frontend:** Livewire 4 single-file components (`.blade.php` files prefixed with `⚡`, resolved automatically by Livewire's finder — see `config/livewire.php`'s `component_locations`). UI is based on the Metronic admin theme assets in `public/theme/1`.

### Localization / Multi-language (`mcamara/laravel-localization`)

- Supported locales are defined in `config/laravellocalization.php` → `supportedLocales`. Currently enabled: `en` (site default), `fa`, `ar`, `ru`, `tr`.
- `hideDefaultLocaleInURL` is set to `true`, so the default locale (`en`) has **no URL prefix**, while the others are prefixed:

  | Locale | Example URL |
  |---|---|
  | English (default) | `/`, `/sign-in` |
  | Persian | `/fa`, `/fa/sign-in` |
  | Arabic | `/ar`, `/ar/sign-in` |
  | Russian | `/ru`, `/ru/sign-in` |
  | Turkish | `/tr`, `/tr/sign-in` |

- All public-facing routes in `routes/web.php` are wrapped in the official `LaravelLocalization::setLocale()` route group with the `localeSessionRedirect`, `localizationRedirect`, and `localeViewPath` middleware (aliases registered in `bootstrap/app.php`).
- The Filament admin panel (`/admin`) is registered outside of this group (via the panel provider, not `routes/web.php`), so it is **not** locale-prefixed.
- App locale/fallback locale come from `.env`: `APP_LOCALE` (default `en`) and `APP_FALLBACK_LOCALE` (default `fa`).
- A language switcher is available in the admin theme's header toolbar — see "Toolbar language switcher" below.

### Site-wide settings (Filament + Spatie Settings)

General site settings (e.g. site name) are managed through a dedicated Filament **settings page**, not a resource — a `Resource` is meant for CRUD over many records, while settings are a single, fixed record per group, which is exactly what `spatie/laravel-settings` models.

- **Settings class:** `app/Settings/GeneralSettings.php`
  - Group: `general`.
  - `public array $site_name` — stored as a **JSON payload** in the database so it can hold one value per locale, e.g. `{"en": "Viravach", "fa": "...", "ar": "...", "ru": "...", "tr": "..."}`.
  - Helper method `getSiteName(?string $locale = null)` returns the value for a given locale (defaults to the current app locale), falling back to `app.fallback_locale`, then to any available translation — mirroring how `spatie/laravel-translatable` resolves translations on Eloquent models.
- **Filament page:** `app/Filament/Pages/ManageGeneralSettings.php` — extends `Filament\Pages\SettingsPage` from `filament/spatie-laravel-settings-plugin`. The form renders one `Tabs` component with a tab per active locale (default locale first, then `fa`, `ar`, `ru`, `tr`), each containing a `TextInput` bound to `site_name.{locale}`. It is auto-discovered by the panel (`discoverPages` in `AdminPanelProvider`), no manual registration needed.
- **Package config:** `config/settings.php` (published from `spatie/laravel-settings`, with `GeneralSettings::class` registered explicitly in the `settings` array in addition to auto-discovery).
- **Migrations:**
  - `database/migrations/2026_07_06_100000_create_settings_table.php` — creates the base `settings` table (`group`, `name`, `payload` JSON, `locked`).
  - `database/settings/2026_07_06_100100_create_general_settings.php` — a *settings migration* (run automatically alongside normal migrations because its directory is registered in `config/settings.php` → `migrations_paths`) that seeds the initial `general.site_name` value for all 5 locales.
- To apply these changes on a fresh environment, run:

  ```bash
  php artisan migrate
  ```

- When overriding a static property already declared on a Filament base class (e.g. `$navigationGroup`), make sure the type hint is **exactly** the same as the parent (PHP requires invariant property types). For example, `Filament\Pages\Page::$navigationGroup` is typed `string|UnitEnum|null`, not `?string`.

### Toolbar language switcher

- New Livewire single-file component: `resources/views/components/header-elements/tools/⚡language-switcher.blade.php`.
- Registered in the header toolbar: `resources/views/components/header-elements/⚡toolbar.blade.php` via `<livewire:header-elements.tools.language-switcher />`.
- Renders a flag icon button (current locale) that opens a dropdown listing every supported locale (native name + flag), highlighting the active one. Each link is generated with `LaravelLocalization::getLocalizedURL($localeCode)` so switching language preserves the current page/route.
- Flags are served from `public/theme/1/media/flags/*.svg`. The locale → flag filename mapping lives in the `$flags` property of the component and currently maps: `en` → `united-states`, `fa` → `iran`, `ar` → `saudi-arabia`, `ru` → `russia`, `tr` → `turkey`. Update this array if a locale is added/removed or a different flag is preferred.

> **Gotcha #1:** `config('app.locale')` is mutated at runtime by `LaravelLocalization::setLocale()` to reflect the *current* request's locale (not the site's actual default). Any code (inside the localized route group) that needs the true default locale must call `LaravelLocalization::getDefaultLocale()` instead — which is captured once when the package boots, before the current URL's locale is applied. The language switcher initially had a bug caused by using `config('app.locale')` for this, which made the default locale (`en`) disappear from the dropdown whenever browsing a non-default locale (e.g. `/fa`).

> **Gotcha #2:** the `localeSessionRedirect` middleware remembers the active locale in the session (`session('locale')`) and silently redirects any URL that has **no** locale segment back to that remembered locale. Since `en` has no prefix (`hideDefaultLocaleInURL = true`), clicking "English" while the session still remembers e.g. `fa` used to redirect straight back to `/fa` — the URL never changed and the language appeared "stuck". The fix is a dedicated switch endpoint, `GET /lang/{locale}` (route name `lang.switch`, defined in `routes/web.php`, intentionally **outside** the localized route group so it isn't itself locale-prefixed), which updates `session(['locale' => $locale])` *before* redirecting to `LaravelLocalization::getLocalizedURL($locale, url()->previous())`. The toolbar language switcher links to `route('lang.switch', $localeCode)` instead of building the localized URL directly.

### Known environment limitation

The sandbox used for some of this work did not have a `php` binary available, so commands like `composer install`, `php artisan migrate`, and `php artisan route:list` could not be executed to verify changes end-to-end. All PHP/Blade files were written by hand to match the installed package versions (checked directly against the sources in `vendor/`). **Please run `composer install` and `php artisan migrate` locally/on your server and verify the admin panel and locale-prefixed routes before deploying.**

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
