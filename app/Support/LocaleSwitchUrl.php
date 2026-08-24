<?php

namespace App\Support;

/**
 * Resolves the correct locale-switch URL for the current host. The public
 * site (config('domains.public')) carries a locale prefix on every URL and
 * its 'lang.switch' route rewrites url()->previous() through
 * LaravelLocalization::getLocalizedURL(); the dashboard (config('domains.app'))
 * has no locale prefix at all and instead uses its own 'app.lang.switch'
 * route (routes/app.php), which just remembers the locale in the session and
 * redirects back(). Every language-switcher view must build its links
 * through here rather than calling route('lang.switch', ...) directly, or
 * switching language from a dashboard page sends the user to the public host
 * and 404s (see routes/public.php).
 */
class LocaleSwitchUrl
{
    public static function for(string $locale): string
    {
        if (request()->getHost() === config('domains.app')) {
            return route('app.lang.switch', $locale);
        }

        return route('lang.switch', $locale);
    }
}
