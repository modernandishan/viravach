<?php

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

Route::view('/haha', 'img-sample');

Route::get("/lang/{locale}", function (string $locale) {
    abort_unless(
        LaravelLocalization::checkLocaleInSupportedLocales($locale),
        404,
    );

    session(["locale" => $locale]);

    return redirect(
        LaravelLocalization::getLocalizedURL($locale, url()->previous()),
    )->cookie("locale_selected_manually", "true", 60 * 24 * 365);
})->name("lang.switch");

Route::group(
    [
        "prefix" => LaravelLocalization::setLocale(),
        "middleware" => [
            "localeSessionRedirect",
            "localizationRedirect",
            "localeViewPath",
        ],
    ],
    function () {


        //Route::livewire("/export-directory", "pages::home")->name("export-directory");


        Route::livewire("/", "pages::home")->name("home");

        // auth routes
        Route::livewire("/sign-in", "pages::auth.sign-in")->name(
            "auth.sign-in",
        );
        Route::livewire("/sign-up", "pages::auth.sign-up")->name(
            "auth.sign-up",
        );
        Route::livewire("/reset-password", "pages::auth.reset-password")->name(
            "auth.reset-password",
        );
    },
);
