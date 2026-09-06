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

## WordPress auto-content (Virawp, in progress, uncommitted)

Monthly AI-generated articles published to the company's **own** WordPress site. Per-article state lives in `WordPressContentPost` (`WordPressPostStatus` queued/generating/published/failed). Services in `app/Services/WordPress/` follow a result/failure-reason object pattern (`WordPressConnectionService`, `WordPressContentGenerationService`, `WordPressPublishingService`); jobs in `app/Jobs/WordPress/` extend `AbstractWordPressPostJob`; trend discovery in `app/Services/Trends/` (`GoogleTrendsService`).

Connection settings live on `companies` (URL/credentials, `WordPressConnectionStatus`, `SeoPlugin` enum `yoast`/`rank_math` — it decides which SEO meta fields the publisher writes, plus a `wp_seo_meta_writable` flag). Quota is `WordPressContentQuota` (plan feature `virawp-monthly-contents`): a plain count of this calendar month's rows — deliberately NOT the package's `recordFeatureUsage()` (rolling window anchored to subscription creation). Limits are read live via `PlanFeature::value()`; feature slugs are stored plan-slug-prefixed (`{plan-slug}-{key}`, globally unique index), which `PlanFeature` is the single place that knows. Entry point: dashboard `⚡wordpress-content` page; tests in `tests/Feature/Dashboard/WordPressConnectionTest.php` and `WordPressContentPageTest.php`. Migrations exist but are NOT yet applied to Postgres (user runs migrate).

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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- filament/filament (FILAMENT) - v5
- laravel/ai (AI) - v0
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/reverb (REVERB) - v1
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- phpunit/phpunit (PHPUNIT) - v12
- laravel-echo (ECHO) - v2
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== filament/filament rules ===

## Filament

- Filament is a Laravel UI framework built on Livewire, Alpine.js, and Tailwind CSS. UIs are defined in PHP via fluent, chainable components. Follow existing conventions in this app.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices. If `search-docs` is unavailable, refer to https://filamentphp.com/docs.

### Artisan

- Always use Filament-specific Artisan commands to create files. Find available commands with the `list-artisan-commands` tool, or run `php artisan --help`.
- Inspect required options before running, and always pass `--no-interaction`.

### Patterns

Always use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field visibility" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
    ->options(CompanyType::class)
    ->required()
    ->live(),

TextInput::make('company_name')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === 'business'),

</code-snippet>

Use `Set $set` inside `->afterStateUpdated()` on a `->live()` field to mutate another field reactively. Prefer `->live(onBlur: true)` on text inputs to avoid per-keystroke updates:

<code-snippet name="Reactive field update" lang="php">
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

TextInput::make('title')
    ->required()
    ->live(onBlur: true)
    ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
        'slug',
        Str::slug($state ?? ''),
    )),

TextInput::make('slug')
    ->required(),

</code-snippet>

Compose layout by nesting `Section` and `Grid`. Children need explicit `->columnSpan()` or `->columnSpanFull()`:

<code-snippet name="Section and Grid layout" lang="php">
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

Section::make('Details')
    ->schema([
        Grid::make(2)->schema([
            TextInput::make('first_name')
                ->columnSpan(1),
            TextInput::make('last_name')
                ->columnSpan(1),
            TextInput::make('bio')
                ->columnSpanFull(),
        ]),
    ]),

</code-snippet>

Use `Repeater` for inline `HasMany` management. `->relationship()` with no args binds to the relationship matching the field name:

<code-snippet name="Repeater for HasMany" lang="php">
use Filament\Forms\Components\Repeater;

Repeater::make('qualifications')
    ->relationship()
    ->schema([
        TextInput::make('institution')
            ->required(),
        TextInput::make('qualification')
            ->required(),
    ])
    ->columns(2),

</code-snippet>

Use `state()` with a `Closure` to compute derived column values:

<code-snippet name="Computed table column value" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
    ->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),

</code-snippet>

Use `SelectFilter` for enum or relationship filters, and `Filter` with a `->query()` closure for custom logic:

<code-snippet name="Table filters" lang="php">
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

SelectFilter::make('status')
    ->options(UserStatus::class),

SelectFilter::make('author')
    ->relationship('author', 'name'),

Filter::make('verified')
    ->query(fn (Builder $query) => $query->whereNotNull('email_verified_at')),

</code-snippet>

Actions are buttons that encapsulate optional modal forms and behavior:

<code-snippet name="Action with modal form" lang="php">
use Filament\Actions\Action;

Action::make('updateEmail')
    ->schema([
        TextInput::make('email')
            ->email()
            ->required(),
    ])
    ->action(fn (array $data, User $record) => $record->update($data)),

</code-snippet>

### Testing

Testing setup (requires `pestphp/pest-plugin-livewire` in `composer.json`):

- Always call `$this->actingAs(User::factory()->create())` before testing panel functionality.
- For edit pages, pass `['record' => $user->id]`, use `->call('save')` (not `->call('create')`), and do not assert `->assertRedirect()` (edit pages do not redirect after save).

<code-snippet name="Table test" lang="php">
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name)
    ->assertCanSeeTableRecords($users->take(1))
    ->assertCanNotSeeTableRecords($users->skip(1));

</code-snippet>

<code-snippet name="Create resource test" lang="php">
use function Pest\Laravel\assertDatabaseHas;

livewire(CreateUser::class)
    ->fillForm([
        'name' => 'Test',
        'email' => 'test@example.com',
    ])
    ->call('create')
    ->assertNotified()
    ->assertHasNoFormErrors()
    ->assertRedirect();

assertDatabaseHas(User::class, [
    'name' => 'Test',
    'email' => 'test@example.com',
]);

</code-snippet>

<code-snippet name="Edit resource test" lang="php">
livewire(EditUser::class, ['record' => $user->id])
    ->fillForm(['name' => 'Updated'])
    ->call('save')
    ->assertNotified()
    ->assertHasNoFormErrors();

assertDatabaseHas(User::class, [
    'id' => $user->id,
    'name' => 'Updated',
]);

</code-snippet>

<code-snippet name="Testing validation" lang="php">
livewire(CreateUser::class)
    ->fillForm([
        'name' => null,
        'email' => 'invalid-email',
    ])
    ->call('create')
    ->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ])
    ->assertNotNotified();

</code-snippet>

Use `->callAction(DeleteAction::class)` for page actions, or `->callAction(TestAction::make('name')->table($record))` for table actions:

<code-snippet name="Calling actions" lang="php">
use Filament\Actions\Testing\TestAction;

livewire(ListUsers::class)
    ->callAction(TestAction::make('promote')->table($user), [
        'role' => 'admin',
    ])
    ->assertNotified();

</code-snippet>

### Correct Namespaces

- Form fields (`TextInput`, `Select`, `Repeater`, etc.): `Filament\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, etc.): `Filament\Infolists\Components\`
- Layout components (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.): `Filament\Schemas\Components\`
- Schema utilities (`Get`, `Set`, etc.): `Filament\Schemas\Components\Utilities\`
- Table columns (`TextColumn`, `IconColumn`, etc.): `Filament\Tables\Columns\`
- Table filters (`SelectFilter`, `Filter`, etc.): `Filament\Tables\Filters\`
- Actions (`DeleteAction`, `CreateAction`, etc.): `Filament\Actions\`. Never use `Filament\Tables\Actions\`, `Filament\Forms\Actions\`, or any other sub-namespace for actions.
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

### Common Mistakes

- **Never assume public file visibility.** File visibility is `private` by default. Always use `->visibility('public')` when public access is needed.
- **Never assume full-width layout.** `Grid`, `Section`, `Fieldset`, and `Repeater` do not span all columns by default.
- **Use `Select::make('author_id')->relationship('author', 'name')` for BelongsTo fields.** `BelongsToSelect` does not exist in v4.
- **`Repeater` uses `->schema()`, not `->fields()`.**
- **Never add `->dehydrated(false)` to fields that need to be saved.** It strips the value from form state before `->action()` or the save handler runs. Only use it for helper/UI-only fields.
- **Use correct property types when overriding `Page`, `Resource`, and `Widget` properties.** These properties have union types or changed modifiers that must be preserved:
  - `$navigationIcon`: `protected static string | BackedEnum | null` (not `?string`)
  - `$navigationGroup`: `protected static string | UnitEnum | null` (not `?string`)
  - `$view`: `protected string` (not `protected static string`) on `Page` and `Widget` classes

=== spatie/laravel-medialibrary rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

</laravel-boost-guidelines>
