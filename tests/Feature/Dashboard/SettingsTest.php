<?php

namespace Tests\Feature\Dashboard;

use App\Enums\CompanyReviewStatus;
use App\Enums\ContentGenerationMode;
use App\Enums\SeoPlugin;
use App\Enums\WordPressConnectionStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_away_from_the_settings_page(): void
    {
        $this->get(route('settings'))->assertRedirect();
    }

    public function test_authenticated_user_can_view_the_settings_page(): void
    {
        $user = User::factory()->create();
        Company::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('settings'))
            ->assertOk();
    }

    public function test_page_loads_without_any_company_and_offers_to_create_one(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('settings'))
            ->assertOk()
            ->assertSee(__('subscriptions.no_companies_notice'))
            ->assertSee(route('create.company'));
    }

    public function test_it_defaults_to_the_first_company_when_no_route_segment_is_given(): void
    {
        $user = User::factory()->create();
        $first = Company::factory()->for($user)->create();
        Company::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings')
            ->assertSet('selectedCompanyId', $first->id);
    }

    public function test_the_company_route_segment_selects_that_company(): void
    {
        $user = User::factory()->create();
        Company::factory()->for($user)->create();
        $second = Company::factory()->for($user)->create(['website' => 'https://second.example']);

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $second])
            ->assertSet('selectedCompanyId', $second->id)
            ->assertSet('website', 'second.example');
    }

    public function test_it_forbids_opening_another_users_company(): void
    {
        $company = Company::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('settings', $company))
            ->assertForbidden();
    }

    public function test_switching_the_selector_reloads_that_companys_values(): void
    {
        $user = User::factory()->create();
        $first = Company::factory()->for($user)->create([
            'website' => 'https://first.example',
            'seo_plugin' => SeoPlugin::Yoast,
        ]);
        $second = Company::factory()->for($user)->create([
            'website' => 'https://second.example',
            'seo_plugin' => SeoPlugin::RankMath,
        ]);

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings')
            ->assertSet('selectedCompanyId', $first->id)
            ->assertSet('website', 'first.example')
            ->assertSet('seoPlugin', SeoPlugin::Yoast->value)
            ->set('selectedCompanyId', $second->id)
            ->assertSet('website', 'second.example')
            ->assertSet('seoPlugin', SeoPlugin::RankMath->value);
    }

    public function test_it_saves_every_field_against_the_selected_company(): void
    {
        $user = User::factory()->create();
        $other = Company::factory()->for($user)->create();
        $target = Company::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $target])
            ->set('website', 'example.com')
            ->set('seoPlugin', SeoPlugin::RankMath->value)
            ->set('wpUsername', 'site-admin')
            ->set('wpApplicationPassword', 'abcd efgh ijkl mnop qrst uvwx')
            ->set('contentGenerationMode', ContentGenerationMode::Trending->value)
            ->call('save')
            ->assertHasNoErrors()
            // The stored value keeps its scheme, the input keeps only the domain.
            ->assertSet('website', 'example.com');

        $target->refresh();

        $this->assertSame('https://example.com', $target->website);
        $this->assertSame(SeoPlugin::RankMath, $target->seo_plugin);
        $this->assertSame('site-admin', $target->wp_username);
        $this->assertSame('abcd efgh ijkl mnop qrst uvwx', $target->wp_application_password);
        $this->assertSame(ContentGenerationMode::Trending, $target->content_generation_mode);

        // The other company is untouched.
        $other->refresh();
        $this->assertNull($other->website);
        $this->assertNull($other->seo_plugin);
    }

    public function test_the_application_password_is_encrypted_at_rest(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->set('seoPlugin', SeoPlugin::Yoast->value)
            ->set('contentGenerationMode', ContentGenerationMode::Industry->value)
            ->set('wpApplicationPassword', 'abcd efgh ijkl mnop qrst uvwx')
            ->call('save')
            ->assertHasNoErrors();

        $stored = DB::table('companies')->where('id', $company->id)->value('wp_application_password');

        $this->assertNotSame('abcd efgh ijkl mnop qrst uvwx', $stored);
        $this->assertSame('abcd efgh ijkl mnop qrst uvwx', Crypt::decryptString($stored));
    }

    public function test_a_pasted_scheme_is_stripped_before_the_prefix_is_reapplied(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->set('seoPlugin', SeoPlugin::Yoast->value)
            ->set('contentGenerationMode', ContentGenerationMode::Industry->value)
            ->set('website', 'http://example.com')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('https://example.com', $company->refresh()->website);
    }

    public function test_changing_the_website_sends_the_draft_back_to_review(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create([
            'review_status' => CompanyReviewStatus::Approved,
        ]);

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->set('seoPlugin', SeoPlugin::Yoast->value)
            ->set('contentGenerationMode', ContentGenerationMode::Industry->value)
            ->set('website', 'example.com')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(CompanyReviewStatus::PendingReview, $company->refresh()->review_status);
    }

    public function test_operator_only_settings_leave_the_review_status_alone(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create([
            'review_status' => CompanyReviewStatus::Approved,
        ]);

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->set('seoPlugin', SeoPlugin::Yoast->value)
            ->set('wpUsername', 'site-admin')
            ->set('wpApplicationPassword', 'abcd efgh ijkl mnop qrst uvwx')
            ->set('contentGenerationMode', ContentGenerationMode::Industry->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(CompanyReviewStatus::Approved, $company->refresh()->review_status);
    }

    public function test_the_wordpress_username_is_loaded_back_into_the_form(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create(['wp_username' => 'site-admin']);

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->assertSet('wpUsername', 'site-admin')
            // Storing credentials alone never claims a working connection.
            ->assertSee(__('settings.wp_status_not_tested'));

        $this->assertSame(WordPressConnectionStatus::NotTested, $company->refresh()->wp_connection_status);
    }

    /**
     * @return Company The company whose saved settings the form starts out matching.
     */
    protected function companyWithSavedConnection(User $user): Company
    {
        return Company::factory()->for($user)->create([
            'website' => 'https://example.com',
            'wp_username' => 'site-admin',
            'wp_application_password' => 'abcd efgh ijkl mnop qrst uvwx',
        ]);
    }

    public function test_testing_the_connection_with_an_unsaved_username_asks_the_user_to_save_first(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $company = $this->companyWithSavedConnection($user);

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->set('wpUsername', 'someone-else')
            ->call('testConnection')
            ->assertSet('connectionNeedsSave', true)
            ->assertSet('connectionFailureReason', null)
            ->assertSee(__('settings.wp_test_unsaved_changes'));

        // Nothing was tested, so nothing may be claimed about the connection.
        Http::assertNothingSent();

        $company->refresh();
        $this->assertSame(WordPressConnectionStatus::NotTested, $company->wp_connection_status);
        $this->assertNull($company->wp_last_checked_at);
    }

    public function test_testing_the_connection_with_an_unsaved_website_asks_the_user_to_save_first(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $company = $this->companyWithSavedConnection($user);

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->set('website', 'other.example')
            ->call('testConnection')
            ->assertSet('connectionNeedsSave', true)
            ->assertSee(__('settings.wp_test_unsaved_changes'));

        Http::assertNothingSent();
    }

    public function test_testing_the_connection_with_an_unsaved_application_password_asks_the_user_to_save_first(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $company = $this->companyWithSavedConnection($user);

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->set('wpApplicationPassword', 'zzzz zzzz zzzz zzzz zzzz zzzz')
            ->call('testConnection')
            ->assertSet('connectionNeedsSave', true)
            ->assertSee(__('settings.wp_test_unsaved_changes'));

        Http::assertNothingSent();
    }

    /**
     * The website input drops the https:// prefix, so an untouched form must
     * not read as an edit just because of that.
     */
    public function test_an_untouched_form_still_runs_the_connection_test(): void
    {
        Http::fake([
            'example.com/wp-json/' => Http::response([
                'name' => 'Example Site',
                'namespaces' => ['wp/v2'],
            ]),
            'example.com/wp-json/wp/v2/users/me*' => Http::response(['id' => 7, 'name' => 'Site Owner']),
            'example.com/wp-json/wp/v2/posts*' => Http::response([]),
        ]);

        $user = User::factory()->create();
        $company = $this->companyWithSavedConnection($user);

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->call('testConnection')
            ->assertSet('connectionNeedsSave', false)
            ->assertSee(__('settings.wp_test_success_title'))
            ->assertDontSee(__('settings.wp_test_unsaved_changes'));

        $this->assertSame(WordPressConnectionStatus::Connected, $company->refresh()->wp_connection_status);
    }

    public function test_saving_clears_the_save_first_notice_so_the_test_can_run(): void
    {
        Http::fake([
            'example.com/wp-json/' => Http::response([
                'name' => 'Example Site',
                'namespaces' => ['wp/v2'],
            ]),
            'example.com/wp-json/wp/v2/users/me*' => Http::response(['id' => 7, 'name' => 'Site Owner']),
            'example.com/wp-json/wp/v2/posts*' => Http::response([]),
        ]);

        $user = User::factory()->create();
        $company = $this->companyWithSavedConnection($user);

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->set('wpUsername', 'someone-else')
            ->call('testConnection')
            ->assertSet('connectionNeedsSave', true)
            ->set('seoPlugin', SeoPlugin::Yoast->value)
            ->set('contentGenerationMode', ContentGenerationMode::Industry->value)
            ->call('save')
            ->assertHasNoErrors()
            ->call('testConnection')
            ->assertSet('connectionNeedsSave', false)
            ->assertSee(__('settings.wp_test_success_title'));

        $this->assertSame('someone-else', $company->refresh()->wp_username);
    }

    public function test_it_rejects_invalid_values(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->set('website', 'not a domain')
            ->set('seoPlugin', 'all-in-one')
            ->set('contentGenerationMode', 'whatever')
            ->call('save')
            ->assertHasErrors(['website', 'seoPlugin', 'contentGenerationMode']);

        $this->assertNull($company->refresh()->seo_plugin);
    }

    public function test_the_seo_plugin_is_required(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->set('seoPlugin', null)
            ->set('contentGenerationMode', ContentGenerationMode::Industry->value)
            ->call('save')
            ->assertHasErrors(['seoPlugin' => 'required']);

        $this->assertNull($company->refresh()->seo_plugin);
    }

    public function test_the_content_generation_mode_is_required(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->set('seoPlugin', SeoPlugin::Yoast->value)
            ->set('contentGenerationMode', null)
            ->call('save')
            ->assertHasErrors(['contentGenerationMode' => 'required']);

        $this->assertNull($company->refresh()->content_generation_mode);
    }

    public function test_the_content_language_is_saved_and_restricted_to_active_locales(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->set('seoPlugin', SeoPlugin::Yoast->value)
            ->set('contentGenerationMode', ContentGenerationMode::Industry->value)
            ->set('contentLanguage', 'en')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('en', $company->refresh()->content_language);

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->set('seoPlugin', SeoPlugin::Yoast->value)
            ->set('contentGenerationMode', ContentGenerationMode::Industry->value)
            ->set('contentLanguage', 'xx')
            ->call('save')
            ->assertHasErrors(['contentLanguage']);

        $this->assertSame('en', $company->refresh()->content_language);
    }

    public function test_the_saved_content_language_loads_back_into_the_form(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create(['content_language' => 'ru']);

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->assertSet('contentLanguage', 'ru');
    }
}
