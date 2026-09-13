<?php

namespace App\Services\Turnstile;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Exchanges a Turnstile widget token for Cloudflare's verdict.
 *
 * One POST to one endpoint, so this is a plain Http call rather than a package
 * (see docs/RESEARCH.md §3). Kept as a service instead of inlined in the Volt
 * component so the component stays testable with a fake and every caller sends
 * the same parameters.
 */
class TurnstileVerifier
{
    /**
     * Cloudflare hands back `error-codes` for a rejected token; this one means
     * the token was already redeemed or has aged past its 300-second life, not
     * that the visitor is a bot. Callers surface it as "solve it again".
     */
    public const ERROR_STALE_TOKEN = 'timeout-or-duplicate';

    /**
     * Verify a token for the given visitor IP.
     *
     * Fails closed on every uncertain outcome — a blank token, a non-2xx
     * response, or an unreachable Cloudflare. The RFQ form is the abuse
     * surface being protected, so an outage must not become an open door; the
     * per-IP and per-company rate limiters remain the backstop if this
     * decision is ever revisited (docs/RESEARCH.md §2).
     */
    public function verify(?string $token, ?string $remoteIp = null): TurnstileVerdict
    {
        if (blank($token)) {
            return new TurnstileVerdict(false, ['missing-input-response']);
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post(config('services.turnstile.verify_url'), array_filter([
                    'secret' => (string) config('services.turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ]));
        } catch (Throwable $exception) {
            Log::warning('Turnstile verification could not be reached.', [
                'exception' => $exception->getMessage(),
            ]);

            return new TurnstileVerdict(false, ['internal-error']);
        }

        if ($response->failed()) {
            Log::warning('Turnstile verification returned an error response.', [
                'status' => $response->status(),
            ]);

            return new TurnstileVerdict(false, ['internal-error']);
        }

        return new TurnstileVerdict(
            $response->json('success') === true,
            (array) $response->json('error-codes', []),
        );
    }
}
