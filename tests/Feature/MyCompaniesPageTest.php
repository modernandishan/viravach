<?php

namespace Tests\Feature;

use App\Enums\CompanyReviewStatus;
use App\Models\Company;
use App\Models\User;
use App\Support\LocalizedDate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MyCompaniesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_the_authenticated_users_companies_of_every_status(): void
    {
        $user = User::factory()->create();

        $pending = Company::factory()->for($user)->create(['review_status' => CompanyReviewStatus::PendingReview]);
        $approved = Company::factory()->for($user)->approved()->create();
        $others = Company::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.my-companies')
            ->assertSeeText($pending->name)
            ->assertSeeText($approved->name)
            ->assertDontSeeText($others->name);
    }

    public function test_it_hides_soft_deleted_companies(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();
        $company->delete();

        Livewire::actingAs($user)
            ->test('pages::dashboard.my-companies')
            ->assertDontSeeText($company->name);
    }

    public function test_it_soft_deletes_a_company_via_the_confirm_modal_flow(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.my-companies')
            ->call('confirmDelete', $company->id)
            ->call('delete')
            ->assertSeeText(__('companies.deleted_successfully'));

        $this->assertSoftDeleted($company);
    }

    public function test_it_does_not_allow_deleting_another_users_company(): void
    {
        $user = User::factory()->create();
        $other = Company::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($user)
            ->test('pages::dashboard.my-companies')
            ->call('confirmDelete', $other->id)
            ->call('delete');

        $this->assertDatabaseHas('companies', ['id' => $other->id, 'deleted_at' => null]);
    }

    public function test_the_create_button_is_disabled_during_the_cooldown_window(): void
    {
        $user = User::factory()->create();
        Company::factory()->for($user)->create(['created_at' => now()->subHour()]);

        $cooldownEndsAt = Company::creationCooldownEndsAt($user->id);
        $this->assertNotNull($cooldownEndsAt);

        Livewire::actingAs($user)
            ->test('pages::dashboard.my-companies')
            ->assertSee(__('companies.company_creation_cooldown', [
                'time' => LocalizedDate::format($cooldownEndsAt, LocalizedDate::FORMAT_DATETIME),
            ]))
            ->assertDontSeeHtml('href="'.route('create.company').'"');
    }

    public function test_search_filters_by_translated_name(): void
    {
        $user = User::factory()->create();
        $match = Company::factory()->for($user)->create(['name' => ['en' => 'Persian Rugs Co', 'fa' => 'فرش ایرانی']]);
        $noMatch = Company::factory()->for($user)->create(['name' => ['en' => 'Other Traders', 'fa' => 'سایر']]);

        Livewire::actingAs($user)
            ->test('pages::dashboard.my-companies')
            ->set('search', 'Persian')
            ->assertSeeText($match->name)
            ->assertDontSeeText($noMatch->name);
    }
}
