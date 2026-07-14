<?php

namespace App\Services\Otp\Exceptions;

use RuntimeException;

class OtpException extends RuntimeException
{
    private function __construct(
        public readonly OtpFailureReason $reason,
        string $message,
        public readonly int $retryAfter = 0,
    ) {
        parent::__construct($message);
    }

    public static function cooldown(int $retryAfter): self
    {
        return new self(OtpFailureReason::Cooldown, 'A code was already sent recently.', $retryAfter);
    }

    public static function deliveryFailed(?string $reason): self
    {
        return new self(OtpFailureReason::DeliveryFailed, $reason ?? 'Failed to send the verification code.');
    }

    public static function notFound(): self
    {
        return new self(OtpFailureReason::NotFound, 'No verification code was requested for this number.');
    }

    public static function expired(): self
    {
        return new self(OtpFailureReason::Expired, 'The verification code has expired.');
    }

    public static function tooManyAttempts(): self
    {
        return new self(OtpFailureReason::TooManyAttempts, 'Too many failed attempts.');
    }

    public static function invalid(): self
    {
        return new self(OtpFailureReason::Invalid, 'The verification code is incorrect.');
    }
}
