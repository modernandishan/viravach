<?php

use App\Ai\Agents\test;
use App\Http\Controllers\EditorUploadController;
use App\Http\Middleware\DetectLocaleFromIp;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/*Route::get('/', function () {
    return view('index');
});*/

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
Route::get('/haha', function () {
    $response = (new test)->prompt('hello, how are you?');
    dd($response->text);
});

Route::post('/editor/upload', [EditorUploadController::class, 'store'])
    ->name('editor.upload')
    ->middleware('auth');

Route::get('/lang/{locale}', function (string $locale) {
    abort_unless(
        LaravelLocalization::checkLocaleInSupportedLocales($locale),
        404,
    );

    session(['locale' => $locale]);

    return redirect(
        LaravelLocalization::getLocalizedURL($locale, url()->previous()),
    )->cookie('locale_selected_manually', 'true', 60 * 24 * 365);
})->name('lang.switch')->withoutMiddleware(DetectLocaleFromIp::class);

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

        // Route::livewire("/export-directory", "pages::home")->name("export-directory");

        Route::livewire('/', 'pages::home')->name('home');
        Route::livewire('/terms-and-conditions', 'pages::rules.terms-and-conditions')->name('terms-and-conditions');

        Route::livewire('/companies/category/{slug}', 'pages::company-category')
            ->name('companies.category');

        Route::livewire('/companies/{slug}', 'pages::company')
            ->name('companies.show');

        // auth routes
        Route::middleware('guest')->group(function () {
            Route::livewire('/sign-in', 'pages::auth.sign-in')->name(
                'auth.sign-in',
            );
            Route::livewire('/secure-login', 'pages::auth.secure-login')->name(
                'auth.secure-login',
            );
            Route::livewire('/sign-up', 'pages::auth.sign-up')->name(
                'auth.sign-up',
            );
            Route::livewire(
                '/reset-password',
                'pages::auth.reset-password',
            )->name('auth.reset-password');
        });

        Route::group(
            [
                'prefix' => 'dashboard',
            ],
            function () {

                Route::livewire('/', 'pages::dashboard')
                    ->name('dashboard')
                    ->middleware('auth');

                Route::livewire('/profile', 'pages::dashboard.profile')
                    ->name('profile')
                    ->middleware('auth');

                Route::livewire('/settings', 'pages::dashboard.settings')
                    ->name('settings')
                    ->middleware('auth');

                Route::livewire('/subscriptions', 'pages::dashboard.subscriptions')
                    ->name('subscriptions')
                    ->middleware('auth');

                Route::livewire('/payment-history', 'pages::dashboard.payment-history')
                    ->name('payment-history')
                    ->middleware('auth');

            });

    },
);
