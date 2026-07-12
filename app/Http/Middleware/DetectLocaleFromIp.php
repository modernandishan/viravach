<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use InteractionDesignFoundation\GeoIP\Facades\GeoIP;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DetectLocaleFromIp
{
    /**
     * @var array<string, string>
     */
    protected array $countryToLocale = [
        'IR' => 'fa',
        'TR' => 'tr',
        'RU' => 'ru',
        'SA' => 'ar',
        'AE' => 'ar',
        'IQ' => 'ar',
        'EG' => 'ar',
        'QA' => 'ar',
        'KW' => 'ar',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->cookie('locale_selected_manually')) {
            return $next($request);
        }

        try {
            $isoCode = GeoIP::getLocation($request->ip())->iso_code;
        } catch (Throwable) {
            return $next($request);
        }

        if (blank($isoCode) || ! isset($this->countryToLocale[$isoCode])) {
            return $next($request);
        }

        $suggestedLocale = $this->countryToLocale[$isoCode];

        if ($suggestedLocale === LaravelLocalization::getCurrentLocale()) {
            return $next($request);
        }

        return redirect(LaravelLocalization::getLocalizedURL($suggestedLocale))
            ->cookie('locale_auto_detected', 'true', 60 * 24 * 30);
    }
}
