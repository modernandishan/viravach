<?php

namespace Tests\Feature;

use App\Models\Plan;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PricingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_the_plan_comparison_for_guests(): void
    {
        $this->seed(PlanSeeder::class);

        $response = $this->get(route('pricing'));

        $response->assertOk();
        $response->assertSeeText(Plan::where('slug', 'free')->first()->name);
        $response->assertSeeText(Plan::where('slug', 'pro-3-months')->first()->name);
    }

    public function test_it_links_plan_ctas_to_the_subscriptions_page(): void
    {
        $this->seed(PlanSeeder::class);

        Livewire::test('pages::pricing')
            ->assertSee(route('subscriptions'));
    }
}
