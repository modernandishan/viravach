<?php

namespace Tests\Feature\WordPress;

use App\Jobs\WordPress\GenerateWordPressPostImage;
use App\Jobs\WordPress\PublishWordPressPost;
use App\Models\Company;
use App\Models\WordPressContentPost;
use App\Settings\ContentSettings;
use App\Support\ImageMimeType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Two production failures are pinned here:
 *
 *  1. Publishing downloaded the featured image from $media->getUrl() — the
 *     PUBLIC media domain — and died with "cURL error 7: Failed to connect
 *     to media.viravach.com port 443", because the containers cannot reach
 *     it (hairpin NAT). The bytes must come off the media's own disk.
 *  2. The image gateway now answers with WebP, but the pipeline stored the
 *     file as .png and uploaded it to WordPress as image/png, so WordPress
 *     saw a file whose name, declared type and contents disagreed.
 */
class WordPressFeaturedImageTransferTest extends TestCase
{
    use RefreshDatabase;

    /** A real 1x1 lossless WebP — finfo must recognise it, not a stub. */
    private function webpBytes(): string
    {
        return (string) hex2bin(
            '524946461a00000057454250'
            .'5650384c0d0000002f00000010071011118888fe0700'
        );
    }

    private function pngBytes(): string
    {
        return (string) hex2bin(
            '89504e470d0a1a0a0000000d49484452000000010000000108060000001f15c489'
            .'0000000a49444154789c6360000002000100fe21bc330000000049454e44ae426082'
        );
    }

    private function companyWithCredentials(): Company
    {
        return Company::factory()->create([
            'website' => 'https://example.com',
            'wp_username' => 'admin',
            'wp_application_password' => 'abcd efgh ijkl mnop qrst uvwx',
        ]);
    }

    private function queuedPost(Company $company): WordPressContentPost
    {
        return WordPressContentPost::factory()->queued()->create([
            'company_id' => $company->id,
            'step' => 2,
            'topic' => 'Industrial pumps',
            'title' => 'How Industrial Pumps Improve Efficiency',
            'excerpt' => 'A short meta description about industrial pumps.',
            'body' => '<p>Body content.</p>',
            'focus_keyword' => 'industrial pumps',
            'image_alt' => 'A worker inspecting an industrial pump',
        ]);
    }

    public function test_publishing_reads_the_featured_image_off_its_disk_and_never_over_http(): void
    {
        Storage::fake('s3');

        Http::fake([
            'example.com/wp-json/wp/v2/media' => Http::response(['id' => 55]),
            'example.com/wp-json/wp/v2/media/55' => Http::response(['id' => 55]),
            'example.com/wp-json/wp/v2/posts' => Http::response(['id' => 101, 'link' => 'https://example.com/p']),
        ]);

        $company = $this->companyWithCredentials();
        $post = $this->queuedPost($company);

        $post->addMediaFromString($this->webpBytes())
            ->usingFileName('featured.webp')
            ->toMediaCollection('featured_image', 's3');

        (new PublishWordPressPost($post->id))->handle();

        // The bytes reached WordPress...
        Http::assertSent(fn ($request): bool => $request->url() === 'https://example.com/wp-json/wp/v2/media'
            && $request->body() === $this->webpBytes());

        // ...and nothing was ever fetched from our own media host.
        Http::assertNotSent(fn ($request): bool => ! str_contains($request->url(), 'example.com'));

        $post->refresh();

        $this->assertSame(101, $post->wp_post_id);
        $this->assertSame(55, $post->wp_media_id);
    }

    public function test_the_upload_declares_the_medias_real_mime_type_and_extension(): void
    {
        Storage::fake('s3');

        Http::fake([
            'example.com/wp-json/wp/v2/media' => Http::response(['id' => 55]),
            'example.com/wp-json/wp/v2/media/55' => Http::response(['id' => 55]),
            'example.com/wp-json/wp/v2/posts' => Http::response(['id' => 101, 'link' => 'https://example.com/p']),
        ]);

        $company = $this->companyWithCredentials();
        $post = $this->queuedPost($company);

        $post->addMediaFromString($this->webpBytes())
            ->usingFileName('featured.webp')
            ->toMediaCollection('featured_image', 's3');

        (new PublishWordPressPost($post->id))->handle();

        Http::assertSent(function ($request): bool {
            if ($request->url() !== 'https://example.com/wp-json/wp/v2/media') {
                return false;
            }

            return $request->header('Content-Type')[0] === 'image/webp'
                && str_contains($request->header('Content-Disposition')[0], '.webp"');
        });
    }

    public function test_the_generated_image_is_stored_under_the_format_the_gateway_returned(): void
    {
        Storage::fake('s3');

        app(ContentSettings::class)->fill([
            'enabled' => true,
            'base_url' => 'https://ai.example/api',
            'api_key' => 'secret-key',
            'image_enabled' => true,
            'image_model' => 'meta/muse-image',
            'image_size' => '512x512',
            'timeout' => 60,
        ])->save();

        Http::fake([
            'https://ai.example/api/v1/images/generations*' => Http::response(
                ['data' => [['b64_json' => base64_encode($this->webpBytes())]]],
            ),
        ]);

        $post = $this->queuedPost($this->companyWithCredentials());
        $post->forceFill(['step' => 1])->save();

        (new GenerateWordPressPostImage($post->id))->handle();

        $media = $post->refresh()->getFirstMedia('featured_image');

        $this->assertNotNull($media);
        $this->assertStringEndsWith('.webp', $media->file_name);
        $this->assertSame('image/webp', $media->mime_type);
    }

    public function test_the_mime_helper_recognises_the_three_supported_formats_and_falls_back_to_png(): void
    {
        $this->assertSame('image/webp', ImageMimeType::detect($this->webpBytes()));
        $this->assertSame('image/png', ImageMimeType::detect($this->pngBytes()));

        $this->assertSame('webp', ImageMimeType::extensionFor('image/webp'));
        $this->assertSame('jpg', ImageMimeType::extensionFor('image/jpeg'));
        $this->assertSame('png', ImageMimeType::extensionFor('image/png'));

        // Anything unrecognised keeps the pipeline's historical assumption
        // rather than inventing a type WordPress would reject.
        $this->assertSame('image/png', ImageMimeType::detect('not-an-image-at-all'));
        $this->assertSame('image/png', ImageMimeType::normalize('image/gif'));
        $this->assertSame('png', ImageMimeType::extensionFor(null));
    }
}
