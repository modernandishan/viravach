<?php

use App\Http\Middleware\DetectLocaleFromIp;
use App\Http\Middleware\DiscardInvalidBroadcastSocketId;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // Must run in the `web` group, before the LaravelLocalization route-group
        // middlewares (localeSessionRedirect/localizationRedirect/localeViewPath
        // in routes/web.php), which are applied on top of this implicit group.
        $middleware->web(append: [
            DetectLocaleFromIp::class,
            // Strips the "X-Socket-ID: undefined" header a not-yet-connected
            // Echo client sends, which would otherwise make pusher-php throw
            // inside every queued MessageWasSent broadcast (see the class).
            DiscardInvalidBroadcastSocketId::class,
        ]);

        $middleware->alias([
            /**** OTHER MIDDLEWARE ALIASES ****/
            'localize' => LaravelLocalizationRoutes::class,
            'localizationRedirect' => LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect' => LocaleSessionRedirect::class,
            'localeCookieRedirect' => LocaleCookieRedirect::class,
            'localeViewPath' => LaravelLocalizationViewPath::class,
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
