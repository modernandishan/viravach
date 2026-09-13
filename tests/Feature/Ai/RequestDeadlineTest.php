<?php

namespace Tests\Feature\Ai;

use App\Ai\ContentGenerator;
use App\Ai\Exceptions\ContentGenerationException;
use App\Ai\ImageGenerator;
use App\Ai\RequestDeadline;
use App\Jobs\Ai\GenerateSourceContent;
use App\Jobs\Middleware\StartRequestDeadline;
use App\Jobs\WordPress\GenerateWordPressPostImage;
use App\Settings\ContentSettings;
use GuzzleHttp\Exception\ConnectException as GuzzleConnectException;
use GuzzleHttp\Psr7\Request as PsrRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * ContentSettings::timeout caps ONE attempt; ContentGenerator may spend it
 * max_retries + 1 times; the queue worker kills the whole job at its own
 * $timeout. Whenever the former exceeded the latter, a slow gateway call was
 * killed mid-request — no ContentGenerationException, nothing written to the
 * row. These tests pin the budget the clients now respect.
 *
 * Deadlines are built with a fractional half-second (in(60.5) rather than
 * in(60)) so the floor() inside attemptTimeout() lands on the same integer
 * regardless of the microseconds that pass before it is read.
 */
class RequestDeadlineTest extends TestCase
{
    use RefreshDatabase;

    protected function enableSettings(int $timeout = 600, int $maxRetries = 2): void
    {
        app(ContentSettings::class)->fill([
            'enabled' => true,
            'base_url' => 'https://ai.example/v1',
            'api_key' => 'secret-key',
            'model' => 'content-model',
            'image_enabled' => true,
            'image_model' => 'image-model',
            'image_size' => '512x512',
            'timeout' => $timeout,
            'max_retries' => $maxRetries,
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonResponse(): array
    {
        return ['choices' => [['message' => ['content' => '{"ok":true}']]]];
    }

    private function clampLogged(string $message, int $settingsTimeout, int $attemptTimeout): callable
    {
        return fn (string $logged, array $context = []): bool => $logged === $message
            && ($context['settings_timeout'] ?? null) === $settingsTimeout
            && ($context['attempt_timeout'] ?? null) === $attemptTimeout;
    }

    // ------------------------------------------------------------- clamping

    public function test_the_attempt_timeout_is_the_smaller_of_the_setting_and_the_remaining_budget(): void
    {
        // Plenty of budget: the configured per-attempt timeout wins.
        $this->assertSame(600, RequestDeadline::in(3600.5)->attemptTimeout(600));

        // Tight budget: the deadline wins, less the safety margin.
        $this->assertSame(50, RequestDeadline::in(60.5)->attemptTimeout(600));

        // Below the minimum usable slice there is nothing worth starting.
        $this->assertNull(RequestDeadline::in(RequestDeadline::SAFETY_MARGIN_SECONDS + 1)->attemptTimeout(600));
        $this->assertNull(RequestDeadline::in(0)->attemptTimeout(600));
        $this->assertNull(RequestDeadline::in(-30)->attemptTimeout(600));
    }

    public function test_the_content_client_clamps_its_attempt_timeout_to_the_deadline(): void
    {
        $this->enableSettings(timeout: 600);

        Log::spy();

        Http::fake(['*' => Http::response($this->jsonResponse())]);

        // 90s of budget against a 600s configured timeout: the call must be
        // bounded by the job, not by the setting.
        app(ContentGenerator::class)->complete('s', 'u', null, RequestDeadline::in(90.5));

        Log::shouldHaveReceived('info')
            ->withArgs($this->clampLogged('Content generation: attempt timeout clamped to the job budget.', 600, 80))
            ->once();
    }

    public function test_the_image_client_clamps_its_attempt_timeout_to_the_deadline(): void
    {
        $this->enableSettings(timeout: 600);

        Log::spy();

        Http::fake(['*' => Http::response(['data' => [['b64_json' => base64_encode('bytes')]]])]);

        app(ImageGenerator::class)->generate('a prompt', RequestDeadline::in(45.5));

        Log::shouldHaveReceived('info')
            ->withArgs($this->clampLogged('Image generation: attempt timeout clamped to the job budget.', 600, 35))
            ->once();
    }

    public function test_no_deadline_leaves_the_configured_timeout_alone(): void
    {
        $this->enableSettings(timeout: 600);

        Log::spy();

        Http::fake(['*' => Http::response($this->jsonResponse())]);

        app(ContentGenerator::class)->complete('s', 'u');

        Http::assertSentCount(1);

        Log::shouldNotHaveReceived('info', [
            'Content generation: attempt timeout clamped to the job budget.',
            Mockery::any(),
        ]);
    }

    // -------------------------------------------------------- read timeouts

    public function test_a_read_timeout_is_not_retried(): void
    {
        $this->enableSettings(maxRetries: 2);

        $sent = 0;

        // cURL 28 is what a read timeout looks like coming out of Guzzle:
        // the request WAS sent and the whole attempt timeout was spent, so a
        // retry would multiply the wall clock rather than recover anything.
        //
        // A fake that THROWS is never added to Http's recorded log, so the
        // attempts are counted here instead of with assertSentCount().
        Http::fake(function () use (&$sent) {
            $sent++;

            throw new ConnectionException(
                'cURL error 28: Operation timed out after 600000 milliseconds with 0 bytes received',
            );
        });

        $this->expectException(ContentGenerationException::class);
        $this->expectExceptionMessage('failed after 1 attempt(s)');

        try {
            app(ContentGenerator::class)->complete('s', 'u');
        } finally {
            $this->assertSame(1, $sent);
        }
    }

    public function test_a_read_timeout_carrying_guzzles_handler_context_is_not_retried(): void
    {
        $this->enableSettings(maxRetries: 2);

        $sent = 0;

        // The real production shape: Laravel wraps Guzzle's ConnectException,
        // whose handler context carries the cURL errno.
        Http::fake(function () use (&$sent) {
            $sent++;

            throw new ConnectionException(
                'timed out',
                0,
                new GuzzleConnectException(
                    'timed out',
                    new PsrRequest('POST', 'https://ai.example/v1/chat/completions'),
                    null,
                    ['errno' => 28],
                ),
            );
        });

        $this->expectException(ContentGenerationException::class);
        $this->expectExceptionMessage('failed after 1 attempt(s)');

        try {
            app(ContentGenerator::class)->complete('s', 'u');
        } finally {
            $this->assertSame(1, $sent);
        }
    }

    public function test_a_refused_connection_is_still_retried(): void
    {
        $this->enableSettings(maxRetries: 2);

        $sent = 0;

        // cURL 7 — nothing left the machine, so a retry is nearly free.
        Http::fake(function () use (&$sent) {
            $sent++;

            throw new ConnectionException('cURL error 7: Failed to connect to ai.example port 443');
        });

        $this->expectException(ContentGenerationException::class);
        $this->expectExceptionMessage('failed after 3 attempt(s)');

        try {
            app(ContentGenerator::class)->complete('s', 'u');
        } finally {
            $this->assertSame(3, $sent);
        }
    }

    public function test_a_503_is_still_retried(): void
    {
        $this->enableSettings(maxRetries: 2);

        Http::fake(['*' => Http::sequence()->pushStatus(503)->pushStatus(503)->pushStatus(503)]);

        $this->expectException(ContentGenerationException::class);
        $this->expectExceptionMessage('failed after 3 attempt(s)');

        try {
            app(ContentGenerator::class)->complete('s', 'u');
        } finally {
            Http::assertSentCount(3);
        }
    }

    public function test_a_503_is_retried_within_a_deadline_that_still_has_room(): void
    {
        $this->enableSettings(timeout: 30, maxRetries: 2);

        Http::fake(['*' => Http::sequence()->pushStatus(503)->push($this->jsonResponse())]);

        $payload = app(ContentGenerator::class)->complete('s', 'u', null, RequestDeadline::in(300.5));

        $this->assertSame(['ok' => true], $payload);
        Http::assertSentCount(2);
    }

    // ----------------------------------------------------- exhausted budget

    public function test_an_exhausted_budget_is_refused_before_the_first_request(): void
    {
        $this->enableSettings();

        Http::fake();

        $this->expectException(ContentGenerationException::class);
        $this->expectExceptionMessage("abandoned after 0 attempt(s): the job's time budget was exhausted");

        try {
            app(ContentGenerator::class)->complete('s', 'u', null, RequestDeadline::in(1));
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_a_budget_exhausted_between_retries_stops_the_loop_and_says_so(): void
    {
        $this->enableSettings(timeout: 5, maxRetries: 2);

        // The first attempt burns 3 real seconds before its 503, which is
        // what pushes the budget under the minimum for a second attempt —
        // exactly the production shape, where a slow call leaves no room to
        // retry and the worker used to kill the job instead.
        Http::fake(function () {
            usleep(3_000_000);

            return Http::response([], 503);
        });

        $this->expectException(ContentGenerationException::class);
        $this->expectExceptionMessage('time budget was exhausted');

        try {
            app(ContentGenerator::class)->complete('s', 'u', null, RequestDeadline::in(17.5));
        } finally {
            Http::assertSentCount(1);
        }
    }

    public function test_the_image_client_refuses_an_exhausted_budget(): void
    {
        $this->enableSettings();

        Http::fake();

        $this->expectException(ContentGenerationException::class);
        $this->expectExceptionMessage("abandoned after 0 attempt(s): the job's time budget was exhausted");

        try {
            app(ImageGenerator::class)->generate('a prompt', RequestDeadline::in(1));
        } finally {
            Http::assertNothingSent();
        }
    }

    // -------------------------------------------------------------- the jobs

    public function test_jobs_derive_their_deadline_from_their_own_timeout(): void
    {
        $job = new GenerateSourceContent(1);

        $this->assertSame(600, $job->timeout);
        $this->assertEqualsWithDelta(590, $job->deadline()?->attemptTimeout(600), 1);

        // The case production actually died on: a 180s job holding a 600s
        // configured timeout now gets 170, not 600.
        $imageJob = new GenerateWordPressPostImage(1);

        $this->assertSame(180, $imageJob->timeout);
        $this->assertEqualsWithDelta(170, $imageJob->deadline()?->attemptTimeout(600), 1);
    }

    public function test_the_middleware_anchors_the_deadline_before_handle_runs(): void
    {
        $job = new GenerateSourceContent(1);

        $this->assertEquals([new StartRequestDeadline], $job->middleware());

        $reached = false;

        (new StartRequestDeadline)->handle($job, function () use (&$reached): void {
            $reached = true;
        });

        $this->assertTrue($reached);
        $this->assertNotNull($job->deadline());
    }
}
