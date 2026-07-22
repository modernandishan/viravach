<?php

namespace Tests\Feature;

use App\Enums\CompanyReviewStatus;
use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\CompanyCategory;
use App\Models\CompanyPublication;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Services\CompanyPublicationService;
use Database\Seeders\PlanSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CompanyReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function actAsAdmin(): void
    {
        $user = User::factory()->create();

        $user->givePermissionTo(
            collect(['ViewAny', 'View', 'Create', 'Update', 'Delete', 'Approve', 'Reject'])
                ->map(fn (string $ability) => Permission::firstOrCreate([
                    'name' => "{$ability}:Company",
                    'guard_name' => 'web',
                ]))
        );

        $this->actingAs($user);
    }

    private function makeState(): State
    {
        $country = Country::create([
            'name' => ['en' => 'Iran'],
            'official_name' => ['en' => 'Islamic Republic of Iran'],
            'capital' => ['en' => 'Tehran'],
            'currency_name' => ['en' => 'Rial'],
            'slug' => 'iran-'.uniqid(),
            'phone_code' => '98',
            'currency' => 'IRR',
            'currency_symbol' => 'IRR',
            'is_active' => true,
        ]);

        return State::create([
            'country_id' => $country->id,
            'name' => ['en' => 'Tehran'],
            'type' => ['en' => 'Province'],
            'slug' => 'tehran-'.uniqid(),
            'code' => 'THR',
            'is_active' => true,
        ]);
    }

    public function test_admin_edit_page_does_not_create_or_update_a_publication(): void
    {
        $this->seed(PlanSeeder::class);
        $this->actAsAdmin();

        $unpublished = Company::factory()->create(['slug' => 'unpublished-co']);

        $published = Company::factory()->approved()->create(['slug' => 'published-co']);
        $publication = app(CompanyPublicationService::class)->publish($published);

        Livewire::test(EditCompany::class, ['record' => $unpublished->getRouteKey()])
            ->fillForm(['slug' => 'unpublished-co-renamed'])
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::test(EditCompany::class, ['record' => $published->getRouteKey()])
            ->fillForm(['slug' => 'published-co-renamed'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('unpublished-co-renamed', $unpublished->fresh()->slug);
        $this->assertSame('published-co-renamed', $published->fresh()->slug);

        // No snapshot was created for the unpublished company, and the
        // existing snapshot kept serving the previously approved data.
        $this->assertDatabaseMissing('company_publications', ['company_id' => $unpublished->id]);
        $this->assertSame(1, CompanyPublication::count());
        $this->assertSame('published-co', $publication->fresh()->slug);
    }

    public function test_dashboard_edit_resets_review_status_and_leaves_the_publication_untouched(): void
    {
        $user = User::factory()->create();
        $category = CompanyCategory::factory()->create();
        $state = $this->makeState();

        $company = Company::factory()->for($user)->approved()->create([
            'name' => ['en' => 'Approved Name', 'fa' => 'نام تأییدشده'],
        ]);
        $company->categories()->attach($category);
        $company->addresses()->create([
            'country_id' => $state->country_id,
            'state_id' => $state->id,
            'type' => 'office',
            'address_line' => ['en' => 'Original street 1'],
            'is_primary' => true,
        ]);

        $publication = app(CompanyPublicationService::class)->publish($company);

        Livewire::actingAs($user)
            ->test('pages::dashboard.edit-company', ['company' => $company->id])
            ->set('name.en', 'Edited Draft Name')
            ->call('updateCompany')
            ->assertHasNoErrors()
            ->assertRedirect(route('my-companies'));

        $this->assertSame(CompanyReviewStatus::PendingReview, $company->fresh()->review_status);
        $this->assertSame('Approved Name', $publication->fresh()->getTranslation('name', 'en'));
    }

    public function test_create_wizard_persists_multiple_address_rows_with_a_single_primary(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $category = CompanyCategory::factory()->create();
        $firstState = $this->makeState();
        $secondState = $this->makeState();

        Livewire::actingAs($user)
            ->test('pages::dashboard.create-company')
            ->set('name', 'Acme Trading Co')
            ->set('categoryIds', [$category->id])
            ->set('addresses', [
                [
                    'state_id' => $firstState->id,
                    'city_id' => null,
                    'type' => 'office',
                    'address_line' => 'First street 1',
                    'postal_code' => null,
                    'is_primary' => false,
                ],
                [
                    'state_id' => $secondState->id,
                    'city_id' => null,
                    'type' => 'warehouse',
                    'address_line' => 'Second street 2',
                    'postal_code' => null,
                    'is_primary' => true,
                ],
            ])
            ->call('createCompany')
            ->assertHasNoErrors()
            ->assertRedirect(route('my-companies'));

        $company = Company::where('user_id', $user->id)->firstOrFail();
        $addresses = CompanyAddress::where('company_id', $company->id)->get();

        $this->assertCount(2, $addresses);
        $this->assertSame(1, $addresses->where('is_primary', true)->count());
        $this->assertTrue($addresses->firstWhere('is_primary', true)->state_id === $secondState->id);
        $this->assertSame(
            [$firstState->country_id, $secondState->country_id],
            $addresses->sortBy('state_id')->pluck('country_id')->values()->all(),
        );
    }

    public function test_reject_action_works_without_a_reason_and_the_column_is_gone(): void
    {
        $this->actAsAdmin();

        $this->assertFalse(Schema::hasColumn('companies', 'rejection_reason'));

        $company = Company::factory()->create(['review_status' => CompanyReviewStatus::PendingReview]);

        Livewire::test(ListCompanies::class)
            ->callAction(TestAction::make('reject')->table($company));

        $company->refresh();

        $this->assertSame(CompanyReviewStatus::Rejected, $company->review_status);
        $this->assertNotNull($company->reviewed_at);
        $this->assertDatabaseMissing('company_publications', ['company_id' => $company->id]);
    }
}
