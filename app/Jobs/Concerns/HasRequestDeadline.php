<?php

namespace App\Jobs\Concerns;

use App\Ai\RequestDeadline;
use App\Jobs\Middleware\StartRequestDeadline;

/**
 * Gives a queued job one absolute deadline, derived from its own $timeout,
 * to hand to the AI clients.
 *
 * The problem it solves: ContentSettings::timeout caps ONE HTTP attempt and
 * ContentGenerator may spend it max_retries + 1 times, while the worker
 * kills the job at $timeout seconds flat. Every combination where
 * timeout × (max_retries + 1) > $timeout ended with a slow call killed
 * mid-request — no exception, no failure_reason, just a dead job. With a
 * deadline in hand the clients size each attempt to the time that is left
 * and fail cleanly when there is none.
 *
 * The deadline is anchored by StartRequestDeadline middleware, so it counts
 * from the instant the worker picked the job up. Calling handle() directly
 * (tests, tinker) bypasses middleware, so deadline() falls back to
 * anchoring on first use — a shade optimistic, but never absent.
 */
trait HasRequestDeadline
{
    protected ?RequestDeadline $requestDeadline = null;

    /**
     * True once the deadline has been anchored, so a job with no wall-clock
     * cap at all is not re-evaluated on every deadline() call.
     */
    protected bool $requestDeadlineStarted = false;

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new StartRequestDeadline];
    }

    public function startRequestDeadline(): void
    {
        if ($this->requestDeadlineStarted) {
            return;
        }

        $this->requestDeadlineStarted = true;

        $budget = (float) ($this->timeout ?? 0);

        // $timeout of 0/null means the worker will never kill this job, so
        // there is no budget to enforce and the clients keep their own.
        $this->requestDeadline = $budget > 0 ? RequestDeadline::in($budget) : null;
    }

    public function deadline(): ?RequestDeadline
    {
        $this->startRequestDeadline();

        return $this->requestDeadline;
    }
}
