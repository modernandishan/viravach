<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Scopes the session cookie to the host being served.
 *
 * The back-office and the public site share one codebase and one session
 * driver, so without this they would also share one cookie: a stolen or
 * XSS-leaked session token from the public site — which renders
 * user-submitted company content — would be a valid admin session too.
 *
 * Rewriting `session.cookie` before StartSession resolves the session
 * manager gives the admin host its own, host-only cookie. The trade-off is
 * that signing in to the site does not sign you in to the panel, which is
 * the intended behaviour.
 *
 * This must run before StartSession, and is therefore registered both at
 * the head of the `web` group (public site, dashboard, Livewire's global
 * update endpoint) and at the head of the Filament panel's own middleware
 * stack, which does not use the `web` group.
 */
class ConfigureSessionForHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getHost() === config('domains.admin')) {
            config([
                'session.cookie' => config('session.cookie').'_admin',
                // Host-only: never sent to viravach.com or app.viravach.com.
                'session.domain' => null,
            ]);
        }

        return $next($request);
    }
}
