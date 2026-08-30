<?php

namespace Tests;

use App\Observers\UserObserver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Host-aware request helpers. Routes are split per hostname in
     * bootstrap/app.php, so requests must carry the right Host header or they
     * miss their routes (or hit the legacy-host redirects). Pass the full URL
     * returned here to get()/postJson()/etc.
     */
    protected function publicUrl(string $path = '/'): string
    {
        return 'https://'.config('domains.public').$path;
    }

    protected function appUrl(string $path = '/'): string
    {
        return 'https://'.config('domains.app').$path;
    }

    protected function adminUrl(string $path = '/'): string
    {
        return 'https://'.config('domains.admin').$path;
    }

    /**
     * Roles are assigned to every user via {@see UserObserver}, so any
     * test that refreshes the database needs them seeded before creating users.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class), true)) {
            $this->seed(RoleSeeder::class);
        }
    }
}
