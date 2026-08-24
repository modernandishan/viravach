<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps every non-public host out of search indexes.
 *
 * Search engines treat app.viravach.com and admin.viravach.com as separate
 * sites. Left unmarked, any inbound link to them would get indexed as
 * duplicate or thin content competing with the public directory.
 *
 * An `X-Robots-Tag` header is used rather than a robots.txt `Disallow`
 * because a disallowed URL is never fetched, so the crawler never learns it
 * should be dropped — it can still surface as a bare URL. Allowing the fetch
 * and answering with noindex is what actually removes it.
 *
 * Registered globally rather than on the `web` group so that Filament panel
 * routes, which bypass that group, are covered too.
 */
class DenyIndexingOnPrivateHosts
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->getHost() !== config('domains.public')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        return $response;
    }
}
