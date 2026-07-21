<?php

namespace Tests\Feature;

use App\Enums\CompanyReviewStatus;
use App\Models\Company;
use App\Models\CompanyCategory;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Services\CompanyPublicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditCompanyPageTest extends TestCase
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

    public function test_it_403s_for_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->for($owner)->create();
        $intruder = User::factory()->create();

        Livewire::actingAs($intruder)
            ->test('pages::dashboard.edit-company', ['company' => $company->id])
            ->assertForbidden();
    }

    public function test_it_prefills_and_updates_the_companys_fields(): void
    {
        $user = User::factory()->create();
        $originalCategory = CompanyCategory::factory()->create();
        $newCategory = CompanyCategory::factory()->create();
        $originalState = $this->makeState();
        $newState = $this->makeState();

        $company = Company::factory()->for($user)->create([
            'name' => ['en' => 'Old Name', 'fa' => 'نام قدیمی'],
            'email' => 'old@example.com',
        ]);
        $company->categories()->attach($originalCategory);
        $company->addresses()->create([
            'country_id' => $originalState->country_id,
            'state_id' => $originalState->id,
            'type' => 'office',
            'address_line' => ['en' => ''],
            'is_primary' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::dashboard.edit-company', ['company' => $company->id])
            ->assertSet('name.en', 'Old Name')
            ->assertSet('email', 'old@example.com')
            ->set('name.en', 'New Name')
            ->set('categoryIds', [$newCategory->id])
            ->set('stateId', $newState->id)
            ->set('email', 'new@example.com')
            ->call('updateCompany')
            ->assertRedirect(route('my-companies'));

        $company->refresh();

        $this->assertSame('New Name', $company->getTranslation('name', 'en', false));
        $this->assertSame('new@example.com', $company->email);
        $this->assertTrue($company->categories()->get()->pluck('id')->contains($newCategory->id));
        $this->assertFalse($company->categories()->get()->pluck('id')->contains($originalCategory->id));
        $this->assertSame($newState->id, $company->primaryAddress()->first()->state_id);
    }

    public function test_it_sanitizes_the_description_html_before_saving(): void
    {
        $user = User::factory()->create();
        $category = CompanyCategory::factory()->create();
        $state = $this->makeState();

        $company = Company::factory()->for($user)->create();
        $company->categories()->attach($category);
        $company->addresses()->create([
            'country_id' => $state->country_id,
            'state_id' => $state->id,
            'type' => 'office',
            'address_line' => ['en' => ''],
            'is_primary' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::dashboard.edit-company', ['company' => $company->id])
            ->set('description.fa', '<p>Safe <strong>text</strong></p><script>alert(1)</script>')
            ->call('updateCompany')
            ->assertRedirect(route('my-companies'));

        $description = $company->fresh()->getTranslation('description', 'fa', false);

        $this->assertStringContainsString('<strong>text</strong>', $description);
        $this->assertStringNotContainsString('<script>', $description);
    }

    public function test_editing_reviewed_fields_resets_status_to_pending_and_keeps_the_publication(): void
    {
        $user = User::factory()->create();
        $category = CompanyCategory::factory()->create();
        $state = $this->makeState();

        $company = Company::factory()->for($user)->approved()->create([
            'name' => ['en' => 'Approved Name', 'fa' => 'نام تأییدشده'],
            'rejection_reason' => null,
        ]);
        $company->categories()->attach($category);
        $company->addresses()->create([
            'country_id' => $state->country_id,
            'state_id' => $state->id,
            'type' => 'office',
            'address_line' => ['en' => ''],
            'is_primary' => true,
        ]);

        $publication = app(CompanyPublicationService::class)->publish($company);

        Livewire::actingAs($user)
            ->test('pages::dashboard.edit-company', ['company' => $company->id])
            ->set('name.en', 'Edited Draft Name')
            ->call('updateCompany')
            ->assertHasNoErrors()
            ->assertRedirect(route('my-companies'));

        $company->refresh();
        $publication->refresh();

        $this->assertSame(CompanyReviewStatus::PendingReview, $company->review_status);
        // The public snapshot must keep serving the approved version.
        $this->assertSame('Approved Name', $publication->getTranslation('name', 'en'));
    }

    public function test_saving_without_reviewed_changes_keeps_the_approved_status(): void
    {
        $user = User::factory()->create();
        $category = CompanyCategory::factory()->create();
        $state = $this->makeState();

        $company = Company::factory()->for($user)->approved()->create();
        $company->categories()->attach($category);
        $company->addresses()->create([
            'country_id' => $state->country_id,
            'state_id' => $state->id,
            'type' => 'office',
            'address_line' => ['en' => ''],
            'is_primary' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages::dashboard.edit-company', ['company' => $company->id])
            ->call('updateCompany')
            ->assertRedirect(route('my-companies'));

        $this->assertSame(CompanyReviewStatus::Approved, $company->fresh()->review_status);
    }

    public function test_it_renders_categories_nested_three_levels_deep(): void
    {
        $user = User::factory()->create();
        $category = CompanyCategory::factory()->create();
        $state = $this->makeState();

        $company = Company::factory()->for($user)->create();
        $company->categories()->attach($category);
        $company->addresses()->create([
            'country_id' => $state->country_id,
            'state_id' => $state->id,
            'type' => 'office',
            'address_line' => ['en' => ''],
            'is_primary' => true,
        ]);

        $grandparent = CompanyCategory::factory()->create(['parent_id' => null]);
        $parent = CompanyCategory::factory()->create(['parent_id' => $grandparent->id]);
        $child = CompanyCategory::factory()->create(['parent_id' => $parent->id, 'title' => ['en' => 'Deeply Nested Category']]);

        Livewire::actingAs($user)
            ->test('pages::dashboard.edit-company', ['company' => $company->id])
            ->assertSee($child->title);
    }

    public function test_the_category_picker_pre_expands_the_ancestor_path_of_the_selection(): void
    {
        $user = User::factory()->create();
        $state = $this->makeState();

        $grandparent = CompanyCategory::factory()->create(['parent_id' => null]);
        $parent = CompanyCategory::factory()->create(['parent_id' => $grandparent->id]);
        $child = CompanyCategory::factory()->create(['parent_id' => $parent->id]);
        $unrelated = CompanyCategory::factory()->create(['parent_id' => null]);

        $company = Company::factory()->for($user)->create();
        $company->categories()->attach($child);
        $company->addresses()->create([
            'country_id' => $state->country_id,
            'state_id' => $state->id,
            'type' => 'office',
            'address_line' => ['en' => ''],
            'is_primary' => true,
        ]);

        $component = Livewire::actingAs($user)
            ->test('pages::dashboard.edit-company', ['company' => $company->id]);

        $this->assertEqualsCanonicalizing(
            [$grandparent->id, $parent->id],
            $component->instance()->expandedCategoryIds(),
        );
    }
}
