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

    public static function notEligible(): self
    {
        return new self(CompanyChatFailureReason::NotEligible, 'The participant has not chatted with the AI assistant about this company yet.');
    }

    public static function noOwner(): self
    {
        return new self(CompanyChatFailureReason::NoOwner, 'The company has no owner to receive direct messages.');
    }
}
