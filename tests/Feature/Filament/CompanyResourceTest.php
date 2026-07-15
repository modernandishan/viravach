<?php

namespace Tests\Feature\Filament;

use App\Enums\CompanyStatus;
use App\Filament\Resources\Companies\Pages\CreateCompany;
use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\Companies\RelationManagers\AddressesRelationManager;
use App\Filament\Resources\Companies\RelationManagers\BrandsRelationManager;
use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\CompanyBrand;
use App\Models\Country;
use App\Models\User;
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
            collect(['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny'])
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
        $user = User::factory()->create();

        Livewire::test(CreateCompany::class)
            ->fillForm([
                'user_id' => $user->id,
                'slug' => 'acme-co',
                'status' => CompanyStatus::Draft->value,
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
        $company = Company::factory()->create(['slug' => 'old-slug']);

        Livewire::test(EditCompany::class, ['record' => $company->getRouteKey()])
            ->fillForm([
                'slug' => 'new-slug',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('new-slug', $company->fresh()->slug);
    }

    public function test_approve_action_sets_status_and_published_at(): void
    {
        $company = Company::factory()->create([
            'status' => CompanyStatus::Pending,
            'published_at' => null,
        ]);

        Livewire::test(ListCompanies::class)
            ->callAction(TestAction::make('approve')->table($company));

        $company->refresh();

        $this->assertSame(CompanyStatus::Approved, $company->status);
        $this->assertNotNull($company->published_at);
    }

    public function test_reject_action_requires_reason_and_sets_status(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Pending]);

        Livewire::test(ListCompanies::class)
            ->callAction(TestAction::make('reject')->table($company), [
                'rejection_reason' => 'مدارک ناقص است',
            ]);

        $company->refresh();

        $this->assertSame(CompanyStatus::Rejected, $company->status);
        $this->assertSame('مدارک ناقص است', $company->rejection_reason);
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
