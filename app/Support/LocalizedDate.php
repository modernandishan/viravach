<?php

namespace App\Support;

use Ariaieboy\Jalali\Jalali;
use Carbon\CarbonInterface;

/**
 * Single source of truth for displaying dates on user-facing (multilingual)
 * pages: Jalali for the Persian locale, Gregorian everywhere else. Display
 * only — the database always stores Gregorian/UTC, and the Filament admin
 * panel (Persian-only) keeps using ariaieboy/filament-jalali's own
 * jalaliDate()/jalaliDateTime() column modifiers directly.
 */
class LocalizedDate
{
    public const FORMAT_DATE = 'date';

    public const FORMAT_DATETIME = 'datetime';

    public static function format(?CarbonInterface $date, string $format = self::FORMAT_DATE): ?string
    {
        if ($date === null) {
            return null;
        }

        if (app()->getLocale() === 'fa') {
            $pattern = $format === self::FORMAT_DATETIME ? 'Y/m/d H:i' : 'Y/m/d';

            return Jalali::fromCarbon($date)->format($pattern);
        }

        $pattern = $format === self::FORMAT_DATETIME ? 'j F Y, H:i' : 'j F Y';

        return $date->copy()->locale(app()->getLocale())->translatedFormat($pattern);
    }
}
