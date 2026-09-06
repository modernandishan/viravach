<?php

namespace Tests\Feature\Dashboard;

use App\Enums\WordPressConnectionStatus;
use App\Models\Company;
use App\Models\User;
use App\Services\WordPress\WordPressConnectionFailureReason;
use App\Services\WordPress\WordPressConnectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Every WordPress call is faked: the suite must never touch a real site.
 */
class WordPressConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected function companyWithCredentials(User $user): Company
    {
        return Company::factory()->for($user)->create([
            'website' => 'https://example.com',
            'wp_username' => 'admin',
            'wp_application_password' => 'abcd efgh ijkl mnop qrst uvwx',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function restRoot(): array
    {
        return [
            'name' => 'Example Site',
            'description' => 'Just another WordPress site',
            'url' => 'https://example.com',
            'namespaces' => ['oembed/1.0', 'wp/v2'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function posts(): array
    {
        return [
            [
                'id' => 3,
                'link' => 'https://example.com/newest',
                'date' => '2026-09-01T10:00:00',
                'date_gmt' => '2026-09-01T06:30:00',
                'title' => ['rendered' => 'Newest &amp; best post'],
            ],
            [
                'id' => 2,
                'link' => 'https://example.com/middle',
                'date' => '2026-08-20T10:00:00',
                'date_gmt' => '2026-08-20T06:30:00',
                'title' => ['rendered' => 'Middle post'],
            ],
            [
                'id' => 1,
                'link' => 'https://example.com/oldest',
                'date' => '2026-08-01T10:00:00',
                'date_gmt' => '2026-08-01T06:30:00',
                'title' => ['rendered' => 'Oldest post'],
            ],
        ];
    }

    protected function fakeSuccessfulSite(): void
    {
        Http::fake([
            'example.com/wp-json/' => Http::response($this->restRoot()),
            'example.com/wp-json/wp/v2/users/me*' => Http::response([
                'id' => 7,
                'name' => 'Site Owner',
                'username' => 'admin',
            ]),
            'example.com/wp-json/wp/v2/posts*' => Http::response($this->posts()),
        ]);
    }

    public function test_a_successful_test_returns_the_identity_and_three_posts(): void
    {
        $this->fakeSuccessfulSite();

        $company = $this->companyWithCredentials(User::factory()->create());

        $result = app(WordPressConnectionService::class)->testConnection($company);

        $this->assertTrue($result->successful);
        $this->assertNull($result->reason);
        $this->assertSame('Example Site', $result->siteName);
        $this->assertSame('Site Owner', $result->userName);
        $this->assertSame('admin', $result->userLogin);
        $this->assertCount(3, $result->posts);
        // Rendered titles arrive as HTML entities and are shown as plain text.
        $this->assertSame('Newest & best post', $result->posts[0]['title']);
        $this->assertSame('https://example.com/newest', $result->posts[0]['link']);
        // Normalised at fetch time so an odd remote date cannot break rendering.
        $this->assertSame('2026-09-01T06:30:00+00:00', $result->posts[0]['date']);
    }

    public function test_an_unparseable_post_date_is_dropped_rather_than_stored(): void
    {
        Http::fake([
            'example.com/wp-json/' => Http::response($this->restRoot()),
            'example.com/wp-json/wp/v2/users/me*' => Http::response(['id' => 7, 'name' => 'Site Owner']),
            'example.com/wp-json/wp/v2/posts*' => Http::response([[
                'link' => 'https://example.com/broken',
                'date_gmt' => 'not a date at all',
                'title' => ['rendered' => 'Broken date'],
            ]]),
        ]);

        $result = app(WordPressConnectionService::class)
            ->testConnection($this->companyWithCredentials(User::factory()->create()));

        $this->assertTrue($result->successful);
        $this->assertNull($result->posts[0]['date']);
        $this->assertSame('Broken date', $result->posts[0]['title']);
    }

    public function test_it_authenticates_with_basic_auth_and_asks_for_three_posts(): void
    {
        $this->fakeSuccessfulSite();

        $company = $this->companyWithCredentials(User::factory()->create());

        app(WordPressConnectionService::class)->testConnection($company);

        $expected = 'Basic '.base64_encode('admin:abcd efgh ijkl mnop qrst uvwx');

        // The unauthenticated probe comes first: no credentials are sent to a
        // host before it is confirmed to be WordPress.
        Http::assertSent(fn ($request): bool => $request->url() === 'https://example.com/wp-json/'
            && ! $request->hasHeader('Authorization'));

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/wp/v2/users/me')
            && $request->hasHeader('Authorization', $expected));

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/wp/v2/posts')
            && str_contains($request->url(), 'per_page=3')
            && str_contains($request->url(), '_embed=1')
            && $request->hasHeader('Authorization', $expected));
    }

    public function test_a_successful_test_is_persisted_on_the_company(): void
    {
        $this->fakeSuccessfulSite();

        $company = $this->companyWithCredentials(User::factory()->create());

        app(WordPressConnectionService::class)->testConnection($company);

        $company->refresh();

        $this->assertSame(WordPressConnectionStatus::Connected, $company->wp_connection_status);
        $this->assertNotNull($company->wp_last_checked_at);
        $this->assertCount(3, $company->wp_last_posts);
        $this->assertSame('Newest & best post', $company->wp_last_posts[0]['title']);
    }

    public function test_wrong_credentials_fail_with_the_authentication_reason(): void
    {
        Http::fake([
            'example.com/wp-json/' => Http::response($this->restRoot()),
            'example.com/wp-json/wp/v2/users/me*' => Http::response([
                'code' => 'incorrect_password',
            ], 401),
        ]);

        $company = $this->companyWithCredentials(User::factory()->create());

        $result = app(WordPressConnectionService::class)->testConnection($company);

        $this->assertFalse($result->successful);
        $this->assertSame(WordPressConnectionFailureReason::AuthenticationFailed, $result->reason);

        $company->refresh();
        $this->assertSame(WordPressConnectionStatus::Failed, $company->wp_connection_status);
        $this->assertNull($company->wp_last_posts);
    }

    public function test_a_non_wordpress_url_fails_before_any_credentials_are_sent(): void
    {
        Http::fake([
            'example.com/wp-json/' => Http::response(['hello' => 'world']),
        ]);

        $company = $this->companyWithCredentials(User::factory()->create());

        $result = app(WordPressConnectionService::class)->testConnection($company);

        $this->assertFalse($result->successful);
        $this->assertSame(WordPressConnectionFailureReason::NotWordPress, $result->reason);

        Http::assertSentCount(1);
    }

    public function test_a_missing_rest_api_fails_as_not_wordpress(): void
    {
        Http::fake([
            'example.com/wp-json/' => Http::response('<html>Not found</html>', 404),
        ]);

        $result = app(WordPressConnectionService::class)
            ->testConnection($this->companyWithCredentials(User::factory()->create()));

        $this->assertSame(WordPressConnectionFailureReason::NotWordPress, $result->reason);
    }

    public function test_a_network_timeout_fails_gracefully(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out after 10000 milliseconds'));

        $company = $this->companyWithCredentials(User::factory()->create());

        $result = app(WordPressConnectionService::class)->testConnection($company);

        $this->assertFalse($result->successful);
        $this->assertSame(WordPressConnectionFailureReason::Unreachable, $result->reason);
        $this->assertSame(WordPressConnectionStatus::Failed, $company->refresh()->wp_connection_status);
    }

    public function test_an_unexpected_error_from_a_wordpress_site_is_reported_separately(): void
    {
        Http::fake([
            'example.com/wp-json/' => Http::response($this->restRoot()),
            'example.com/wp-json/wp/v2/users/me*' => Http::response(['code' => 'boom'], 500),
        ]);

        $result = app(WordPressConnectionService::class)
            ->testConnection($this->companyWithCredentials(User::factory()->create()));

        $this->assertSame(WordPressConnectionFailureReason::RequestFailed, $result->reason);
    }

    public function test_incomplete_settings_fail_without_calling_out_or_recording_a_status(): void
    {
        Http::fake();

        $company = Company::factory()->for(User::factory()->create())->create([
            'website' => 'https://example.com',
            'wp_username' => null,
            'wp_application_password' => 'abcd efgh ijkl mnop qrst uvwx',
        ]);

        $result = app(WordPressConnectionService::class)->testConnection($company);

        $this->assertSame(WordPressConnectionFailureReason::IncompleteSettings, $result->reason);

        Http::assertNothingSent();
        $this->assertSame(WordPressConnectionStatus::NotTested, $company->refresh()->wp_connection_status);
    }

    public function test_verified_credentials_survive_an_unreadable_posts_endpoint(): void
    {
        Http::fake([
            'example.com/wp-json/' => Http::response($this->restRoot()),
            'example.com/wp-json/wp/v2/users/me*' => Http::response(['id' => 7, 'name' => 'Site Owner']),
            'example.com/wp-json/wp/v2/posts*' => Http::response(['code' => 'boom'], 500),
        ]);

        $result = app(WordPressConnectionService::class)
            ->testConnection($this->companyWithCredentials(User::factory()->create()));

        $this->assertTrue($result->successful);
        $this->assertSame([], $result->posts);
    }

    public function test_the_settings_page_shows_the_identity_and_posts_after_a_successful_test(): void
    {
        $this->fakeSuccessfulSite();

        $user = User::factory()->create();
        $company = $this->companyWithCredentials($user);

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->call('testConnection')
            ->assertSet('connectionFailureReason', null)
            ->assertSet('connectionSiteName', 'Example Site')
            ->assertSet('connectionUserName', 'Site Owner')
            ->assertSee(__('settings.wp_test_success_title'))
            // Blade escapes it back for output; the stored value is plain text.
            ->assertSee('Newest & best post')
            ->assertSee('https://example.com/newest')
            ->assertSee('Middle post')
            ->assertSee('Oldest post');
    }

    public function test_the_settings_page_shows_the_auth_specific_failure_message(): void
    {
        Http::fake([
            'example.com/wp-json/' => Http::response($this->restRoot()),
            'example.com/wp-json/wp/v2/users/me*' => Http::response([], 401),
        ]);

        $user = User::factory()->create();
        $company = $this->companyWithCredentials($user);

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->call('testConnection')
            ->assertSet('connectionFailureReason', WordPressConnectionFailureReason::AuthenticationFailed->value)
            ->assertSee(__('settings.wp_test_failed_authentication_failed'))
            ->assertDontSee(__('settings.wp_test_failed_not_wordpress'));
    }

    public function test_the_settings_page_shows_the_not_wordpress_failure_message(): void
    {
        Http::fake([
            'example.com/wp-json/' => Http::response(['hello' => 'world']),
        ]);

        $user = User::factory()->create();
        $company = $this->companyWithCredentials($user);

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->call('testConnection')
            ->assertSee(__('settings.wp_test_failed_not_wordpress'))
            ->assertDontSee(__('settings.wp_test_failed_unreachable'));
    }

    public function test_the_settings_page_shows_the_unreachable_failure_message_on_timeout(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $user = User::factory()->create();
        $company = $this->companyWithCredentials($user);

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->call('testConnection')
            ->assertSee(__('settings.wp_test_failed_unreachable'))
            ->assertDontSee(__('settings.wp_test_failed_authentication_failed'));
    }

    public function test_a_stored_confirmation_is_rendered_without_re_testing(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $company = $this->companyWithCredentials($user);
        $company->forceFill([
            'wp_connection_status' => WordPressConnectionStatus::Connected,
            'wp_last_checked_at' => now(),
            'wp_last_posts' => [
                ['title' => 'Stored post', 'link' => 'https://example.com/stored', 'date' => '2026-08-01T06:30:00'],
            ],
        ])->save();

        Livewire::actingAs($user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->assertSee(__('settings.wp_test_success_title'))
            ->assertSee('Stored post')
            ->assertSee('https://example.com/stored');

        Http::assertNothingSent();
    }
}
