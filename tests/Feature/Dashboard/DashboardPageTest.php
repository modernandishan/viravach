<?php

namespace Tests\Feature\Dashboard;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_away_from_the_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect();
    }
}
