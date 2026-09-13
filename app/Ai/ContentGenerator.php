<?php

namespace App\Ai;

use App\Ai\Exceptions\ContentGenerationException;
use App\Settings\ContentSettings;
use GuzzleHttp\Exception\ConnectException as GuzzleConnectException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

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

    /**
     * cURL error numbers worth a second try: the request never reached the
     * gateway, and it failed fast enough that retrying costs almost nothing
     * — proxy/host resolution, connection refused, TLS handshake.
     *
     * CURLE_OPERATION_TIMEDOUT (28) is deliberately absent. That is the read
     * timeout: the request WAS sent, the gateway is chewing on it, and the
     * whole per-attempt timeout has already been spent. Retrying it doubles
     * or triples the wall-clock cost for a call that is not failing fast,
     * which is precisely what used to overrun the job's budget. So is
     * CURLE_GOT_NOTHING (52) — an empty reply after the request went out.
     */
    protected const RETRYABLE_CURL_ERRORS = [
        5,  // CURLE_COULDNT_RESOLVE_PROXY
        6,  // CURLE_COULDNT_RESOLVE_HOST
        7,  // CURLE_COULDNT_CONNECT
        35, // CURLE_SSL_CONNECT_ERROR
    ];

    /**
     * @param  RequestDeadline|null  $deadline  The calling job's remaining wall-clock
     *                                          budget. Null (tinker, sync code paths) keeps the historical
     *                                          behaviour: every attempt gets the full configured timeout.
     */
    public function complete(
        string $systemPrompt,
        string $userPrompt,
        ?string $model = null,
        ?RequestDeadline $deadline = null,
    ): array {
        $settings = app(ContentSettings::class);

        if (! $settings->enabled) {
            throw new ContentGenerationException('AI content generation is disabled.');
        }

        $model ??= $settings->model;

        $startedAt = hrtime(true);
        $response = $this->send($settings, $model, $systemPrompt, $userPrompt, $deadline);

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
     * Sends the request, retrying only failures that are both transient AND
     * fast: a refused connection, an unresolvable host, a 5xx or a 429. A
     * 4xx is a caller-side problem (bad model, malformed payload) and a read
     * timeout is a slow failure that has already spent its whole budget —
     * neither is retried.
     *
     * Every attempt is sized to what is left of $deadline, and the loop
     * gives up with an explicit "time budget exhausted" error rather than
     * firing a request the queue worker would kill mid-flight.
     */
    protected function send(
        ContentSettings $settings,
        string $model,
        string $systemPrompt,
        string $userPrompt,
        ?RequestDeadline $deadline = null,
    ): Response {
        $attempts = max(1, $settings->max_retries + 1);
        $lastFailure = 'no response';
        $lastBody = null;
        $lastStatus = null;
        $attemptTimeout = $settings->timeout;
        // The loop breaks early on a 4xx (see below), so the maximum is not
        // the number actually tried — reporting the max made a single-attempt
        // 400 read as "failed after 3 attempts", which sent debugging down
        // the wrong path entirely.
        $attemptsMade = 0;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            if ($attempt > 1) {
                $this->backoff($attempt, $deadline);
            }

            if ($deadline !== null) {
                $remaining = $deadline->attemptTimeout($settings->timeout);

                if ($remaining === null) {
                    $this->throwBudgetExhausted($model, $attemptsMade, $deadline, $systemPrompt, $userPrompt);
                }

                if ($remaining < $settings->timeout) {
                    // Visible proof in the logs that the job's budget, not
                    // the configured timeout, is what bounded this call.
                    Log::info('Content generation: attempt timeout clamped to the job budget.', [
                        'model' => $model,
                        'attempt' => $attempt,
                        'settings_timeout' => $settings->timeout,
                        'attempt_timeout' => $remaining,
                    ]);
                }

                $attemptTimeout = $remaining;
            }

            $attemptsMade = $attempt;

            try {
                $response = Http::timeout($attemptTimeout)
                    ->connectTimeout(min(5, $attemptTimeout))
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
                if (! $this->isRetryableConnectionFailure($e)) {
                    $lastFailure = 'read timed out or transport failed without a fast error: '.$e->getMessage();

                    break;
                }

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
            'attempt_timeout' => $attemptTimeout,
            'budget_remaining' => $deadline === null ? null : round($deadline->remaining(), 1),
            // Truncated, and never the api_key — only the gateway's own reply.
            'response_body' => $lastBody === null ? null : Str::limit($lastBody, 1000),
            'system_prompt_chars' => mb_strlen($systemPrompt),
            'user_prompt_chars' => mb_strlen($userPrompt),
        ]);

        throw new ContentGenerationException(
            "The AI gateway request failed after {$attemptsMade} attempt(s): {$lastFailure}.",
        );
    }

    /**
     * Exponential backoff, never past the point where the next attempt
     * could still run inside the job's budget.
     */
    protected function backoff(int $attempt, ?RequestDeadline $deadline): void
    {
        $seconds = (self::BACKOFF_MICROSECONDS * 2 ** ($attempt - 2)) / 1_000_000;

        if ($deadline !== null) {
            $seconds = $deadline->cappedSleepSeconds($seconds);
        }

        if ($seconds > 0) {
            usleep((int) round($seconds * 1_000_000));
        }
    }

    /**
     * @throws ContentGenerationException always
     */
    protected function throwBudgetExhausted(
        string $model,
        int $attemptsMade,
        RequestDeadline $deadline,
        string $systemPrompt,
        string $userPrompt,
    ): never {
        $remaining = round($deadline->remaining(), 1);

        Log::warning('Content generation: abandoned, job time budget exhausted.', [
            'model' => $model,
            'attempts_made' => $attemptsMade,
            'budget_remaining' => $remaining,
            'system_prompt_chars' => mb_strlen($systemPrompt),
            'user_prompt_chars' => mb_strlen($userPrompt),
        ]);

        throw new ContentGenerationException(
            "The AI gateway request was abandoned after {$attemptsMade} attempt(s): "
            ."the job's time budget was exhausted ({$remaining}s left before the worker kills it).",
        );
    }

    /**
     * Only a failure that never reached the gateway is worth retrying.
     *
     * The cURL error number is the reliable signal, read from the Guzzle
     * ConnectException's handler context; a ConnectionException raised
     * without one (a faked client, a non-cURL handler) falls back to its
     * message, where anything mentioning a timeout is treated as the slow
     * failure it almost certainly is.
     */
    protected function isRetryableConnectionFailure(ConnectionException $exception): bool
    {
        $errno = $this->curlErrorNumber($exception);

        if ($errno !== null) {
            return in_array($errno, self::RETRYABLE_CURL_ERRORS, true);
        }

        return ! Str::contains(Str::lower($exception->getMessage()), ['timed out', 'timeout']);
    }

    protected function curlErrorNumber(ConnectionException $exception): ?int
    {
        $previous = $exception->getPrevious();

        if ($previous instanceof GuzzleConnectException) {
            $errno = $previous->getHandlerContext()['errno'] ?? null;

            if (is_int($errno) && $errno > 0) {
                return $errno;
            }
        }

        // Laravel copies Guzzle's message verbatim, and Guzzle's always
        // opens with "cURL error {n}: ..." for a handler-level failure.
        return $this->curlErrorNumberFromMessage($exception->getMessage())
            ?? ($previous instanceof Throwable
                ? $this->curlErrorNumberFromMessage($previous->getMessage())
                : null);
    }

    private function curlErrorNumberFromMessage(string $message): ?int
    {
        return preg_match('/cURL error (\d+)/i', $message, $matches) === 1
            ? (int) $matches[1]
            : null;
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
