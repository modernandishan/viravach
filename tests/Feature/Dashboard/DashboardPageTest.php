<?php

namespace Tests\Feature\Dashboard;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use CyrildeWit\EloquentViewable\View;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_away_from_the_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect();
    }

    public function test_it_shows_an_empty_state_when_the_user_has_no_companies(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard')
            ->assertSeeText(__('dashboard.empty_no_companies_title'));
    }

    public function test_stats_are_scoped_to_the_authenticated_users_companies_only(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $mine = Company::factory()->for($user)->create();
        $others = Company::factory()->create();

        View::create(['viewable_type' => Company::class, 'viewable_id' => $mine->id, 'viewed_at' => now()]);
        View::create(['viewable_type' => Company::class, 'viewable_id' => $mine->id, 'viewed_at' => now()->subDay()]);
        View::create(['viewable_type' => Company::class, 'viewable_id' => $others->id, 'viewed_at' => now()]);

        Invoice::factory()->for($user)->for($mine)->create(['status' => InvoiceStatus::Paid, 'amount' => 1_000_000]);
        Invoice::factory()->for($user)->for($mine)->create(['status' => InvoiceStatus::Pending, 'amount' => 500_000]);
        Invoice::factory()->create(['status' => InvoiceStatus::Paid, 'amount' => 9_000_000]);

        $component = Livewire::actingAs($user)->test('pages::dashboard');

        $stats = $component->instance()->stats();

        $this->assertSame(1, $stats['companies']);
        $this->assertSame(2, $stats['total_views']);
        $this->assertSame(1_000_000, $stats['total_spent']);
    }

    public function test_soft_deleted_companies_are_excluded_from_stats(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();
        $company->delete();

        $component = Livewire::actingAs($user)->test('pages::dashboard');

        $this->assertSame(0, $component->instance()->stats()['companies']);
    }

    public function test_top_companies_by_views_are_ranked_and_scoped_to_the_user(): void
    {
        $user = User::factory()->create();
        $topCompany = Company::factory()->for($user)->create();
        $lowCompany = Company::factory()->for($user)->create();
        $others = Company::factory()->create();

        View::create(['viewable_type' => Company::class, 'viewable_id' => $topCompany->id, 'viewed_at' => now()]);
        View::create(['viewable_type' => Company::class, 'viewable_id' => $topCompany->id, 'viewed_at' => now()]);
        View::create(['viewable_type' => Company::class, 'viewable_id' => $lowCompany->id, 'viewed_at' => now()]);
        View::create(['viewable_type' => Company::class, 'viewable_id' => $others->id, 'viewed_at' => now()]);
        View::create(['viewable_type' => Company::class, 'viewable_id' => $others->id, 'viewed_at' => now()]);
        View::create(['viewable_type' => Company::class, 'viewable_id' => $others->id, 'viewed_at' => now()]);

        $component = Livewire::actingAs($user)->test('pages::dashboard');

        $ranked = $component->instance()->topCompaniesByViews();

        $this->assertSame($topCompany->id, $ranked->first()['company']->id);
        $this->assertSame(2, $ranked->first()['views']);
        $this->assertCount(2, $ranked);
    }

    public function test_latest_invoices_are_scoped_to_the_authenticated_user(): void
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $mineCompany = Company::factory()->for($user)->create(['name' => ['en' => 'Mine Trading Co', 'fa' => 'شرکت من']]);
        $othersCompany = Company::factory()->create(['name' => ['en' => 'Others Trading Co', 'fa' => 'شرکت دیگری']]);

        Invoice::factory()->for($user)->for($mineCompany)->create();
        Invoice::factory()->for($othersCompany)->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard')
            ->assertSeeText('Mine Trading Co')
            ->assertDontSeeText('Others Trading Co');
    }
}
