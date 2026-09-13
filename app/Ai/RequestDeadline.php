<?php

namespace App\Ai;

/**
 * The absolute instant by which a queued job's HTTP work must be finished.
 *
 * ContentSettings::timeout is a PER-ATTEMPT cap, and ContentGenerator may
 * spend it max_retries + 1 times over; the queue worker, meanwhile, kills
 * the whole job at its own $timeout with SIGALRM. Whenever the client's
 * worst case exceeded the job's budget, a slow call was killed mid-request
 * — no ContentGenerationException, no failure_reason on the row, just a
 * MaxAttemptsExceeded/timeout kill. This carries the job's budget into the
 * clients so every attempt is sized to the time that is actually left.
 *
 * The instant comes from hrtime(), which is monotonic: a clock adjustment
 * mid-job cannot make the remaining budget jump. That also means an
 * instance is only meaningful inside the process that created it, which is
 * exactly its lifetime — one job execution.
 */
final class RequestDeadline
{
    /**
     * Held back from every attempt so the job still has time to decode the
     * response, write its row and raise a clean exception before the
     * worker's alarm fires.
     */
    public const SAFETY_MARGIN_SECONDS = 10;

    /**
     * Shorter than this and an attempt is not worth starting: the gateway's
     * fastest observed successful call is far longer, so the request would
     * only be cut off mid-flight after burning what is left.
     */
    public const MINIMUM_ATTEMPT_SECONDS = 5;

    private function __construct(
        private readonly float $expiresAt,
    ) {}

    /**
     * A deadline $seconds from now. A non-positive budget yields an
     * already-expired deadline rather than an unlimited one.
     */
    public static function in(float $seconds): self
    {
        return new self(self::now() + $seconds);
    }

    public function remaining(): float
    {
        return $this->expiresAt - self::now();
    }

    /**
     * The timeout one HTTP attempt may use: the smaller of the configured
     * per-attempt timeout and whatever is left of the job's budget, less
     * the safety margin. Null means there is no longer enough time to start
     * an attempt at all — the caller must give up and say so, not fire a
     * request the worker will kill.
     *
     * A non-positive $settingsTimeout is treated as "no configured cap",
     * so the deadline alone decides.
     */
    public function attemptTimeout(int $settingsTimeout): ?int
    {
        $usable = (int) floor($this->remaining() - self::SAFETY_MARGIN_SECONDS);

        if ($usable < self::MINIMUM_ATTEMPT_SECONDS) {
            return null;
        }

        return $settingsTimeout > 0 ? min($settingsTimeout, $usable) : $usable;
    }

    /**
     * A backoff sleep must not eat into the margin either — it is capped at
     * the usable time so the following attemptTimeout() call reports an
     * exhausted budget instead of the sleep silently overrunning it.
     */
    public function cappedSleepSeconds(float $seconds): float
    {
        return max(0.0, min($seconds, $this->remaining() - self::SAFETY_MARGIN_SECONDS));
    }

    private static function now(): float
    {
        return hrtime(true) / 1e9;
    }
}
