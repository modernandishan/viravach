<?php

namespace Tests\Feature\Dashboard;

use App\Enums\WordPressConnectionStatus;
use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use App\Models\WordPressContentPost;
use App\Services\CompanySubscriptionService;
use App\Settings\ContentSettings;
use App\Support\LocalizedDate;
use App\Support\WordPressContentQuota;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The WordPress content page is observer-only: generation is fully
 * automatic (scheduled), so the page shows the allowance, when the next
 * attempt lands, and the publish history — and must show no manual
 * trigger anywhere.
 */
class WordPressContentPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);
        app(ContentSettings::class)->fill(['enabled' => true])->save();
    }

    protected function connectedCompany(User $user): Company
    {
        return Company::factory()->for($user)->create([
            'website' => 'https://example.com',
            'wp_username' => 'admin',
            'wp_application_password' => 'abcd efgh ijkl mnop qrst uvwx',
            'wp_connection_status' => WordPressConnectionStatus::Connected,
        ]);
    }

    public function test_guest_is_redirected_away(): void
    {
        $this->get(route('wordpress-content'))->assertRedirect();
    }

    public function test_authenticated_user_can_view_the_page(): void
    {
        $user = User::factory()->create();
        Company::factory()->for($user)->create();

        $this->actingAs($user)->get(route('wordpress-content'))->assertOk();
    }

    public function test_it_forbids_opening_another_users_company(): void
    {
        $company = Company::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('wordpress-content', $company))
            ->assertForbidden();
    }

    public function test_an_unconnected_company_sees_the_not_connected_guard(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create([
            'wp_connection_status' => WordPressConnectionStatus::NotTested,
        ]);

        Livewire::actingAs($user)
            ->test('pages::dashboard.wordpress-content', ['company' => $company])
            ->assertSee(__('wordpress_content.guard_not_connected'))
            ->assertSee(__('wordpress_content.guard_go_to_settings'));
    }

    public function test_a_company_with_no_attempts_sees_the_tonight_schedule(): void
    {
        $user = User::factory()->create();
        $company = $this->connectedCompany($user);

        Livewire::actingAs($user)
            ->test('pages::dashboard.wordpress-content', ['company' => $company])
            ->assertSee(__('wordpress_content.schedule_tonight'))
            ->assertDontSee(__('wordpress_content.generate_button'));
    }

    public function test_a_company_within_the_cadence_window_sees_the_next_attempt_date(): void
    {
        $user = User::factory()->create();
        $company = $this->connectedCompany($user);

        // Pro, so the single post below stays inside the allowance and the
        // status line reflects the cadence rather than an exhausted quota.
        app(CompanySubscriptionService::class)->switchToPlan(
            $company,
            Plan::where('slug', 'pro-3-months')->firstOrFail(),
        );

        WordPressContentPost::factory()->create([
            'company_id' => $company->id,
            'created_at' => now()->subDay(),
        ]);

        $expected = __('wordpress_content.schedule_next_at', [
            'date' => LocalizedDate::format(now()->subDay()->addDays(3), LocalizedDate::FORMAT_DATE),
        ]);

        Livewire::actingAs($user)
            ->test('pages::dashboard.wordpress-content', ['company' => $company])
            ->assertSee($expected);
    }

    public function test_an_in_flight_generation_shows_the_in_progress_status(): void
    {
        $user = User::factory()->create();
        $company = $this->connectedCompany($user);

        WordPressContentPost::factory()->queued()->create([
            'company_id' => $company->id,
            'created_at' => now()->subDays(5),
        ]);

        Livewire::actingAs($user)
            ->test('pages::dashboard.wordpress-content', ['company' => $company])
            ->assertSee(__('wordpress_content.schedule_in_progress'));
    }

    public function test_an_exhausted_quota_shows_when_it_resets(): void
    {
        $user = User::factory()->create();
        $company = $this->connectedCompany($user);

        WordPressContentPost::factory()->create([
            'company_id' => $company->id,
            'created_at' => now()->subDays(5),
        ]);

        $expected = __('wordpress_content.schedule_quota_reached', [
            'date' => LocalizedDate::format(
                WordPressContentQuota::for($company)->resetsAt(),
                LocalizedDate::FORMAT_DATE,
            ),
        ]);

        Livewire::actingAs($user)
            ->test('pages::dashboard.wordpress-content', ['company' => $company])
            ->assertSee($expected);
    }

    public function test_the_history_table_lists_the_companys_posts(): void
    {
        $user = User::factory()->create();
        $company = $this->connectedCompany($user);
        WordPressContentPost::factory()->create([
            'company_id' => $company->id,
            'title' => 'A Generated Article',
            'wp_post_url' => 'https://example.com/a-generated-article',
        ]);

        Livewire::actingAs($user)
            ->test('pages::dashboard.wordpress-content', ['company' => $company])
            ->assertSee('A Generated Article')
            ->assertSee('https://example.com/a-generated-article')
            ->assertSee(__('wordpress_content.status_published'));
    }

    public function test_the_history_table_is_scoped_to_the_selected_company(): void
    {
        $user = User::factory()->create();
        $company = $this->connectedCompany($user);
        $other = Company::factory()->for($user)->create();

        WordPressContentPost::factory()->create(['company_id' => $other->id, 'title' => 'Other Companys Article']);

        Livewire::actingAs($user)
            ->test('pages::dashboard.wordpress-content', ['company' => $company])
            ->assertDontSee('Other Companys Article')
            ->assertSee(__('wordpress_content.history_empty'));
    }

    public function test_a_failed_posts_reason_is_shown_translated(): void
    {
        $user = User::factory()->create();
        $company = $this->connectedCompany($user);
        WordPressContentPost::factory()->failed('wordpress_content.publish_failed_authentication_failed')
            ->create(['company_id' => $company->id]);

        Livewire::actingAs($user)
            ->test('pages::dashboard.wordpress-content', ['company' => $company])
            ->assertSee(__('wordpress_content.publish_failed_authentication_failed'));
    }
}
