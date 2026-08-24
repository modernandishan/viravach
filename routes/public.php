<?php

use App\Http\Middleware\DetectLocaleFromIp;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/*
|--------------------------------------------------------------------------
| Public Site Routes
|--------------------------------------------------------------------------
|
| Everything served from the apex host and meant to be crawled and indexed.
| These are the only routes that carry a locale prefix, because the prefix
| exists for SEO (one canonical URL per language) — not for the visitor's
| convenience. Authenticated surfaces deliberately live elsewhere.
|
| Registered against config('domains.public') in bootstrap/app.php.
|
*/

// Explicit locale-switch endpoint. This intentionally lives outside the
// localized route group below (it must not be locale-prefixed itself).
//
// It exists because `LocaleSessionRedirect` remembers the locale in the
// session and will redirect any URL without a locale segment back to that
// remembered locale. If we just linked directly to `LaravelLocalization::
// getLocalizedURL('en')` from the toolbar language switcher, clicking
// "English" while the session still remembers e.g. `fa` would immediately
// get redirected back to `/fa` by that middleware. Updating the session
// here, before redirecting, keeps it in sync with the user's explicit choice.
//
// `DetectLocaleFromIp` is excluded here because it's appended to the global
// `web` middleware group, so it would otherwise also run on this route. The
// `locale_selected_manually` cookie it checks for is only set by *this*
// route's own response below, so on the very first manual switch (before
// that cookie exists) it would re-run its GeoIP lookup, see the current
// locale as still 'lang' -> default locale, decide the visitor's IP-based
// locale differs, and redirect to a localized version of the *current*
// `/lang/{locale}` path itself (e.g. `/tr/lang/fa`), which isn't a real
// route and 404s.
Route::get('/lang/{locale}', function (string $locale) {
    abort_unless(
        LaravelLocalization::checkLocaleInSupportedLocales($locale),
        404,
    );

    session(['locale' => $locale]);

    $previousUrl = url()->previous(route('home'));

    // Only run the previous URL through getLocalizedURL() when it actually
    // belongs to this locale-prefixed host. Other known hosts (e.g.
    // app.viravach.com) have no locale prefixes at all, so injecting one
    // there would 404 — redirect back to those unchanged instead.
    $target = parse_url($previousUrl, PHP_URL_HOST) === config('domains.public')
        ? LaravelLocalization::getLocalizedURL($locale, $previousUrl)
        : $previousUrl;

    return redirect($target)->cookie('locale_selected_manually', 'true', 60 * 24 * 365);
})->name('lang.switch')->withoutMiddleware(DetectLocaleFromIp::class);

// Legacy back-office path. Kept as a permanent redirect so bookmarks and
// any stale links survive the move to the dedicated admin host.
Route::any('/admin/{path?}', function (?string $path = null) {
    return redirect()->away(
        rtrim('https://'.config('domains.admin').'/'.$path, '/'),
        301,
    );
})->where('path', '.*')->name('admin.legacy');

// Legacy dashboard paths, with or without their old locale prefix. Permanent
// redirects keep bookmarks, e-mailed links and payment receipts working.
Route::any('/{locale}/dashboard/{path?}', function (string $locale, ?string $path = null) {
    return redirect()->away(
        rtrim('https://'.config('domains.app').'/'.$path, '/'),
        301,
    );
})->where(['locale' => '[a-zA-Z]{2}', 'path' => '.*'])->name('dashboard.legacy.localized');

Route::any('/dashboard/{path?}', function (?string $path = null) {
    return redirect()->away(
        rtrim('https://'.config('domains.app').'/'.$path, '/'),
        301,
    );
})->where('path', '.*')->name('dashboard.legacy');

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => [
            'localeSessionRedirect',
            'localizationRedirect',
            'localeViewPath',
        ],
    ],
    function () {

        Route::livewire('/', 'pages::home')->name('home');

        Route::livewire('/terms-and-conditions', 'pages::rules.terms-and-conditions')
            ->name('terms-and-conditions');

        Route::livewire('/category/{slug}', 'pages::company-category')
            ->name('companies.category');

        Route::livewire('/country/state/{slug}', 'pages::company-state')
            ->name('companies.state');

        Route::livewire('/companies/{slug}', 'pages::company')
            ->name('companies.show');

        Route::livewire('/pricing', 'pages::pricing')
            ->name('pricing');

        // Authentication entry points. These stay on the public host for now;
        // whether they move to app.* is decided in step 6, once the shared
        // session cookie is in place.
        Route::middleware('guest')->group(function () {
            Route::livewire('/sign-in', 'pages::auth.sign-in')
                ->name('auth.sign-in');

            Route::livewire('/secure-login', 'pages::auth.secure-login')
                ->name('auth.secure-login');

            Route::livewire('/sign-up', 'pages::auth.sign-up')
                ->name('auth.sign-up');

            Route::livewire('/reset-password', 'pages::auth.reset-password')
                ->name('auth.reset-password');
        });
    },
);
