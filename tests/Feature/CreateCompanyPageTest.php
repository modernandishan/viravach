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
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_it_creates_a_company_and_auto_attaches_the_free_plan(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $category = CompanyCategory::factory()->create();
        $state = $this->makeState();

        Livewire::actingAs($user)
            ->test('pages::dashboard.create-company')
            ->set('name', 'Acme Trading Co')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('categoryIds', [$category->id])
            ->call('nextStep')
            ->assertSet('step', 3)
            ->set('stateId', $state->id)
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
            ->call('nextStep')
            ->assertSet('step', 2)
            ->call('nextStep')
            ->assertHasErrors(['categoryIds'])
            ->assertSet('step', 2);
    }

    public function test_it_sanitizes_the_description_html_before_saving(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $category = CompanyCategory::factory()->create();
        $state = $this->makeState();

        Livewire::actingAs($user)
            ->test('pages::dashboard.create-company')
            ->set('name', 'Acme Trading Co')
            ->set('description', '<p>Safe <strong>text</strong></p><script>alert(1)</script>')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('categoryIds', [$category->id])
            ->call('nextStep')
            ->assertSet('step', 3)
            ->set('stateId', $state->id)
            ->call('nextStep')
            ->assertSet('step', 4)
            ->call('nextStep')
            ->assertSet('step', 5)
            ->call('createCompany');

        $company = Company::where('user_id', $user->id)->firstOrFail();
        $description = $company->getTranslation('description', 'fa', false);

        $this->assertStringContainsString('<strong>text</strong>', $description);
        $this->assertStringNotContainsString('<script>', $description);
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
            ->call('nextStep')
            ->assertSet('step', 2)
            ->assertSee($child->title);
    }
}
