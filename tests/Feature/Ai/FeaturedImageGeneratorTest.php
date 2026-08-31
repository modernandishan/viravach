<?php

namespace Tests\Feature\Ai;

use App\Ai\Exceptions\ContentGenerationException;
use App\Ai\ImageGenerator;
use App\Settings\ContentSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Exercises ImageGenerator's response-shape handling directly, against the
 * real Open WebUI behaviour verified in production: base_url has no /v1
 * segment, the generations endpoint does, response_format is never sent,
 * and a successful generation response can be a bare array, an
 * OpenAI-style {"data":[...]} object, or (other gateways) a b64_json block.
 */
class FeaturedImageGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        app(ContentSettings::class)->fill([
            'enabled' => true,
            'base_url' => 'https://ai.example/api',
            'api_key' => 'secret-key',
            'image_enabled' => true,
            'image_model' => 'image-model',
            'image_size' => '512x512',
            'timeout' => 60,
        ])->save();
    }

    private function pngBytes(): string
    {
        return (string) base64_decode(self::VALID_PNG_BASE64, true);
    }

    public function test_the_bare_array_shape_with_a_relative_url_is_resolved_and_fetched_with_the_bearer_token(): void
    {
        Http::fake([
            'https://ai.example/api/v1/images/generations*' => Http::response([
                ['url' => '/api/v1/files/some-uuid/content'],
            ]),
            'https://ai.example/api/v1/files/some-uuid/content' => Http::response(
                $this->pngBytes(),
                200,
                ['Content-Type' => 'image/png'],
            ),
        ]);

        $bytes = app(ImageGenerator::class)->generate('a prompt');

        $this->assertSame($this->pngBytes(), $bytes);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://ai.example/api/v1/images/generations'
            && ! array_key_exists('response_format', $request->data()));

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://ai.example/api/v1/files/some-uuid/content'
            && $request->hasHeader('Authorization', 'Bearer secret-key'));
    }

    public function test_the_openai_data_array_shape_with_an_absolute_url_is_supported(): void
    {
        Http::fake([
            'https://ai.example/api/v1/images/generations*' => Http::response([
                'data' => [['url' => 'https://cdn.example/generated.png']],
            ]),
            'https://cdn.example/generated.png' => Http::response(
                $this->pngBytes(),
                200,
                ['Content-Type' => 'image/png'],
            ),
        ]);

        $bytes = app(ImageGenerator::class)->generate('a prompt');

        $this->assertSame($this->pngBytes(), $bytes);
    }

    public function test_the_b64_json_shape_is_supported(): void
    {
        Http::fake([
            'https://ai.example/api/v1/images/generations*' => Http::response([
                'data' => [['b64_json' => self::VALID_PNG_BASE64]],
            ]),
        ]);

        $bytes = app(ImageGenerator::class)->generate('a prompt');

        $this->assertSame($this->pngBytes(), $bytes);
    }

    public function test_a_200_response_with_an_empty_array_body_throws(): void
    {
        Http::fake([
            'https://ai.example/api/v1/images/generations*' => Http::response([]),
        ]);

        $this->expectException(ContentGenerationException::class);

        app(ImageGenerator::class)->generate('a prompt');
    }

    public function test_a_file_fetch_returning_html_instead_of_an_image_throws(): void
    {
        Http::fake([
            'https://ai.example/api/v1/images/generations*' => Http::response([
                ['url' => '/api/v1/files/some-uuid/content'],
            ]),
            'https://ai.example/api/v1/files/some-uuid/content' => Http::response(
                '<html><body>Not authorized</body></html>',
                200,
                ['Content-Type' => 'text/html'],
            ),
        ]);

        $this->expectException(ContentGenerationException::class);

        app(ImageGenerator::class)->generate('a prompt');
    }
}
