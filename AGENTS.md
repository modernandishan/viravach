# Viravach (viravach.com) — project-specific facts

Repo-specific context an agent would otherwise miss. The Laravel Boost guidelines in the system prompt are generic; this section is what actually differs from a stock Laravel app.

## Environment: everything runs in Docker

There is **no `php` binary on the host**. All PHP/artisan/composer/pint commands must run inside the `viravach_app` container:

```bash
docker exec viravach_app php artisan test --compact
docker exec viravach_app vendor/bin/pint --dirty --format agent
docker exec viravach_app composer install
```

- App code lives on the host at `/var/www/viravach`, bind-mounted into containers at `/var/www/html`.
- Compose file is **outside this repo**: `/HHD/docker/viravach/docker-compose.yml`. Containers: `viravach_app` (php-fpm), `viravach_queue` (`queue:work`), `viravach_nginx` — all on the external `webproxy` network.
- Postgres, Redis, and MinIO (S3 file storage) are **shared standalone containers** (`postgres`, `redis`, `minio`) on the same network — there is no viravach-specific DB/cache container. Production DB: `viravach_laravel`, user `viravach_user`.
- Frontend assets (Tailwind v4 via Vite) build on the **host**: `npm run build`. If a frontend change isn't reflected, the user needs to run `npm run build` / `npm run dev`.
- Logs: `docker logs viravach_app --tail 50`, `docker logs viravach_nginx --tail 50`.

## Host-split routing (do NOT look for `routes/web.php`)

There is **no `routes/web.php`**. Routes are split per hostname in `bootstrap/app.php` (`Route::domain(...)`), with the host map in `config/domains.php`:

- `routes/public.php` → `viravach.com` — SEO-critical, multilingual, indexable. The **only** locale-prefixed routes.
- `routes/app.php` → `app.viravach.com` — authenticated dashboard, noindex, no locale prefix (locale resolved from session via `SetLocaleFromSession`).
- `routes/web123.php` → **legacy/dead**, not loaded anywhere. Ignore it.
- Admin (`admin.viravach.com`) and `api` hosts are configured but the Filament panel mounts at `/admin` via `app/Providers/Filament/AdminPanelProvider.php`.

## Two frontends

1. **Public site** — Livewire 4 full-page single-file components in `resources/views/pages/` (filenames prefixed `⚡`, namespace `pages::`), registered via `Route::livewire('/uri', 'pages::name')`. UI based on Metronic theme assets in `public/theme/1`.
2. **Filament v5 admin** — at `/admin`, Persian-only, roles via `bezhansalleh/filament-shield`.

## Localization (mcamara/laravel-localization)

- Locales: `en` (default, **no URL prefix** — `hideDefaultLocaleInURL = true`), `fa`, `ar`, `ru`, `tr`.
- `GET /lang/{locale}` (route `lang.switch`) lives **outside** the localized group: it writes `session(['locale' => ...])` before redirecting so `LocaleSessionRedirect` doesn't bounce the user back to the previous locale. It also excludes `DetectLocaleFromIp`.
- Inside the localized group, `config('app.locale')` reflects the *current request's* locale, not the site default — use `LaravelLocalization::getDefaultLocale()` for the true default.
- Model content is translated with `spatie/laravel-translatable` (JSON columns); site-wide settings use `spatie/laravel-settings` with per-locale JSON payloads (`app/Settings/GeneralSettings.php`).
- Translation strings live in `lang/{en,fa,ar,ru,tr}/` — keep all five in sync when adding keys.

## Company draft/snapshot publication model

The public site never renders `Company` records directly. Users edit a `Company` (the **draft**); admins review it in Filament (`CompanyReviewStatus`). Approving calls `CompanyPublicationService::publish()`, which upserts a `CompanyPublication` **snapshot** (translatable fields, addresses, SEO, fresh media copies) in a transaction. Public pages query `CompanyPublication` only, so draft edits stay invisible until re-approved. Rejection only flags the draft — the published snapshot stays live.

## AI content pipeline (shipped, Aug 2026)

`companies.description` (translatable Tiptap HTML) was **repurposed away**: it's replaced by `companies.brief` + `brief_locale` — the user's own single-language plain-text brief. The old HTML was backfilled into `brief` by migration `2026_08_29_000001`; `description` is now dropped from **both** `companies` and `company_publications` (`2026_08_31_000001`). Both tables instead carry a `content` jsonb column holding the AI-rendered payload (per-locale map).

Pipeline state lives in `CompanyContent` (one row per company: `CompanyContentStatus` draft/queued/generating/ready/failed, `ai_payload`, `input_hash`, `step`, `failure_reason`, `locked_at`). Generation runs as queued jobs in `app/Jobs/Ai/` (`GenerateSourceContent`, `LocalizeContent`/`LocalizeContentLocale`, `ReserveKeyword`, `GenerateSeoBlock`, `GenerateFeaturedImage`, `FinalizeContent` — all extending `AbstractAiContentJob`), with structured-output schemas in `app/Ai/Schemas/` (`CompanyContentSchema`, `CompanySeoSchema`), prompts in `app/Ai/Prompts/`, and tunables in `app/Settings/ContentSettings.php` (Filament page `ManageContentSettings`). `SeoKeywordReservation` enforces that a (locale, keyword) SEO pair is claimed by only one owner via a `seoable` morph.

Rendering: the public company page draws the payload via `resources/views/components/company-content/*` (hero/about/offerings/markets/strengths/faq/specs/cta) plus FAQPage & specs JSON-LD; `CompanyPublication::contentFor($locale)` picks the locale with fallback. Entry points: the dashboard AI card (quota = plan feature `ai-content-generations`, 6h rate limiter, 409-style lock while generating) and Filament's content editor (`CompanyContentSection`) + republish/generate actions.

## Subscriptions

`CompanySubscriptionService` is the **only** place allowed to create/modify plan subscriptions (`laravelcm/laravel-subscriptions`): `switchToPlan()`, `assignFreePlanIfMissing()`, `startProPlusTrial()` (14-day), `revertExpiredTrials()`. Payments via `shetabit/payment`, callback at `dashboard/payment/callback`.

## ViraBot AI chat

`config/viravach_chat.php` holds the per-locale system prompts for **ViraBot**, the site AI assistant: `laravel/ai` calling a self-hosted OpenWebUI instance (`ai_provider => 'openwebui'`, model `virabot`). Each prompt leads with an explicit "always reply in <language>" rule (relying on the model to match message language proved unreliable) and ends in a `{context}` placeholder filled with page context; the prompt is chosen from the locale passed explicitly into `GenerateAiChatReply`, never guessed from message content. Replies are queued via `app/Jobs/GenerateAiChatReply.php` around `app/Ai/Agents/ViraBotAgent.php`; the conversation plumbing (AI service, support-transfer to human admins, company context builder, translation) lives in `app/Services/Chat/`. Persistence is `musonza/chat`; realtime delivery via `laravel/reverb` + `laravel-echo`/`pusher-js` (`.env.example` ships `BROADCAST_CONNECTION=log`). User-facing routes on app host: `/chat` and `/support-chats`; admin tunables in `app/Settings/ChatSettings.php`.

## Tickets & support chats

Support tickets are built on `musonza/chat`: a ticket is a `chat_conversations` row with `data.type = ticket` (NOT `makeDirect` — no pair-uniqueness, and staff visibility comes from the `Ticket` row, not conversation participation). Ticket refs are `TICKET-YYYYMMDD-NNNN`, generated with a `UniqueConstraintViolationException` catch + bounded retry (the conversation is created outside the retry loop). A customer reply auto-reopens a closed ticket. Staff queue is shared by role (`support`, `admin`, `super_admin`). Dashboard routes on the app host: tickets and support-chats.

## RFQ quote requests (in progress, uncommitted)

Anonymous buyers submit a quote request to a company from the published company page (`⚡rfq-form` Volt component mounted in `⚡company.blade.php`); the company owner reads and replies in a dashboard inbox (route `rfqs` → `pages::dashboard.rfqs`). Replies are stored only — deliberately NOT delivered to the buyer yet (future phase; buyers have no account). Built separately from musonza/chat because the buyer is not a `User`.

- `Rfq` has **no `user_id`** (buyers are anonymous by design; `ip_address` kept for rate-limiter audit); `RfqResponse.user_id` is nullable so replies survive staff deletion. `RfqStatus` = `Pending/Responded/Expired/Closed` — `Expired` is reserved, nothing writes it yet; replying auto-flips pending→responded.
- Plan gate is the boolean plan feature `rfq-system` via `PlanFeature::value()` (a numeric `rfq-monthly-limit` sits commented out in PlanSeeder for later).
- Anti-abuse: per-IP 10/hour + per-company 30/hour `RateLimiter` windows in `submit()`, counters only hit after validation + Turnstile pass.
- **Turnstile** (Cloudflare captcha): `app/Services/Turnstile/` — `TurnstileVerifier` fails closed (blank token / non-2xx / unreachable Cloudflare), `TurnstileVerdict` is the DTO. Used only by ⚡rfq-form. The widget-in-Volt embedding pattern (wire:ignore container, `$wire.set` token callback, `turnstile.reset()` after every failure) is documented in `docs/RESEARCH.md` — read it before touching the form. Config lives in `config/services.php`; `.env.example` ships Cloudflare's always-pass dummy keys (replace before deploy).
- Owner notification: `NewRfqReceived` queued on a dedicated `rfq-notifications` queue, channels = mail + custom `IPPanelChannel` pattern SMS (`config/ippanel.php` `rfq` group; `User::routeNotificationForIppanel()` returns the phone, null = mail-only). The mailable itself is deliberately NOT ShouldQueue — queueing is the notification's job.
- Tests: `tests/Feature/Rfq/RfqFormTest.php`, `RfqInboxTest.php`, `tests/Feature/Notifications/NewRfqReceivedTest.php`, shared concern `tests/Concerns/AssertsFlashMessages.php` (decodes the SweetAlert2 flash payload). Migrations ARE applied to Postgres.

## WordPress auto-content (Virawp, in progress, uncommitted)

Monthly AI-generated articles published to the company's **own** WordPress site. Per-article state lives in `WordPressContentPost` (`WordPressPostStatus` queued/generating/published/failed). Services in `app/Services/WordPress/` follow a result/failure-reason object pattern (`WordPressConnectionService`, `WordPressContentGenerationService`, `WordPressPublishingService`); jobs in `app/Jobs/WordPress/` extend `AbstractWordPressPostJob`; trend discovery in `app/Services/Trends/` (`GoogleTrendsService`).

Connection settings live on `companies` (URL/credentials, `WordPressConnectionStatus`, `SeoPlugin` enum `yoast`/`rank_math` — it decides which SEO meta fields the publisher writes, plus a `wp_seo_meta_writable` flag). Quota is `WordPressContentQuota` (plan feature `virawp-monthly-contents`): a plain count of this calendar month's rows — deliberately NOT the package's `recordFeatureUsage()` (rolling window anchored to subscription creation). Limits are read live via `PlanFeature::value()`; feature slugs are stored plan-slug-prefixed (`{plan-slug}-{key}`, globally unique index), which `PlanFeature` is the single place that knows. Entry point: dashboard `⚡wordpress-content` page; tests in `tests/Feature/Dashboard/WordPressConnectionTest.php` and `WordPressContentPageTest.php`. Migrations are applied to Postgres.

## Testing gotchas

- **PHPUnit only — Pest is not installed.** All tests are PHPUnit classes extending `Tests\TestCase`. If asked for "Pest tests", write PHPUnit.
- Tests run on **in-memory sqlite** (`phpunit.xml`); production is **Postgres**. Postgres-specific failures (e.g. `DISTINCT` over translatable `json` columns in Filament `->relationship()` multi-selects) won't be caught by the suite — verify such queries against Postgres manually.
- `phpunit.xml` raises `memory_limit` to 512M because 5-locale translatable Filament forms exceed the default when several schemas build in one process.
- Reach pages in feature tests via `route()` (gives the correct host + locale prefix), not raw paths. `tests/TestCase.php` also offers `publicUrl()` / `appUrl()` / `adminUrl()` helpers.

## Homepage depends on a `pages` row with `slug = '/'`

`pages::home` does `Page::where('slug', '/')->firstOrFail()` in `mount()`. `HomePageSeeder` (registered in `DatabaseSeeder`) creates that row, so a freshly seeded DB is fine — but any database missing it (e.g. seeded before the seeder existed) makes the homepage 404. It's a real 404, not a routing failure; other pages like `/fa/sign-in` work fine.

## SEO: exactly one `<h1>` per public page

The shared ⚡heading1 block (`resources/views/components/header-elements/`) renders `<h1>` everywhere **except** the routes listed in its `SELF_HEADING_ROUTES` constant, where it demotes to a lower heading so the page's own hero can be the single h1. When adding a public page with its own hero heading, add its route there instead of hardcoding another `<h1>`.

## Skills

Workspace skills live in `.agents/skills/` (mirrored in `.claude/skills/` and partially in `.zcode/skills/`). Activate the relevant one:
- `viravach-project` — infra topology, localization routing, Livewire page structure, known gaps (only in `.claude/skills/`)
- `medialibrary-development` — required for any media/`HasMedia` work
- `translatable-development` — for `spatie/laravel-translatable` work
- `livewire-development` — for Livewire 4 components
- `echo-development` — broadcasting/Reverb/Echo (chat realtime)
- `ai-sdk-development` — `laravel/ai` (ViraBot)
- `laravel-best-practices`, `tailwindcss-development` — general conventions

Filament v5 guidance comes from Boost's `filament/filament` package guidelines, not a workspace skill. `docs/viravach-media-queue-setup.md` documents the media-conversion queue setup.

## Commands quick reference

```bash
# Testing
docker exec viravach_app php artisan test --compact
docker exec viravach_app php artisan test --compact tests/Feature/CompanyPageTest.php
docker exec viravach_app php artisan test --compact --filter=testName

# Formatting (required after modifying any PHP file)
docker exec viravach_app vendor/bin/pint --dirty --format agent

# Frontend assets
npm run build
npm run dev

# Database
docker exec viravach_app php artisan migrate
docker exec viravach_app php artisan migrate:fresh --seed
```

## Verification

After any PHP change: run `vendor/bin/pint --dirty --format agent` inside the container, then run the affected tests with `php artisan test --compact --filter=...`.
