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

// A content-generation run holds the row lock only for one chain step, so
// anything still queued/generating after 15 minutes means its worker died.
// Sweep frequently enough that a user's "retry" button never fights a dead
// lock for long.
Schedule::command('app:sweep-stuck-content-generations')->everyFiveMinutes();

// WordPress articles are fully automatic: once a day is enough for the
// 3-day cadence, and the quota's calendar-month window is derived from
// now() at check time, so running on the 1st re-enables exhausted
// companies with nothing to reset. All the per-company gates (connection,
// settings, quota, in-flight run) are re-checked inside the service, and
// the command isolates one company's failure from the rest.
Schedule::command('app:generate-scheduled-wordpress-content')->dailyAt('03:17');
