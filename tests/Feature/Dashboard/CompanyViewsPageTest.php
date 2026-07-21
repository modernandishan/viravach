<?php

namespace Tests\Feature\Dashboard;

use App\Models\Company;
use App\Models\CompanyPublication;
use App\Models\User;
use CyrildeWit\EloquentViewable\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyViewsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_away_from_the_company_views_page(): void
    {
        $this->get(route('company-views'))->assertRedirect();
    }

    public function test_it_shows_an_empty_state_when_the_user_has_no_companies(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.company-views')
            ->assertSeeText(__('companies.views_no_companies_found'));
    }

    public function test_it_only_lists_the_authenticated_users_companies(): void
    {
        $user = User::factory()->create();
        $mine = Company::factory()->for($user)->create(['name' => ['en' => 'Mine Trading Co', 'fa' => 'شرکت من']]);
        $others = Company::factory()->create(['name' => ['en' => 'Others Trading Co', 'fa' => 'شرکت دیگری']]);

        Livewire::actingAs($user)
            ->test('pages::dashboard.company-views')
            ->assertSeeText('Mine Trading Co')
            ->assertDontSeeText('Others Trading Co');
    }

    public function test_total_and_recent_view_counts_are_computed_correctly(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();

        // Public visits are recorded against the publication snapshot.
        $publication = CompanyPublication::factory()->create(['company_id' => $company->id]);

        // within the last 30 days
        View::create(['viewable_type' => CompanyPublication::class, 'viewable_id' => $publication->id, 'viewed_at' => now()->subDays(5)]);
        View::create(['viewable_type' => CompanyPublication::class, 'viewable_id' => $publication->id, 'viewed_at' => now()->subDays(10)]);
        // outside the 30-day window, should still count towards total
        View::create(['viewable_type' => CompanyPublication::class, 'viewable_id' => $publication->id, 'viewed_at' => now()->subDays(45)]);

        $rows = Livewire::actingAs($user)
            ->test('pages::dashboard.company-views')
            ->instance()
            ->rows();

        $row = $rows->firstWhere('company.id', $company->id);

        $this->assertSame(3, $row['total_views']);
        $this->assertSame(2, $row['recent_views']);
    }

    public function test_soft_deleted_companies_are_not_listed(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();
        $company->delete();

        Livewire::actingAs($user)
            ->test('pages::dashboard.company-views')
            ->assertSeeText(__('companies.views_no_companies_found'));
    }
}
