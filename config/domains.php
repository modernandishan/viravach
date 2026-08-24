<?php

/*
|--------------------------------------------------------------------------
| Application Host Map
|--------------------------------------------------------------------------
|
| Viravach runs as a single Laravel codebase served over several hostnames.
| Each hostname targets a distinct audience and gets its own route file,
| middleware stack and session cookie:
|
|   public → the SEO-critical, multilingual, publicly indexable site
|   app    → the authenticated dashboard for business owners (noindex)
|   admin  → the Filament back-office (noindex, IP-restricted at the proxy)
|   api    → the stateless, token-authenticated public API for external
|            consumers (mobile clients, partners, other in-house products)
|
| Values are bare hostnames without scheme or trailing slash, because
| Route::domain() and Filament's ->domain() both expect a host pattern.
|
*/

return [

    'public' => env('DOMAIN_PUBLIC', 'viravach.com'),

    'app' => env('DOMAIN_APP', 'app.viravach.com'),

    'admin' => env('DOMAIN_ADMIN', 'admin.viravach.com'),

    'api' => env('DOMAIN_API', 'api.viravach.com'),

    /*
    |--------------------------------------------------------------------------
    | Cookie Scope
    |--------------------------------------------------------------------------
    |
    | The leading-dot parent domain that a session cookie may be shared across.
    | Used later when the sign-in flow needs to hand a session from the public
    | site to app.*, and consumed by SESSION_DOMAIN. The admin host will
    | deliberately opt out of this shared scope with its own cookie name.
    |
    */

    'cookie' => env('DOMAIN_COOKIE', '.viravach.com'),

];
