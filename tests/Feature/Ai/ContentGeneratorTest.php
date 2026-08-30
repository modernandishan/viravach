<?php

namespace Tests\Feature\Ai;

use App\Ai\ContentGenerator;
use App\Ai\Exceptions\ContentGenerationException;
use App\Settings\ContentSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContentGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected function enableSettings(): void
    {
        app(ContentSettings::class)->fill([
            'enabled' => true,
            'base_url' => 'https://ai.example/v1',
            'api_key' => 'secret-key',
            'model' => 'content-model',
            'max_retries' => 2,
        ])->save();
    }

    public function test_a_valid_json_response_is_decoded(): void
    {
        $this->enableSettings();

        Http::fake([
            'https://ai.example/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => '{"title":"عنوان","sections":[]}']],
                ],
                'usage' => ['total_tokens' => 42],
            ]),
        ]);

        $payload = app(ContentGenerator::class)->complete('system prompt', 'user prompt');

        $this->assertSame(['title' => 'عنوان', 'sections' => []], $payload);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->hasHeader('Authorization', 'Bearer secret-key')
                && $body['model'] === 'content-model'
                && $body['response_format'] === ['type' => 'json_object']
                && $body['messages'][0]['role'] === 'system'
                && $body['messages'][0]['content'] === 'system prompt'
                && $body['messages'][1]['role'] === 'user'
                && $body['messages'][1]['content'] === 'user prompt';
        });
    }

    public function test_a_fenced_json_response_is_decoded(): void
    {
        $this->enableSettings();

        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => "```json\n{\"title\":\"Fenced\"}\n```"]],
                ],
            ]),
        ]);

        $this->assertSame(['title' => 'Fenced'], app(ContentGenerator::class)->complete('s', 'u'));
    }

    public function test_invalid_json_throws_without_retrying(): void
    {
        $this->enableSettings();

        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'This is prose, not JSON.']],
                ],
            ]),
        ]);

        $this->expectException(ContentGenerationException::class);
        $this->expectExceptionMessage('did not return valid JSON');

        try {
            app(ContentGenerator::class)->complete('s', 'u');
        } finally {
            Http::assertSentCount(1);
        }
    }

    public function test_a_500_is_retried_then_throws(): void
    {
        $this->enableSettings();

        Http::fake([
            '*' => Http::sequence()
                ->pushStatus(500)
                ->pushStatus(500)
                ->pushStatus(500),
        ]);

        $this->expectException(ContentGenerationException::class);
        $this->expectExceptionMessage('failed after 3 attempt(s)');

        try {
            app(ContentGenerator::class)->complete('s', 'u');
        } finally {
            Http::assertSentCount(3);
        }
    }

    public function test_a_429_is_retried_then_throws(): void
    {
        $this->enableSettings();

        Http::fake([
            '*' => Http::sequence()
                ->pushStatus(429)
                ->pushStatus(429)
                ->pushStatus(429),
        ]);

        $this->expectException(ContentGenerationException::class);

        try {
            app(ContentGenerator::class)->complete('s', 'u');
        } finally {
            Http::assertSentCount(3);
        }
    }

    public function test_a_400_is_not_retried(): void
    {
        $this->enableSettings();

        Http::fake([
            '*' => Http::response(['error' => ['message' => 'bad model']], 400),
        ]);

        $this->expectException(ContentGenerationException::class);

        try {
            app(ContentGenerator::class)->complete('s', 'u');
        } finally {
            Http::assertSentCount(1);
        }
    }

    public function test_disabled_settings_throw_immediately_without_any_http_call(): void
    {
        // enabled defaults to false from the settings migration.
        app(ContentSettings::class)->fill([
            'base_url' => 'https://ai.example/v1',
            'api_key' => 'secret-key',
        ])->save();

        Http::fake();

        $this->expectException(ContentGenerationException::class);
        $this->expectExceptionMessage('disabled');

        try {
            app(ContentGenerator::class)->complete('s', 'u');
        } finally {
            Http::assertNothingSent();
        }
    }
}
