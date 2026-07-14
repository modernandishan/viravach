<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Plans\Pages\CreatePlan;
use App\Filament\Resources\Plans\Pages\EditPlan;
use App\Filament\Resources\Plans\Pages\ListPlans;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravelcm\Subscriptions\Interval;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PlanResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        $user->givePermissionTo(
            collect(['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny'])
                ->map(fn (string $ability) => Permission::firstOrCreate([
                    'name' => "{$ability}:Plan",
                    'guard_name' => 'web',
                ]))
        );

        $this->actingAs($user);
    }

    protected function makePlan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'slug' => 'test-plan',
            'name' => ['en' => 'Test Plan', 'fa' => 'پلن تست'],
            'description' => ['en' => 'A test plan', 'fa' => 'یک پلن تست'],
            'is_active' => true,
            'price' => 10,
            'signup_fee' => 0,
            'currency' => 'USD',
            'trial_period' => 0,
            'trial_interval' => Interval::DAY->value,
            'invoice_period' => 1,
            'invoice_interval' => Interval::MONTH->value,
            'grace_period' => 0,
            'grace_interval' => Interval::DAY->value,
            'sort_order' => 0,
        ], $overrides));
    }

    public function test_it_can_list_plans(): void
    {
        $plans = collect(range(1, 3))->map(
            fn (int $i) => $this->makePlan(['slug' => "plan-{$i}"])
        );

        Livewire::test(ListPlans::class)
            ->assertCanSeeTableRecords($plans);
    }

    public function test_it_can_create_a_plan_with_translations(): void
    {
        Livewire::test(CreatePlan::class)
            ->fillForm([
                'slug' => 'pro',
                'sort_order' => 1,
                'is_active' => true,
                'active_subscribers_limit' => null,
                'price' => 29,
                'signup_fee' => 0,
                'currency' => 'USD',
                'invoice_period' => 1,
                'invoice_interval' => Interval::MONTH->value,
                'trial_period' => 30,
                'trial_interval' => Interval::DAY->value,
                'grace_period' => 3,
                'grace_interval' => Interval::DAY->value,
                'name' => [
                    'en' => 'Pro',
                    'fa' => 'حرفه‌ای',
                ],
                'description' => [
                    'en' => 'Pro plan',
                    'fa' => 'پلن حرفه‌ای',
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $plan = Plan::sole();

        $this->assertSame('pro', $plan->slug);
        $this->assertSame('Pro', $plan->getTranslation('name', 'en'));
        $this->assertSame('حرفه‌ای', $plan->getTranslation('name', 'fa'));
        $this->assertSame(30, $plan->trial_period);
        $this->assertTrue($plan->hasTrial());
        $this->assertFalse($plan->isFree());
    }

    public function test_it_can_update_a_plan(): void
    {
        $plan = $this->makePlan(['slug' => 'old-slug']);

        Livewire::test(EditPlan::class, ['record' => $plan->getRouteKey()])
            ->fillForm([
                'slug' => 'new-slug',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('new-slug', $plan->fresh()->slug);
    }
}
