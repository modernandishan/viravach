<?php

namespace App\Services\Chat\Exceptions;

use RuntimeException;

class CompanyChatException extends RuntimeException
{
    private function __construct(
        public readonly CompanyChatFailureReason $reason,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function noOwner(): self
    {
        return new self(CompanyChatFailureReason::NoOwner, 'The company has no owner to receive direct messages.');
    }

    public static function isOwner(): self
    {
        return new self(CompanyChatFailureReason::IsOwner, 'The participant is the company\'s own owner and cannot message it.');
    }
}
