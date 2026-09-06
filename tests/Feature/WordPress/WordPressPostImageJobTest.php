<?php

namespace Tests\Feature\WordPress;

use App\Ai\Exceptions\ContentGenerationException;
use App\Jobs\WordPress\GenerateWordPressPostImage;
use App\Models\Company;
use App\Models\WordPressContentPost;
use App\Settings\ContentSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * A gateway rejection (HTTP 400 and friends) used to surface as bare
 * "HTTP 400" in the stored failure_reason, which made the dashboard row
 * undiagnosable. These tests pin the fix: the gateway's JSON error body is
 * logged AND carried into the exception message, so the stored
 * WordPressContentPost row shows the gateway's actual complaint.
 */
class WordPressPostImageJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(ContentSettings::class)->fill([
            'enabled' => true,
            'base_url' => 'https://ai.example/api',
            'api_key' => 'secret-key',
            'image_enabled' => true,
            'image_model' => 'meta/muse-image',
            'image_size' => '512x512',
            'timeout' => 60,
        ])->save();
    }

    protected function postWithImageAlt(): WordPressContentPost
    {
        $company = Company::factory()->create();

        return WordPressContentPost::factory()->queued()->create([
            'company_id' => $company->id,
            'title' => 'How Industrial Pumps Improve Efficiency',
            'image_alt' => 'A worker inspecting an industrial pump',
        ]);
    }

    /**
     * Runs the job the way a queue worker would: the thrown exception is
     * handed to failed(), which is what writes the stored failure_reason.
     */
    protected function runJobExpectingFailure(WordPressContentPost $post): ContentGenerationException
    {
        $job = new GenerateWordPressPostImage($post->id);

        try {
            $job->handle();

            $this->fail('Expected the image job to throw.');
        } catch (ContentGenerationException $exception) {
            $job->failed($exception);

            return $exception;
        }
    }

    public function test_a_gateway_400_logs_and_surfaces_its_error_body(): void
    {
        Log::spy();

        Http::fake([
            'https://ai.example/api/v1/images/generations*' => Http::response(
                ['error' => ['message' => 'Prompt contains unsupported characters', 'code' => 400]],
                400,
            ),
        ]);

        $post = $this->postWithImageAlt();

        $exception = $this->runJobExpectingFailure($post);

        // The log carries the full body for diagnosis...
        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn (string $message, array $context): bool => $message === 'Image generation: request failed.'
                && $context['failure'] === 'HTTP 400'
                && str_contains((string) $context['response_body'], 'Prompt contains unsupported characters'),
        );

        // ...and the stored row shows the gateway's actual complaint, not
        // just the status code.
        $post->refresh();

        $this->assertSame('failed', $post->status->value);
        $this->assertStringContainsString('HTTP 400', $post->failure_reason);
        $this->assertStringContainsString('Prompt contains unsupported characters', $post->failure_reason);
        $this->assertStringContainsString('Prompt contains unsupported characters', $exception->getMessage());
    }

    public function test_the_payload_matches_the_profile_pipeline_shape(): void
    {
        Http::fake([
            'https://ai.example/api/v1/images/generations*' => Http::response(
                ['data' => [['b64_json' => 'aWNvbg==']]],
            ),
        ]);

        $post = $this->postWithImageAlt();

        (new GenerateWordPressPostImage($post->id))->handle();

        // Same endpoint contract as the company-profile pipeline: model,
        // prompt, n and size — the request that 400'd differed only in its
        // prompt text, never in shape.
        Http::assertSent(function ($request): bool {
            $data = $request->data();

            return $request->url() === 'https://ai.example/api/v1/images/generations'
                && $data['model'] === 'meta/muse-image'
                && $data['n'] === 1
                && $data['size'] === '512x512'
                && str_contains((string) $data['prompt'], 'How Industrial Pumps Improve Efficiency');
        });
    }
}
