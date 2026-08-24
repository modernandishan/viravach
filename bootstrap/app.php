<?php

use App\Http\Middleware\ConfigureSessionForHost;
use App\Http\Middleware\DenyIndexingOnPrivateHosts;
use App\Http\Middleware\DetectLocaleFromIp;
use App\Http\Middleware\DiscardInvalidBroadcastSocketId;
use App\Http\Middleware\SetLocaleFromSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            // Each audience gets its own host and its own route file. Route
            // files stay host-agnostic; the mapping lives here alone, so
            // moving a surface to another host is a one-line change.
            Route::domain(config('domains.public'))
                ->middleware('web')
                ->group(base_path('routes/public.php'));

            // The dashboard: authenticated, noindex, locale from session.
            // Both entries must go in a single middleware() call — the
            // registrar replaces the attribute on each call rather than
            // appending, so a second call would silently drop the `web`
            // group and with it the session. Order matters too:
            // SetLocaleFromSession reads the session, so it has to sit
            // after the group that starts it.
            //
            // DetectLocaleFromIp is excluded because it redirects to a
            // locale-prefixed URL, and this host has no locale prefix.
            Route::domain(config('domains.app'))
                ->middleware(['web', SetLocaleFromSession::class])
                ->withoutMiddleware(DetectLocaleFromIp::class)
                ->group(base_path('routes/app.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // Laravel resolves the request host from proxy-supplied headers. Since
        // every host now maps to the same container, an attacker could forge a
        // Host header to poison generated URLs (password-reset links, cached
        // pages, redirects). Whitelisting the four known hosts closes that.
        $middleware->trustHosts(at: fn () => array_values(config('domains', [])));

        // Must run before StartSession, hence `prepend` rather than `append`.
        $middleware->web(prepend: [
            ConfigureSessionForHost::class,
        ]);

        // Global rather than group-scoped: the Filament panel does not use
        // the `web` middleware group, and it is the host most in need of this.
        $middleware->append(DenyIndexingOnPrivateHosts::class);

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
