<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active locale for non-indexed hosts.
 *
 * The public site derives its locale from the URL prefix because search
 * engines need one canonical URL per language. The dashboard is noindex, so
 * that prefix buys nothing and only clutters the URL. Instead the locale is
 * read from the session, which the shared `.viravach.com` cookie makes
 * available across hosts and which `/lang/{locale}` already maintains.
 */
class SetLocaleFromSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        if (! LaravelLocalization::checkLocaleInSupportedLocales($locale)) {
            $locale = config('app.locale');
        }

        // Go through the package rather than app()->setLocale() so that its
        // own state stays consistent, keeping getLocalizedURL() usable in
        // shared layout partials such as the language switcher.
        LaravelLocalization::setLocale($locale);

        return $next($request);
    }
}
