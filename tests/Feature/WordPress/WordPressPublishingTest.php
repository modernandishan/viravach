<?php

namespace Tests\Feature\WordPress;

use App\Enums\SeoPlugin;
use App\Models\Company;
use App\Models\WordPressContentPost;
use App\Services\WordPress\WordPressPublishFailureReason;
use App\Services\WordPress\WordPressPublishingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Every request to the destination WordPress site is faked — this suite
 * must never publish to a real site.
 */
class WordPressPublishingTest extends TestCase
{
    use RefreshDatabase;

    protected function companyWithCredentials(?SeoPlugin $plugin = null): Company
    {
        return Company::factory()->create([
            'website' => 'https://example.com',
            'wp_username' => 'admin',
            'wp_application_password' => 'abcd efgh ijkl mnop qrst uvwx',
            'seo_plugin' => $plugin,
        ]);
    }

    protected function queuedPost(Company $company): WordPressContentPost
    {
        return WordPressContentPost::factory()->queued()->create([
            'company_id' => $company->id,
            'title' => 'How Industrial Pumps Improve Efficiency',
            'excerpt' => 'A short meta description about industrial pumps.',
            'body' => '<p>Body content.</p>',
            'focus_keyword' => 'industrial pumps',
            'image_alt' => 'A worker inspecting an industrial pump',
        ]);
    }

    public function test_a_successful_publish_uploads_the_image_and_creates_the_post(): void
    {
        Http::fake([
            'example.com/wp-json/wp/v2/media' => Http::response(['id' => 55]),
            'example.com/wp-json/wp/v2/media/55' => Http::response(['id' => 55, 'alt_text' => 'ok']),
            'example.com/wp-json/wp/v2/posts' => Http::response([
                'id' => 101,
                'link' => 'https://example.com/how-industrial-pumps-improve-efficiency',
            ]),
        ]);

        $company = $this->companyWithCredentials(SeoPlugin::Yoast);
        $post = $this->queuedPost($company);

        $result = app(WordPressPublishingService::class)->publish($post, 'fake-image-bytes');

        $this->assertTrue($result->successful);
        $this->assertSame(101, $result->postId);
        $this->assertSame('https://example.com/how-industrial-pumps-improve-efficiency', $result->postUrl);
        $this->assertSame(55, $result->mediaId);
    }

    public function test_the_media_upload_sends_a_content_disposition_filename_and_alt_text_separately(): void
    {
        Http::fake([
            'example.com/wp-json/wp/v2/media' => Http::response(['id' => 55]),
            'example.com/wp-json/wp/v2/media/55' => Http::response(['id' => 55]),
            'example.com/wp-json/wp/v2/posts' => Http::response(['id' => 101, 'link' => 'https://example.com/post']),
        ]);

        $company = $this->companyWithCredentials();
        $post = $this->queuedPost($company);

        app(WordPressPublishingService::class)->publish($post, 'fake-image-bytes');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://example.com/wp-json/wp/v2/media'
            && str_contains((string) $request->header('Content-Disposition')[0], 'attachment; filename=')
            && $request->header('Content-Type')[0] === 'image/png'
            && $request->body() === 'fake-image-bytes');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://example.com/wp-json/wp/v2/media/55'
            && $request['alt_text'] === 'A worker inspecting an industrial pump');
    }

    public function test_no_image_bytes_skips_the_media_upload_and_publishes_without_a_featured_image(): void
    {
        Http::fake([
            'example.com/wp-json/wp/v2/posts' => Http::response(['id' => 101, 'link' => 'https://example.com/post']),
        ]);

        $company = $this->companyWithCredentials();
        $post = $this->queuedPost($company);

        $result = app(WordPressPublishingService::class)->publish($post, null);

        $this->assertTrue($result->successful);
        $this->assertNull($result->mediaId);
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/wp/v2/media'));
    }

    public function test_yoast_meta_keys_are_sent_with_the_post(): void
    {
        Http::fake(['example.com/*' => Http::response(['id' => 101, 'link' => 'https://example.com/post'])]);

        $company = $this->companyWithCredentials(SeoPlugin::Yoast);
        $post = $this->queuedPost($company);

        app(WordPressPublishingService::class)->publish($post, null);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://example.com/wp-json/wp/v2/posts'
            && $request['meta']['_yoast_wpseo_title'] === $post->title
            && $request['meta']['_yoast_wpseo_metadesc'] === $post->excerpt
            && $request['meta']['_yoast_wpseo_focuskw'] === $post->focus_keyword);
    }

    public function test_rank_math_meta_keys_are_sent_with_the_post(): void
    {
        Http::fake(['example.com/*' => Http::response(['id' => 101, 'link' => 'https://example.com/post'])]);

        $company = $this->companyWithCredentials(SeoPlugin::RankMath);
        $post = $this->queuedPost($company);

        app(WordPressPublishingService::class)->publish($post, null);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://example.com/wp-json/wp/v2/posts'
            && $request['meta']['rank_math_title'] === $post->title
            && $request['meta']['rank_math_focus_keyword'] === $post->focus_keyword);
    }

    public function test_no_seo_plugin_selected_sends_no_meta_at_all(): void
    {
        Http::fake(['example.com/*' => Http::response(['id' => 101, 'link' => 'https://example.com/post'])]);

        $company = $this->companyWithCredentials(null);
        $post = $this->queuedPost($company);

        app(WordPressPublishingService::class)->publish($post, null);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://example.com/wp-json/wp/v2/posts'
            && ! array_key_exists('meta', $request->data()));
    }

    public function test_meta_keys_silently_dropped_by_wordpress_are_detected_as_not_writable(): void
    {
        Http::fake([
            'example.com/wp-json/wp/v2/posts' => Http::response(['id' => 101, 'link' => 'https://example.com/post']),
            // WordPress accepted the request but did not persist the keys —
            // exactly what an unregistered Yoast/Rank Math meta key does.
            'example.com/wp-json/wp/v2/posts/101*' => Http::response(['id' => 101, 'meta' => []]),
        ]);

        $company = $this->companyWithCredentials(SeoPlugin::Yoast);
        $post = $this->queuedPost($company);

        $result = app(WordPressPublishingService::class)->publish($post, null);

        $this->assertTrue($result->successful);
        $this->assertFalse($result->seoMetaWritable);
    }

    public function test_meta_keys_that_survive_are_detected_as_writable(): void
    {
        Http::fake([
            'example.com/wp-json/wp/v2/posts' => Http::response(['id' => 101, 'link' => 'https://example.com/post']),
            'example.com/wp-json/wp/v2/posts/101*' => Http::response([
                'id' => 101,
                'meta' => ['_yoast_wpseo_title' => 'How Industrial Pumps Improve Efficiency'],
            ]),
        ]);

        $company = $this->companyWithCredentials(SeoPlugin::Yoast);
        $post = $this->queuedPost($company);

        $result = app(WordPressPublishingService::class)->publish($post, null);

        $this->assertTrue($result->seoMetaWritable);
    }

    public function test_wrong_credentials_fail_with_the_authentication_reason(): void
    {
        Http::fake(['example.com/*' => Http::response([], 401)]);

        $company = $this->companyWithCredentials();
        $post = $this->queuedPost($company);

        $result = app(WordPressPublishingService::class)->publish($post, null);

        $this->assertFalse($result->successful);
        $this->assertSame(WordPressPublishFailureReason::AuthenticationFailed, $result->reason);
    }

    public function test_a_forbidden_account_fails_with_the_not_permitted_reason(): void
    {
        Http::fake(['example.com/*' => Http::response([], 403)]);

        $company = $this->companyWithCredentials();
        $post = $this->queuedPost($company);

        $result = app(WordPressPublishingService::class)->publish($post, null);

        $this->assertSame(WordPressPublishFailureReason::NotPermitted, $result->reason);
    }

    public function test_a_network_timeout_fails_with_the_unreachable_reason(): void
    {
        Http::fake(fn () => throw new ConnectionException('timed out'));

        $company = $this->companyWithCredentials();
        $post = $this->queuedPost($company);

        $result = app(WordPressPublishingService::class)->publish($post, null);

        $this->assertFalse($result->successful);
        $this->assertSame(WordPressPublishFailureReason::Unreachable, $result->reason);
    }

    public function test_a_failed_media_upload_fails_with_its_own_reason_and_never_creates_the_post(): void
    {
        Http::fake([
            'example.com/wp-json/wp/v2/media' => Http::response(['code' => 'upload_error'], 500),
        ]);

        $company = $this->companyWithCredentials();
        $post = $this->queuedPost($company);

        $result = app(WordPressPublishingService::class)->publish($post, 'fake-image-bytes');

        $this->assertSame(WordPressPublishFailureReason::MediaUploadFailed, $result->reason);
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/wp/v2/posts'));
    }

    public function test_incomplete_settings_fail_without_any_request(): void
    {
        Http::fake();

        $company = Company::factory()->create(['website' => 'https://example.com', 'wp_username' => null]);
        $post = $this->queuedPost($company);

        $result = app(WordPressPublishingService::class)->publish($post, null);

        $this->assertSame(WordPressPublishFailureReason::IncompleteSettings, $result->reason);
        Http::assertNothingSent();
    }

    public function test_wordpress_rejecting_the_post_itself_fails_with_its_own_reason(): void
    {
        Http::fake(['example.com/*' => Http::response(['code' => 'rest_cannot_create'], 400)]);

        $company = $this->companyWithCredentials();
        $post = $this->queuedPost($company);

        $result = app(WordPressPublishingService::class)->publish($post, null);

        $this->assertSame(WordPressPublishFailureReason::PostCreationFailed, $result->reason);
    }
}
