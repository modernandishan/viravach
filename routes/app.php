<?php

use App\Http\Controllers\EditorUploadController;
use App\Http\Controllers\PaymentCallbackController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/*
|--------------------------------------------------------------------------
| Authenticated Dashboard Routes
|--------------------------------------------------------------------------
|
| Served from config('domains.app'). Never indexed, so there is no locale
| prefix here — SetLocaleFromSession resolves the language instead, from the
| session cookie shared across *.viravach.com.
|
| Route names are unchanged from when these lived on the public host, so
| every existing route() call and breadcrumb definition keeps working; only
| the generated host and the dropped /dashboard prefix differ.
|
*/

// Mirror of the public host's locale switch, so the language selector in the
// dashboard layout has a same-host endpoint to post to. It writes the same
// shared session key, which means switching language here also carries over
// to the public site, and vice versa.
Route::get('/lang/{locale}', function (string $locale) {
    abort_unless(
        LaravelLocalization::checkLocaleInSupportedLocales($locale),
        404,
    );

    session(['locale' => $locale]);

    return back()->cookie('locale_selected_manually', 'true', 60 * 24 * 365);
})->name('app.lang.switch');

Route::post('/editor/upload', [EditorUploadController::class, 'store'])
    ->name('editor.upload')
    ->middleware('auth');

Route::middleware('auth')->group(function () {

    Route::livewire('/', 'pages::dashboard')
        ->name('dashboard');

    Route::livewire('/profile', 'pages::dashboard.profile')
        ->name('profile');

    Route::livewire('/settings', 'pages::dashboard.settings')
        ->name('settings');

    Route::livewire('/my-companies', 'pages::dashboard.my-companies')
        ->name('my-companies');

    Route::livewire('/create-company', 'pages::dashboard.create-company')
        ->name('create.company');

    Route::livewire('/edit-company/{company}', 'pages::dashboard.edit-company')
        ->name('edit.company');

    Route::livewire('/subscriptions/{company?}', 'pages::dashboard.subscriptions')
        ->name('subscriptions');

    Route::livewire('/chat', 'pages::dashboard.chat')
        ->name('chat');

    // Ticket support: the user's own ticket threads. Access control is the
    // auth group itself — eligibility is the plan 'support' feature, which
    // PlanSeeder sets 'true' on every plan (checked in the component).
    Route::livewire('/tickets', 'pages::dashboard.tickets')
        ->name('tickets');

    Route::livewire('/payments', 'pages::dashboard.payments')
        ->name('payments');

    Route::livewire('/company-views', 'pages::dashboard.company-views')
        ->name('company-views');

    // No role:support middleware here: company owners reach this same inbox
    // to answer their own companies' direct conversations (see 2D), so
    // access control lives in the component's mount() instead, which
    // authorizes support agents OR company owners.
    Route::livewire('/support-chats', 'pages::dashboard.support-chats')
        ->name('support-chats');

    // Zarinpal returns the payer here. Because the URL is generated from this
    // route name at payment time, it follows the host automatically — but the
    // callback domain registered in the gateway's merchant panel must be
    // updated to app.viravach.com before going live.
    Route::get('/payment/callback', PaymentCallbackController::class)
        ->name('payment.callback');
});
