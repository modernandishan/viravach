<?php

namespace Tests\Unit;

use App\Support\LocalizedDate;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LocalizedDateTest extends TestCase
{
    protected function tearDown(): void
    {
        app()->setLocale(config('app.locale'));

        parent::tearDown();
    }

    public function test_it_returns_null_for_a_null_date(): void
    {
        $this->assertNull(LocalizedDate::format(null));
    }

    public function test_fa_locale_formats_as_jalali(): void
    {
        app()->setLocale('fa');

        // 2026-07-19 is 1405-04-28 on the Jalali calendar.
        $date = Carbon::create(2026, 7, 19, 14, 30);

        $this->assertSame('1405/04/28', LocalizedDate::format($date));
        $this->assertSame('1405/04/28 14:30', LocalizedDate::format($date, LocalizedDate::FORMAT_DATETIME));
    }

    public function test_non_fa_locales_format_as_gregorian(): void
    {
        app()->setLocale('en');

        $date = Carbon::create(2026, 7, 19, 14, 30);

        $this->assertSame('19 July 2026', LocalizedDate::format($date));
        $this->assertSame('19 July 2026, 14:30', LocalizedDate::format($date, LocalizedDate::FORMAT_DATETIME));
    }

    public function test_the_stored_carbon_instance_is_not_mutated(): void
    {
        app()->setLocale('en');

        $date = Carbon::create(2026, 7, 19);
        $originalLocale = $date->locale;

        LocalizedDate::format($date);

        $this->assertSame($originalLocale, $date->locale);
    }
}
