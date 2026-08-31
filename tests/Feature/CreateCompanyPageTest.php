<?php

namespace Tests\Feature;

use App\Enums\CompanyReviewStatus;
use App\Models\Company;
use App\Models\CompanyCategory;
use App\Models\Country;
use App\Models\Plan;
use App\Models\State;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class CreateCompanyPageTest extends TestCase
{
    use RefreshDatabase;

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

    private function validBrief(): string
    {
        return str_repeat('Acme designs and exports handwoven carpets. ', 3);
    }

    /**
     * Fills every wizard step and submits, for tests only interested in
     * whether createCompany() itself accepted or rejected the attempt.
     */
    private function submitWizard(User $user, CompanyCategory $category, State $state): Testable
    {
        return Livewire::actingAs($user)
            ->test('pages::dashboard.create-company')
            ->set('name', 'Second Trading Co')
            ->set('brief', $this->validBrief())
            ->call('nextStep')
            ->set('categoryIds', [$category->id])
            ->call('nextStep')
            ->set('addresses.0.state_id', $state->id)
            ->set('addresses.0.address_line', '123 Example Street')
            ->call('nextStep')
            ->call('nextStep')
            ->call('createCompany');
    }

    public function test_it_creates_a_company_and_auto_attaches_the_free_plan(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $category = CompanyCategory::factory()->create();
        $state = $this->makeState();

        Livewire::actingAs($user)
            ->test('pages::dashboard.create-company')
            ->set('name', 'Acme Trading Co')
            ->set('brief', $this->validBrief())
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('categoryIds', [$category->id])
            ->call('nextStep')
            ->assertSet('step', 3)
            ->set('addresses.0.state_id', $state->id)
            ->set('addresses.0.address_line', '123 Example Street')
            ->call('nextStep')
            ->assertSet('step', 4)
            ->call('nextStep')
            ->assertSet('step', 5)
            ->set('email', 'contact@acme.test')
            ->call('createCompany')
            ->assertRedirect(route('my-companies'));

        $company = Company::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('Acme Trading Co', $company->getTranslation('name', 'fa', false));
        $this->assertSame(CompanyReviewStatus::PendingReview, $company->review_status);
        $this->assertTrue($company->categories->contains($category));
        $this->assertSame($state->id, $company->primaryAddress->state_id);

        $freePlan = Plan::where('slug', 'free')->firstOrFail();
        $this->assertTrue($company->subscribedTo($freePlan->id));
    }

    public function test_the_website_bare_domain_is_normalized_and_stored_with_https(): void
    {
        $this->seed(PlanSeeder::class);
        $user = User::factory()->create();
        $category = CompanyCategory::factory()->create();
        $state = $this->makeState();

        Livewire::actingAs($user)
            ->test('pages::dashboard.create-company')
            ->set('name', 'Acme Trading Co')
            ->set('brief', $this->validBrief())
            ->call('nextStep')
            ->set('categoryIds', [$category->id])
            ->call('nextStep')
            ->set('addresses.0.state_id', $state->id)
            ->set('addresses.0.address_line', '123 Example Street')
            ->call('nextStep')
            ->call('nextStep')
            ->set('website', 'geosaz.com')
            ->call('createCompany')
            ->assertHasNoErrors();

        $company = Company::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('https://geosaz.com', $company->website);
    }

    public function test_the_website_prefixed_domain_is_stored_once_without_a_double_scheme(): void
    {
        $this->seed(PlanSeeder::class);
        $user = User::factory()->create();
        $category = CompanyCategory::factory()->create();
        $state = $this->makeState();

        Livewire::actingAs($user)
            ->test('pages::dashboard.create-company')
            ->set('name', 'Acme Trading Co')
            ->set('brief', $this->validBrief())
            ->call('nextStep')
            ->set('categoryIds', [$category->id])
            ->call('nextStep')
            ->set('addresses.0.state_id', $state->id)
            ->set('addresses.0.address_line', '123 Example Street')
            ->call('nextStep')
            ->call('nextStep')
            ->set('website', 'https://geosaz.com')
            ->call('createCompany')
            ->assertHasNoErrors();

        $company = Company::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('https://geosaz.com', $company->website);
    }

    public function test_the_website_garbage_input_is_rejected_with_the_localized_message(): void
    {
        $this->seed(PlanSeeder::class);
        $user = User::factory()->create();
        $category = CompanyCategory::factory()->create();
        $state = $this->makeState();

        Livewire::actingAs($user)
            ->test('pages::dashboard.create-company')
            ->set('name', 'Acme Trading Co')
            ->set('brief', $this->validBrief())
            ->call('nextStep')
            ->set('categoryIds', [$category->id])
            ->call('nextStep')
            ->set('addresses.0.state_id', $state->id)
            ->set('addresses.0.address_line', '123 Example Street')
            ->call('nextStep')
            ->call('nextStep')
            ->set('website', 'not a url at all')
            ->call('createCompany')
            ->assertHasErrors(['website']);

        $this->assertNull(Company::where('user_id', $user->id)->first());
    }

    public function test_it_rejects_advancing_past_step_one_without_a_required_name(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.create-company')
            ->call('nextStep')
            ->assertHasErrors(['name'])
            ->assertSet('step', 1);
    }

    public function test_it_rejects_advancing_past_category_step_without_a_selection(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.create-company')
            ->set('name', 'Acme Trading Co')
            ->set('brief', $this->validBrief())
            ->call('nextStep')
            ->assertSet('step', 2)
            ->call('nextStep')
            ->assertHasErrors(['categoryIds'])
            ->assertSet('step', 2);
    }

    public function test_it_rejects_a_brief_shorter_than_100_characters(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.create-company')
            ->set('name', 'Acme Trading Co')
            ->set('brief', str_repeat('a', 99))
            ->call('nextStep')
            ->assertHasErrors(['brief'])
            ->assertSet('step', 1);
    }

    public function test_a_successful_create_stores_the_brief_and_its_locale(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $category = CompanyCategory::factory()->create();
        $state = $this->makeState();
        $brief = str_repeat('Acme designs and exports handwoven carpets. ', 4);

        Livewire::actingAs($user)
            ->test('pages::dashboard.create-company')
            ->set('name', 'Acme Trading Co')
            ->set('brief', $brief)
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('categoryIds', [$category->id])
            ->call('nextStep')
            ->assertSet('step', 3)
            ->set('addresses.0.state_id', $state->id)
            ->set('addresses.0.address_line', '123 Example Street')
            ->call('nextStep')
            ->assertSet('step', 4)
            ->call('nextStep')
            ->assertSet('step', 5)
            ->call('createCompany')
            ->assertRedirect(route('my-companies'));

        $company = Company::where('user_id', $user->id)->firstOrFail();

        $this->assertSame($brief, $company->brief);
        $this->assertSame(app()->getLocale(), $company->brief_locale);
    }

    public function test_it_renders_categories_nested_three_levels_deep(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $grandparent = CompanyCategory::factory()->create(['parent_id' => null]);
        $parent = CompanyCategory::factory()->create(['parent_id' => $grandparent->id]);
        $child = CompanyCategory::factory()->create(['parent_id' => $parent->id, 'title' => ['en' => 'Deeply Nested Category']]);

        Livewire::actingAs($user)
            ->test('pages::dashboard.create-company')
            ->set('name', 'Acme Trading Co')
            ->set('brief', $this->validBrief())
            ->call('nextStep')
            ->assertSet('step', 2)
            ->assertSee($child->title);
    }

    public function test_creating_a_second_company_within_48_hours_is_rejected_even_when_the_first_was_soft_deleted(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $category = CompanyCategory::factory()->create();
        $state = $this->makeState();

        $first = Company::factory()->for($user)->create(['created_at' => now()->subHours(10)]);
        $first->delete();

        $this->submitWizard($user, $category, $state)->assertHasErrors(['cooldown']);

        $this->assertSame(1, Company::withTrashed()->where('user_id', $user->id)->count());
    }

    public function test_creating_a_company_after_48_hours_succeeds(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $category = CompanyCategory::factory()->create();
        $state = $this->makeState();

        Company::factory()->for($user)->create(['created_at' => now()->subHours(49)]);

        $this->submitWizard($user, $category, $state)
            ->assertHasNoErrors()
            ->assertRedirect(route('my-companies'));

        $this->assertSame(2, Company::where('user_id', $user->id)->count());
    }

    public function test_an_admin_user_is_exempt_from_the_creation_cooldown(): void
    {
        $this->seed(PlanSeeder::class);
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');
        $category = CompanyCategory::factory()->create();
        $state = $this->makeState();

        Company::factory()->for($user)->create(['created_at' => now()->subHour()]);

        $this->submitWizard($user, $category, $state)
            ->assertHasNoErrors()
            ->assertRedirect(route('my-companies'));

        $this->assertSame(2, Company::where('user_id', $user->id)->count());
    }
}
