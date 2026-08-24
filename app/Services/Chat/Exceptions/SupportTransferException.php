<?php

namespace App\Services\Chat\Exceptions;

use RuntimeException;

class SupportTransferException extends RuntimeException
{
    private function __construct(
        public readonly SupportTransferFailureReason $reason,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function noAgentAvailable(): self
    {
        return new self(SupportTransferFailureReason::NoAgentAvailable, 'No support agent is currently available.');
    }
}
