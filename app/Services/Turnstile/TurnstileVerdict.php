<?php

namespace App\Services\Turnstile;

/**
 * Cloudflare's answer about one widget token.
 */
class TurnstileVerdict
{
    /**
     * @param  array<int, string>  $errorCodes
     */
    public function __construct(
        public readonly bool $passed,
        public readonly array $errorCodes = [],
    ) {}

    /**
     * The token was fine but is no longer usable — already redeemed, or older
     * than its 300-second lifetime. The visitor is not necessarily a bot, so
     * the form asks them to solve the widget again rather than rejecting them.
     */
    public function needsFreshToken(): bool
    {
        return in_array(TurnstileVerifier::ERROR_STALE_TOKEN, $this->errorCodes, true);
    }
}
