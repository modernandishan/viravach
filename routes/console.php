<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily is sufficient for a 14-day trial window, but the check itself is
// cheap, so run hourly to keep the window a company overstays its trial
// small.
Schedule::command('app:revert-expired-trials')->hourly();
