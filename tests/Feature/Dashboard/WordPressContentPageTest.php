<?php

namespace Tests\Feature\Dashboard;

use App\Enums\ContentGenerationMode;
use App\Enums\WordPressConnectionStatus;
use App\Jobs\WordPress\GenerateWordPressPostContent;
use App\Jobs\WordPress\GenerateWordPressPostImage;
use App\Jobs\WordPress\PublishWordPressPost;
use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use App\Models\WordPressContentPost;
use App\Services\CompanySubscriptionService;
use App\Settings\ContentSettings;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

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

    public function test_an_unconnected_company_sees_the_not_connected_guard_and_no_generate_form(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create([
            'wp_connection_status' => WordPressConnectionStatus::NotTested,
        ]);

        Livewire::actingAs($user)
            ->test('pages::dashboard.wordpress-content', ['company' => $company])
            ->assertSee(__('wordpress_content.guard_not_connected'))
            ->assertDontSee(__('wordpress_content.generate_button'));
    }

    public function test_a_connected_company_can_request_a_generation(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $company = $this->connectedCompany($user);

        Livewire::actingAs($user)
            ->test('pages::dashboard.wordpress-content', ['company' => $company])
            ->set('locale', 'fa')
            ->set('mode', ContentGenerationMode::Industry->value)
            ->call('generate')
            ->assertSet('generateFailureReason', null)
            ->assertSee(__('wordpress_content.generation_queued'));

        $this->assertSame(1, $company->wordPressContentPosts()->count());
        Bus::assertChained([
            GenerateWordPressPostContent::class,
            GenerateWordPressPostImage::class,
            PublishWordPressPost::class,
        ]);
    }

    public function test_an_exhausted_quota_shows_its_own_guard_message_and_disables_the_button(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $company = $this->connectedCompany($user);
        WordPressContentPost::factory()->create(['company_id' => $company->id]);

        $component = Livewire::actingAs($user)
            ->test('pages::dashboard.wordpress-content', ['company' => $company])
            ->call('generate');

        $component->assertSet('generateFailureReason', 'quota_exhausted')
            ->assertSee(__('wordpress_content.guard_quota_exhausted'));

        // The refusal itself must not have queued a second row.
        $this->assertSame(1, $company->wordPressContentPosts()->count());
        Bus::assertNothingDispatched();
    }

    public function test_a_generation_already_in_progress_blocks_a_second_request(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $company = $this->connectedCompany($user);

        // Pro, not Free, so the in-progress guard is what triggers here —
        // not the (also true) fact that Free's single monthly slot is taken.
        app(CompanySubscriptionService::class)->switchToPlan(
            $company,
            Plan::where('slug', 'pro-3-months')->firstOrFail(),
        );

        WordPressContentPost::factory()->queued()->create(['company_id' => $company->id]);

        Livewire::actingAs($user)
            ->test('pages::dashboard.wordpress-content', ['company' => $company])
            ->call('generate')
            ->assertSet('generateFailureReason', 'already_running')
            ->assertSee(__('wordpress_content.guard_already_running'));

        Bus::assertNothingDispatched();
    }

    public function test_generation_disabled_globally_blocks_the_request(): void
    {
        Bus::fake();
        app(ContentSettings::class)->fill(['enabled' => false])->save();

        $user = User::factory()->create();
        $company = $this->connectedCompany($user);

        Livewire::actingAs($user)
            ->test('pages::dashboard.wordpress-content', ['company' => $company])
            ->call('generate')
            ->assertSet('generateFailureReason', 'generation_disabled')
            ->assertSee(__('wordpress_content.guard_generation_disabled'));

        Bus::assertNothingDispatched();
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
