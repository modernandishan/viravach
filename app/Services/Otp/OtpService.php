<?php

namespace App\Services\Otp;

use App\Models\OtpCode;
use App\Services\Otp\Exceptions\OtpException;
use Illuminate\Support\Facades\Hash;
use Ippanel\Client;

/**
 * Generates, sends and verifies one-time passwords over IPPanel.
 *
 * Every setting (origin number, pattern, digit count, pattern param) falls
 * back to config('ippanel.otp.*') but can be overridden per call, so any
 * flow in the app can send an OTP with a different pattern/number/digit
 * count while reusing the same throttling, storage and verification logic.
 */
class OtpService
{
    public function __construct(private readonly Client $client) {}

    public const DEFAULT_PURPOSE = 'login';

    public function secondsUntilResend(string $phone, string $purpose = self::DEFAULT_PURPOSE): int
    {
        $otp = $this->findFor($phone, $purpose);

        if (! $otp) {
            return 0;
        }

        $availableAt = $otp->last_sent_at->clone()->addSeconds(
            (int) config('ippanel.otp.resend_after'),
        );

        return $availableAt->isFuture() ? (int) now()->diffInSeconds($availableAt) : 0;
    }

    /**
     * @throws OtpException when a code was sent too recently or delivery fails
     */
    public function send(
        string $phone,
        string $purpose = self::DEFAULT_PURPOSE,
        ?string $pattern = null,
        ?string $originNumber = null,
        ?int $digits = null,
        ?string $paramKey = null,
    ): OtpCode {
        $remaining = $this->secondsUntilResend($phone, $purpose);

        if ($remaining > 0) {
            throw OtpException::cooldown($remaining);
        }

        $digits ??= (int) config('ippanel.otp.digits');
        $pattern ??= (string) config('ippanel.otp.pattern');
        $originNumber ??= (string) config('ippanel.otp.origin_number');
        $paramKey ??= (string) config('ippanel.otp.pattern_param');

        $code = $this->generateCode($digits);

        $response = $this->client->sendPattern($pattern, $originNumber, $this->toE164($phone), [
            $paramKey => $code,
        ]);

        if (! $response->isSuccessful()) {
            throw OtpException::deliveryFailed($response->getMessage());
        }

        return OtpCode::query()->updateOrCreate(
            ['phone' => $phone, 'purpose' => $purpose],
            [
                'code' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addSeconds((int) config('ippanel.otp.ttl')),
                'verified_at' => null,
                'last_sent_at' => now(),
                'ip_address' => request()->ip(),
            ],
        );
    }

    /**
     * @throws OtpException when there is no pending, unexpired, correct code left to try
     */
    public function verify(string $phone, string $code, string $purpose = self::DEFAULT_PURPOSE): void
    {
        $otp = $this->findFor($phone, $purpose);

        if (! $otp) {
            throw OtpException::notFound();
        }

        if ($otp->isExpired()) {
            throw OtpException::expired();
        }

        if ($otp->attempts >= (int) config('ippanel.otp.max_attempts')) {
            throw OtpException::tooManyAttempts();
        }

        if (! Hash::check($code, $otp->code)) {
            $otp->increment('attempts');

            throw OtpException::invalid();
        }

        $otp->forceFill(['verified_at' => now()])->save();
    }

    protected function findFor(string $phone, string $purpose): ?OtpCode
    {
        return OtpCode::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->first();
    }

    protected function generateCode(int $digits): string
    {
        $max = (10 ** $digits) - 1;

        return str_pad((string) random_int(0, $max), $digits, '0', STR_PAD_LEFT);
    }

    /**
     * IPPanel expects recipients in E.164 format (e.g. +989121234567), while
     * phone numbers are stored/looked-up locally in the domestic 09xxxxxxxxx
     * format used throughout the rest of the app.
     */
    protected function toE164(string $phone): string
    {
        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        if (str_starts_with($phone, '0')) {
            return '+98'.substr($phone, 1);
        }

        return $phone;
    }
}
