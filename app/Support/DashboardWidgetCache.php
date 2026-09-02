<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cache keys for the dashboard widgets, in one place so the widgets that
 * read them and the services that invalidate them cannot drift apart.
 *
 * This is a plain helper in the existing App\Support namespace (alongside
 * LocalizedDate, LocaleSwitchUrl and TrustBadgeSanitizer), not a new caching
 * layer: it composes key strings and forwards to the Cache facade, nothing
 * more. Without it, every chokepoint would have to repeat the
 * locale × widget loop and any future widget would mean editing several
 * services.
 *
 * Keys are per-user, locale-keyed (widget payloads contain translated plan
 * names and LocalizedDate-formatted dates) and version-suffixed, matching
 * the convention used by ⚡footer and ⚡world-globe.
 */
class DashboardWidgetCache
{
    public const VERSION = 'v1';

    public const VIEWS_CHART = 'views-chart';

    public const TOP_COMPANIES = 'top-companies';

    public const SUBSCRIPTIONS = 'subscriptions';

    /**
     * Every widget that caches. Widgets 3 (companies) and 5 (invoices) are
     * deliberately absent — see their components for why they run their
     * single indexed query uncached.
     *
     * @var array<int, string>
     */
    public const WIDGETS = [
        self::VIEWS_CHART,
        self::TOP_COMPANIES,
        self::SUBSCRIPTIONS,
    ];

    /**
     * View-derived widgets. Public page views are written by
     * RecordsPageView on every visit to a public company page, so there is
     * no discrete event to invalidate on — these carry a short TTL instead
     * of living forever. A view arriving a few minutes late is not the
     * class of staleness that matters; a plan or publication change is.
     */
    public const VIEW_TTL_MINUTES = 10;

    public static function key(string $widget, string $locale, int $userId): string
    {
        return "dashboard.widget.{$widget}.".self::VERSION.".{$locale}.{$userId}";
    }

    /**
     * Event-invalidated payload: lives until a chokepoint clears it.
     */
    public static function remember(string $widget, int $userId, Closure $callback): mixed
    {
        return Cache::rememberForever(self::key($widget, app()->getLocale(), $userId), $callback);
    }

    /**
     * TTL payload, for the view-derived widgets that have no chokepoint.
     */
    public static function rememberViews(string $widget, int $userId, Closure $callback): mixed
    {
        return Cache::remember(
            self::key($widget, app()->getLocale(), $userId),
            now()->addMinutes(self::VIEW_TTL_MINUTES),
            $callback,
        );
    }

    /**
     * Clears every widget key for one user, across every supported locale.
     * Called from the write paths that change what the widgets show.
     */
    public static function forgetForUser(?int $userId): void
    {
        if ($userId === null) {
            return;
        }

        foreach (array_keys((array) config('laravellocalization.supportedLocales')) as $locale) {
            foreach (self::WIDGETS as $widget) {
                Cache::forget(self::key($widget, (string) $locale, $userId));
            }
        }
    }
}
