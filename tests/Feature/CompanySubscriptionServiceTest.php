<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Plan;
use App\Services\CompanySubscriptionService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_switch_to_plan_starts_a_subscription_when_none_exists(): void
    {
        $this->seed(PlanSeeder::class);

        $company = Company::factory()->create();
        $plan = Plan::where('slug', 'pro-3-months')->firstOrFail();

        $subscription = app(CompanySubscriptionService::class)->switchToPlan($company, $plan);

        $this->assertSame($plan->id, $subscription->plan_id);
        $this->assertTrue($company->subscribedTo($plan->id));
    }

    public function test_switch_to_plan_mutates_the_same_subscription_row_instead_of_creating_a_new_one(): void
    {
        $this->seed(PlanSeeder::class);

        $company = Company::factory()->create();
        $service = app(CompanySubscriptionService::class);

        $first = $service->switchToPlan($company, Plan::where('slug', 'pro-3-months')->firstOrFail());
        $second = $service->switchToPlan($company, Plan::where('slug', 'pro-plus-6-months')->firstOrFail());

        // changePlan() mutates the existing subscription record in place —
        // it does not end the old one and insert a new row.
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, $company->planSubscriptions()->count());
        $this->assertSame(
            Plan::where('slug', 'pro-plus-6-months')->value('id'),
            $second->fresh()->plan_id,
        );
    }

    public function test_switch_to_plan_clears_ends_at_for_the_free_plan(): void
    {
        $this->seed(PlanSeeder::class);

        $company = Company::factory()->create();
        $service = app(CompanySubscriptionService::class);

        $service->switchToPlan($company, Plan::where('slug', 'pro-3-months')->firstOrFail());
        $subscription = $service->switchToPlan($company, Plan::where('slug', 'free')->firstOrFail());

        $this->assertNull($subscription->fresh()->ends_at);
    }

    public function test_assign_free_plan_if_missing_does_nothing_when_a_subscription_already_exists(): void
    {
        $this->seed(PlanSeeder::class);

        $company = Company::factory()->create();
        $service = app(CompanySubscriptionService::class);

        $proPlan = Plan::where('slug', 'pro-3-months')->firstOrFail();
        $service->switchToPlan($company, $proPlan);

        $service->assignFreePlanIfMissing($company);

        $this->assertTrue($company->fresh()->subscribedTo($proPlan->id));
    }

    public function test_assign_free_plan_if_missing_is_a_no_op_when_no_free_plan_is_seeded(): void
    {
        $company = Company::factory()->create();

        app(CompanySubscriptionService::class)->assignFreePlanIfMissing($company);

        $this->assertNull($company->activeSubscription());
    }
}
