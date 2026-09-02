<?php

namespace App\Ai;

use App\Ai\Exceptions\ContentGenerationException;
use App\Settings\ContentSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Minimal OpenAI-compatible chat-completions client for the content
 * generation pipeline. Transport only: it knows nothing about prompts,
 * schemas or companies. Content generation is an admin-enabled feature —
 * when it is off, or when the gateway misbehaves, callers get a
 * ContentGenerationException, never a partial or guessed structure.
 */
class ContentGenerator
{
    /**
     * Base delay for the exponential retry backoff, in microseconds.
     */
    protected const BACKOFF_MICROSECONDS = 500_000;

    public function complete(string $systemPrompt, string $userPrompt, ?string $model = null): array
    {
        $settings = app(ContentSettings::class);

        if (! $settings->enabled) {
            throw new ContentGenerationException('AI content generation is disabled.');
        }

        $model ??= $settings->model;

        $startedAt = hrtime(true);
        $response = $this->send($settings, $model, $systemPrompt, $userPrompt);

        $content = $response->json('choices.0.message.content');

        if (! is_string($content)) {
            Log::warning('Content generation: unexpected response shape.', [
                'model' => $model,
                'status' => $response->status(),
            ]);

            throw new ContentGenerationException('The AI gateway returned an unexpected response shape.');
        }

        $payload = $this->decode($content, $model);

        Log::info('Content generation completed.', [
            'model' => $model,
            'usage' => $response->json('usage'),
            'duration_ms' => (int) ((hrtime(true) - $startedAt) / 1e6),
        ]);

        return $payload;
    }

    /**
     * Sends the request, retrying only connection errors and 5xx/429 —
     * a 4xx is a caller-side problem (bad model, malformed payload), and
     * retrying it can only burn quota without changing the answer.
     */
    protected function send(ContentSettings $settings, string $model, string $systemPrompt, string $userPrompt): Response
    {
        $attempts = max(1, $settings->max_retries + 1);
        $lastFailure = 'no response';
        $lastBody = null;
        $lastStatus = null;
        // The loop breaks early on a 4xx (see below), so the maximum is not
        // the number actually tried — reporting the max made a single-attempt
        // 400 read as "failed after 3 attempts", which sent debugging down
        // the wrong path entirely.
        $attemptsMade = 0;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $attemptsMade = $attempt;

            if ($attempt > 1) {
                usleep(self::BACKOFF_MICROSECONDS * 2 ** ($attempt - 2));
            }

            try {
                $response = Http::timeout($settings->timeout)
                    ->connectTimeout(5)
                    ->withToken($settings->api_key)
                    ->acceptJson()
                    ->asJson()
                    ->post($this->endpoint($settings), [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => $userPrompt],
                        ],
                        'response_format' => ['type' => 'json_object'],
                        // Low temperature: factual copy that must not invent.
                        'temperature' => $settings->temperature,
                    ]);
            } catch (ConnectionException $e) {
                $lastFailure = 'connection failed: '.$e->getMessage();

                continue;
            }

            if ($response->successful()) {
                return $response;
            }

            $lastStatus = $response->status();
            $lastBody = $response->body();

            // An OpenAI-compatible gateway explains WHY it rejected a request
            // in the body (unknown model, unsupported response_format,
            // context_length_exceeded, bad temperature). Logging only the
            // status threw that away and left "HTTP 400" with no cause.
            $lastFailure = 'HTTP '.$lastStatus.': '.Str::limit($this->summariseError($lastBody), 300);

            if (! $response->serverError() && $lastStatus !== 429) {
                break;
            }
        }

        Log::warning('Content generation: request failed.', [
            'model' => $model,
            'status' => $lastStatus,
            'failure' => $lastFailure,
            'attempts_made' => $attemptsMade,
            'attempts_allowed' => $attempts,
            // Truncated, and never the api_key — only the gateway's own reply.
            'response_body' => $lastBody === null ? null : Str::limit($lastBody, 1000),
            'system_prompt_chars' => mb_strlen($systemPrompt),
            'user_prompt_chars' => mb_strlen($userPrompt),
        ]);

        throw new ContentGenerationException(
            "The AI gateway request failed after {$attemptsMade} attempt(s): {$lastFailure}.",
        );
    }

    protected function endpoint(ContentSettings $settings): string
    {
        return rtrim($settings->base_url, '/').'/chat/completions';
    }

    /**
     * Pulls the human-readable reason out of an OpenAI-compatible error body
     * ({"error": {"message": "...", "code": "..."}}), falling back to the raw
     * body when the gateway returns something else (an HTML error page from a
     * proxy, for instance).
     */
    protected function summariseError(?string $body): string
    {
        if ($body === null || trim($body) === '') {
            return 'empty response body';
        }

        $decoded = json_decode($body, true);

        if (is_array($decoded)) {
            $message = data_get($decoded, 'error.message') ?? data_get($decoded, 'message');
            $code = data_get($decoded, 'error.code') ?? data_get($decoded, 'error.type');

            if (is_string($message) && $message !== '') {
                return $code ? $message.' (code: '.$code.')' : $message;
            }
        }

        return trim($body);
    }

    /**
     * Models emit ```json fences even under response_format json_object, so
     * they are stripped before decoding. Anything that does not decode to an
     * array is a hard failure — never a partial or guessed structure.
     */
    protected function decode(string $content, string $model): array
    {
        $trimmed = trim($content);

        if (str_starts_with($trimmed, '```')) {
            $trimmed = (string) preg_replace('/^```(?:json)?\s*/i', '', $trimmed);
            $trimmed = (string) preg_replace('/\s*```$/i', '', $trimmed);
        }

        $decoded = json_decode(trim($trimmed), true);

        if (! is_array($decoded)) {
            Log::warning('Content generation: assistant content is not valid JSON.', ['model' => $model]);

            throw new ContentGenerationException('The AI assistant did not return valid JSON.');
        }

        return $decoded;
    }
}
