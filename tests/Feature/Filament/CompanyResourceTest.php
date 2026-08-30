<?php

namespace Tests\Feature\Filament;

use App\Enums\CompanyReviewStatus;
use App\Filament\Resources\Companies\Pages\CreateCompany;
use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\Companies\RelationManagers\AddressesRelationManager;
use App\Filament\Resources\Companies\RelationManagers\BrandsRelationManager;
use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\CompanyBrand;
use App\Models\CompanyPublication;
use App\Models\Country;
use App\Models\Plan;
use App\Models\User;
use App\Services\CompanyPublicationService;
use App\Services\CompanySubscriptionService;
use Database\Seeders\PlanSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CompanyResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        $user->givePermissionTo(
            collect(['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny', 'Approve', 'Reject'])
                ->map(fn (string $ability) => Permission::firstOrCreate([
                    'name' => "{$ability}:Company",
                    'guard_name' => 'web',
                ]))
        );

        $this->actingAs($user);
    }

    private function makeCountry(): Country
    {
        return Country::create([
            'name' => ['en' => 'Testland', 'fa' => 'تست‌لند'],
            'official_name' => ['en' => 'Republic of Testland', 'fa' => 'جمهوری تست‌لند'],
            'capital' => ['en' => 'Test City', 'fa' => 'شهر تست'],
            'currency_name' => ['en' => 'Test Dollar', 'fa' => 'دلار تست'],
            'slug' => 'testland-'.uniqid(),
            'phone_code' => '+000',
            'currency' => 'TST',
            'currency_symbol' => 'T$',
        ]);
    }

    public function test_it_can_list_companies(): void
    {
        $companies = Company::factory()->count(3)->create();

        Livewire::test(ListCompanies::class)
            ->assertCanSeeTableRecords($companies);
    }

    public function test_it_can_create_a_company_with_translations(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();

        Livewire::test(CreateCompany::class)
            ->fillForm([
                'user_id' => $user->id,
                'slug' => 'acme-co',
                'review_status' => CompanyReviewStatus::PendingReview->value,
                'name' => [
                    'en' => 'Acme Co',
                    'fa' => 'شرکت آکمی',
                ],
                'description' => [
                    'en' => 'About us',
                    'fa' => 'درباره ما',
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $company = Company::sole();

        $this->assertSame('acme-co', $company->slug);
        $this->assertSame('Acme Co', $company->getTranslation('name', 'en'));
        $this->assertSame('شرکت آکمی', $company->getTranslation('name', 'fa'));
        $this->assertTrue($company->user->is($user));
    }

    public function test_it_can_update_a_company(): void
    {
        $this->seed(PlanSeeder::class);

        $company = Company::factory()->create(['slug' => 'old-slug']);

        Livewire::test(EditCompany::class, ['record' => $company->getRouteKey()])
            ->fillForm([
                'slug' => 'new-slug',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('new-slug', $company->fresh()->slug);
    }

    public function test_creating_a_company_without_picking_a_plan_defaults_to_free(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();

        Livewire::test(CreateCompany::class)
            ->fillForm([
                'user_id' => $user->id,
                'slug' => 'acme-co',
                'review_status' => CompanyReviewStatus::PendingReview->value,
                'name' => ['en' => 'Acme Co', 'fa' => 'شرکت آکمی'],
                'description' => ['en' => 'About us', 'fa' => 'درباره ما'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $company = Company::sole();
        $freePlan = Plan::where('slug', 'free')->firstOrFail();

        $this->assertTrue($company->subscribedTo($freePlan->id));
    }

    public function test_creating_a_company_with_an_explicitly_picked_plan_activates_that_plan(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $proPlan = Plan::where('slug', 'pro-3-months')->firstOrFail();

        Livewire::test(CreateCompany::class)
            ->fillForm([
                'user_id' => $user->id,
                'slug' => 'acme-co',
                'review_status' => CompanyReviewStatus::PendingReview->value,
                'name' => ['en' => 'Acme Co', 'fa' => 'شرکت آکمی'],
                'description' => ['en' => 'About us', 'fa' => 'درباره ما'],
                'plan_id' => $proPlan->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $company = Company::sole();

        $this->assertTrue($company->subscribedTo($proPlan->id));
        // Only one subscription row: the observer's Free-plan safety net
        // must not have created a separate one before the form's plan
        // field ran.
        $this->assertSame(1, $company->planSubscriptions()->count());
    }

    public function test_editing_the_plan_field_switches_the_active_subscription(): void
    {
        $this->seed(PlanSeeder::class);

        $company = Company::factory()->create();
        app(CompanySubscriptionService::class)->assignFreePlanIfMissing($company);

        $proPlusPlan = Plan::where('slug', 'pro-plus-1-year')->firstOrFail();

        Livewire::test(EditCompany::class, ['record' => $company->getRouteKey()])
            ->fillForm(['plan_id' => $proPlusPlan->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $company->refresh();

        $this->assertTrue($company->subscribedTo($proPlusPlan->id));
        $this->assertSame(1, $company->planSubscriptions()->count());
    }

    public function test_the_plan_field_preloads_the_companys_current_active_plan(): void
    {
        $this->seed(PlanSeeder::class);

        $company = Company::factory()->create();
        $proPlan = Plan::where('slug', 'pro-6-months')->firstOrFail();
        app(CompanySubscriptionService::class)->switchToPlan($company, $proPlan);

        Livewire::test(EditCompany::class, ['record' => $company->getRouteKey()])
            ->assertFormSet(['plan_id' => $proPlan->id]);
    }

    public function test_approve_action_sets_review_fields_and_publishes_a_snapshot(): void
    {
        $company = Company::factory()->create([
            'review_status' => CompanyReviewStatus::PendingReview,
        ]);

        Livewire::test(ListCompanies::class)
            ->callAction(TestAction::make('approve')->table($company));

        $company->refresh();

        $this->assertSame(CompanyReviewStatus::Approved, $company->review_status);
        $this->assertNotNull($company->reviewed_at);

        $publication = CompanyPublication::where('company_id', $company->id)->sole();

        $this->assertSame($company->slug, $publication->slug);
        $this->assertSame(
            $company->getTranslation('name', 'fa', false),
            $publication->getTranslation('name', 'fa', false),
        );
    }

    public function test_approve_action_is_hidden_without_the_approve_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'ViewAny:Company', 'guard_name' => 'web']));
        $this->actingAs($user);

        $company = Company::factory()->create([
            'review_status' => CompanyReviewStatus::PendingReview,
        ]);

        Livewire::test(ListCompanies::class)
            ->assertActionHidden(TestAction::make('approve')->table($company));
    }

    public function test_republish_action_updates_the_published_snapshot(): void
    {
        $company = Company::factory()->create([
            'review_status' => CompanyReviewStatus::Approved,
            'name' => ['en' => 'First Name', 'fa' => 'نام اول'],
        ]);

        $publication = app(CompanyPublicationService::class)->publish($company);

        $company->update(['name' => ['en' => 'Renamed Company', 'fa' => 'نام جدید']]);

        Livewire::test(ListCompanies::class)
            ->callAction(TestAction::make('republish')->table($company));

        $publication->refresh();

        // The snapshot now carries the current draft data.
        $this->assertSame('Renamed Company', $publication->getTranslation('name', 'en', false));
        $this->assertSame($company->slug, $publication->fresh()->slug);
    }

    public function test_republish_action_is_hidden_for_a_company_without_a_publication(): void
    {
        $company = Company::factory()->create([
            'review_status' => CompanyReviewStatus::Approved,
        ]);

        Livewire::test(ListCompanies::class)
            ->assertActionHidden(TestAction::make('republish')->table($company));
    }

    public function test_republish_action_is_hidden_when_review_status_is_not_approved(): void
    {
        $company = Company::factory()->create([
            'review_status' => CompanyReviewStatus::PendingReview,
        ]);
        app(CompanyPublicationService::class)->publish($company);

        Livewire::test(ListCompanies::class)
            ->assertActionHidden(TestAction::make('republish')->table($company));
    }

    public function test_republish_action_is_hidden_without_the_approve_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'ViewAny:Company', 'guard_name' => 'web']));
        $this->actingAs($user);

        $company = Company::factory()->create([
            'review_status' => CompanyReviewStatus::Approved,
        ]);
        CompanyPublication::factory()->create(['company_id' => $company->id]);

        Livewire::test(ListCompanies::class)
            ->assertActionHidden(TestAction::make('republish')->table($company));
    }

    public function test_reject_action_sets_status(): void
    {
        $company = Company::factory()->create(['review_status' => CompanyReviewStatus::PendingReview]);

        Livewire::test(ListCompanies::class)
            ->callAction(TestAction::make('reject')->table($company));

        $company->refresh();

        $this->assertSame(CompanyReviewStatus::Rejected, $company->review_status);
        $this->assertNotNull($company->reviewed_at);
        $this->assertDatabaseMissing('company_publications', ['company_id' => $company->id]);
    }

    public function test_it_can_list_addresses_relation_manager(): void
    {
        $company = Company::factory()->create();
        $country = $this->makeCountry();

        $addresses = CompanyAddress::factory()->count(2)->create([
            'company_id' => $company->id,
            'country_id' => $country->id,
        ]);

        Livewire::test(AddressesRelationManager::class, [
            'ownerRecord' => $company,
            'pageClass' => EditCompany::class,
        ])->assertCanSeeTableRecords($addresses);
    }

    public function test_it_can_list_brands_relation_manager(): void
    {
        $company = Company::factory()->create();
        $brands = CompanyBrand::factory()->count(2)->create(['company_id' => $company->id]);

        Livewire::test(BrandsRelationManager::class, [
            'ownerRecord' => $company,
            'pageClass' => EditCompany::class,
        ])->assertCanSeeTableRecords($brands);
    }
}
