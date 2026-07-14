<?php

namespace Tests\Feature;

use InteractionDesignFoundation\GeoIP\Facades\GeoIP;
use InteractionDesignFoundation\GeoIP\Location;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    /**
     * Regression test for a bug where switching languages while browsing
     * from an IP whose GeoIP-suggested locale differs from the current one
     * (e.g. a Turkish IP switching to Persian) sent the user to a broken,
     * double-prefixed URL like `/tr/lang/fa` instead of switching locales.
     *
     * `DetectLocaleFromIp` is appended to the global `web` middleware group,
     * so without an explicit exclusion it also ran on `/lang/{locale}`
     * itself. Since the `locale_selected_manually` cookie it checks for is
     * only set by that route's own response, the very first manual switch
     * (before the cookie exists) re-triggered the GeoIP redirect against the
     * `/lang/{locale}` path itself, producing a 404.
     */
    public function test_switching_locale_is_not_hijacked_by_geoip_detection(): void
    {
        GeoIP::shouldReceive('getLocation')->andReturn(new Location(['iso_code' => 'TR']));

        $response = $this->get('/lang/fa');

        $response->assertRedirect();
        $this->assertStringNotContainsString('/lang/', (string) $response->headers->get('Location'));
        $response->assertCookie('locale_selected_manually');
    }

    public function test_switching_to_an_unsupported_locale_returns_a_404(): void
    {
        $response = $this->get('/lang/xx');

        $response->assertNotFound();
    }
}
