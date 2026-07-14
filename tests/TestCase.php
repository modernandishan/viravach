<?php

namespace Tests;

use App\Observers\UserObserver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
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
