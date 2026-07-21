<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use App\Services\CompanySubscriptionService;
use App\Services\Payment\InvoicePaymentService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SubscriptionsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_plans_from_the_database(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        Company::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.subscriptions')
            ->assertSeeText(Plan::where('slug', 'free')->first()->name)
            ->assertSeeText(Plan::where('slug', 'pro-3-months')->first()->name);
    }

    public function test_it_shows_a_create_company_prompt_when_the_user_has_no_companies(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.subscriptions')
            ->assertSeeText(__('subscriptions.no_companies_notice'));
    }

    public function test_it_grants_the_pro_plus_trial_exactly_once(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $companyA = Company::factory()->for($user)->create();
        $companyB = Company::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.subscriptions', ['company' => $companyA->id])
            ->call('startTrial')
            ->assertSeeText(__('subscriptions.trial_started_successfully'));

        $user->refresh();
        $this->assertNotNull($user->trial_used_at);

        $proPlusPlan = Plan::where('slug', 'pro-plus-3-months')->firstOrFail();
        $this->assertTrue($companyA->subscribedTo($proPlusPlan->id));

        $trial = $companyA->fresh()->activeSubscription();
        $this->assertTrue($trial->is_trial);
        $this->assertTrue($trial->ends_at->betweenIncluded(now()->addDays(13), now()->addDays(15)));

        Livewire::actingAs($user)
            ->test('pages::dashboard.subscriptions', ['company' => $companyB->id])
            ->call('startTrial')
            ->assertForbidden();

        $this->assertFalse($companyB->fresh()->subscribedTo($proPlusPlan->id));
    }

    public function test_trial_stays_used_even_after_the_company_it_was_granted_to_is_deleted(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.subscriptions', ['company' => $company->id])
            ->call('startTrial');

        $company->delete();

        $this->assertTrue($user->fresh()->hasUsedTrial());
    }

    public function test_starting_a_trial_is_forbidden_when_the_company_already_has_a_paid_plan(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();

        app(CompanySubscriptionService::class)->switchToPlan(
            $company,
            Plan::where('slug', 'pro-3-months')->firstOrFail(),
        );

        Livewire::actingAs($user)
            ->test('pages::dashboard.subscriptions', ['company' => $company->id])
            ->call('startTrial')
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasUsedTrial());
    }

    public function test_the_daily_revert_command_switches_expired_trials_back_to_free(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.subscriptions', ['company' => $company->id])
            ->call('startTrial');

        // Simulate the 14 days having elapsed.
        $company->fresh()->activeSubscription()->forceFill([
            'ends_at' => now()->subDay(),
        ])->save();

        $this->artisan('app:revert-expired-trials')->assertSuccessful();

        $freePlan = Plan::where('slug', 'free')->firstOrFail();
        $this->assertTrue($company->fresh()->subscribedTo($freePlan->id));

        // The user's one-time grant stays consumed even after the revert.
        $this->assertTrue($user->fresh()->hasUsedTrial());
    }

    public function test_the_revert_command_does_not_touch_a_currently_paid_company_that_previously_had_a_trial(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();
        $service = app(CompanySubscriptionService::class);

        Livewire::actingAs($user)
            ->test('pages::dashboard.subscriptions', ['company' => $company->id])
            ->call('startTrial');

        // The trial's historical row is marked as expired, but the company
        // has since purchased a real paid plan on a fresh subscription row.
        $trialSubscription = $company->fresh()->activeSubscription();
        $trialSubscription->forceFill(['ends_at' => now()->subDay()])->save();

        $proPlusPlan = Plan::where('slug', 'pro-plus-1-year')->firstOrFail();
        $service->switchToPlan($company->fresh(), $proPlusPlan);

        $this->artisan('app:revert-expired-trials')->assertSuccessful();

        $this->assertTrue($company->fresh()->subscribedTo($proPlusPlan->id));
    }

    public function test_subscribing_to_the_free_plan_switches_instantly_without_an_invoice(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();
        $freePlan = Plan::where('slug', 'free')->firstOrFail();

        Livewire::actingAs($user)
            ->test('pages::dashboard.subscriptions', ['company' => $company->id])
            ->call('subscribeToPlan', $freePlan->id)
            ->assertSeeText(__('subscriptions.switch_success'));

        $this->assertTrue($company->fresh()->subscribedTo($freePlan->id));
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_subscribing_to_a_paid_plan_creates_a_pending_invoice_and_redirects_to_the_gateway(): void
    {
        $this->seed(PlanSeeder::class);

        $this->mock(InvoicePaymentService::class, function ($mock) {
            $mock->shouldReceive('purchase')->once()->andReturn('https://sandbox.zarinpal.com/pg/StartPay/mock-authority');
        });

        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();
        $paidPlan = Plan::where('slug', 'pro-3-months')->firstOrFail();

        Livewire::actingAs($user)
            ->test('pages::dashboard.subscriptions', ['company' => $company->id])
            ->call('subscribeToPlan', $paidPlan->id)
            ->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/mock-authority');

        $this->assertDatabaseHas('invoices', [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'plan_id' => $paidPlan->id,
            'status' => InvoiceStatus::Pending->value,
        ]);
    }

    public function test_subscribing_to_a_plan_priced_below_the_minimum_shows_an_error_and_creates_no_invoice(): void
    {
        $this->seed(PlanSeeder::class);

        $cheapPlan = Plan::create([
            'slug' => 'cheap-test-plan',
            'name' => ['en' => 'Cheap Plan', 'fa' => 'پلن ارزان'],
            'description' => ['en' => 'Below minimum', 'fa' => 'زیر حداقل'],
            'is_active' => true,
            'price' => config('pricing.min_purchase_amount') - 1,
            'signup_fee' => 0,
            'currency' => 'IRT',
            'trial_period' => 0,
            'trial_interval' => 'day',
            'invoice_period' => 1,
            'invoice_interval' => 'month',
            'grace_period' => 0,
            'grace_interval' => 'day',
            'sort_order' => 99,
        ]);

        $this->mock(InvoicePaymentService::class, function ($mock) {
            $mock->shouldNotReceive('purchase');
        });

        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.subscriptions', ['company' => $company->id])
            ->call('subscribeToPlan', $cheapPlan->id)
            ->assertSeeText(__('subscriptions.amount_below_minimum'));

        $this->assertDatabaseMissing('invoices', [
            'company_id' => $company->id,
            'plan_id' => $cheapPlan->id,
        ]);
    }

    public function test_it_403s_when_preselecting_another_users_company(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $other = Company::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.subscriptions', ['company' => $other->id])
            ->assertForbidden();
    }
}
