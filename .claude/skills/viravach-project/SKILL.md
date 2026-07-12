---
name: viravach-project
description: "Project-specific knowledge about the viravach.com Laravel app: Docker infrastructure, database/service topology, localization routing, Livewire page structure, and known gaps. Use whenever debugging deployment/infra issues (404s, container problems, DB connectivity), working on the public-facing localized pages, or needing to know how this specific app is wired together (as opposed to generic Laravel/Filament/Livewire conventions covered by the other skills)."
metadata:
  type: project-knowledge
---

# Viravach project knowledge

## Infrastructure

- App code lives on the host at `/var/www/viravach`, bind-mounted into containers at `/var/www/html`.
- Compose file: `/HHD/docker/viravach/docker-compose.yml` (not inside the app repo).
- Containers defined there: `viravach_app` (php-fpm), `viravach_queue` (`php artisan queue:work`), `viravach_nginx`.
  All three share the **external** `webproxy` Docker network.
- Database (Postgres) and Redis are **not** defined in this compose file — they're standalone shared
  containers named `postgres` and `redis` also attached to `webproxy`. DB name: `viravach_laravel`,
  user `viravach_user`. Don't look for a `viravach`-specific Postgres/Redis container; there isn't one.
- File storage uses a shared `minio` container (S3-compatible), not part of this compose file either.
- Public traffic path: `nginx-proxy-manager` (host ports 80/443) → `viravach_nginx` → `viravach_app` (php-fpm).
- Useful commands: `docker logs viravach_nginx --tail 50`, `docker logs viravach_app --tail 50`,
  `docker exec viravach_app php artisan ...`.

## Localization routing

- Uses `mcamara/laravel-localization`. In `routes/web.php`, the public route group is prefixed with
  `LaravelLocalization::setLocale()`, which is re-evaluated **per request** (not cached at boot), so the
  prefix (`fa`, `en`, or empty for a hidden default locale) matches whatever locale the current request
  resolves to.
- `/lang/{locale}` is an explicit, non-prefixed route that stores the chosen locale in session before
  redirecting — it exists so the language switcher doesn't get bounced back by `LocaleSessionRedirect`.
- Middleware on the group: `localeSessionRedirect`, `localizationRedirect`, `localeViewPath`.

## Livewire 4 page structure

- Full-page Livewire components live under `resources/views/pages/` (Livewire config: `'pages' =>
  resource_path('views/pages')`, namespace `pages::`).
- Single-file components use a `⚡` prefix in the filename, e.g. `resources/views/pages/⚡home.blade.php`.
- Registered via `Route::livewire('/uri', 'pages::component-name')->name(...)` in `routes/web.php`,
  inside the locale-prefixed group.

## Known gap: empty `pages` table / homepage 404

- `resources/views/pages/⚡home.blade.php` does `Page::where('slug', '/')->firstOrFail()` in `mount()`.
  If no `App\Models\Page` row with `slug = '/'` exists, the homepage 404s (this is a real 404 response,
  not a routing failure — other pages like `/fa/sign-in` and `/admin/login` work fine).
- As of 2026-07-12, the `pages` table has **no seeder** (`database/seeders/DatabaseSeeder.php` only calls
  `RoleSeeder`, `SuperAdminSeeder`, `CountrySeeder`, `StateSeeder`, `CompanyCategorySeeder`) and had 0 rows.
  This is a pre-existing content gap, not something caused by restarting Docker/the server — other tables
  (`users`, `general_settings`, `company_categories`, `countries`, `states`, `permissions`, `roles`) were
  intact after a restart, so data isn't being lost on restart.
- Fix (not yet applied — user deferred this): create a `Page` record with `slug = '/'` either via the
  Filament admin at `/admin/pages`, or by adding a `PageSeeder` and registering it in `DatabaseSeeder`.
  `Page` fields: `slug` (unique string), `title` (translatable JSON), `is_active` (bool, default true),
  `published_at` (nullable timestamp), `sort_order` (uint, default 0).

## Admin panel

- Filament admin panel is mounted at `/admin` (e.g. `/admin/login`, `/admin/pages`,
  `/admin/company-categories`, `/admin/manage-general-settings`).
