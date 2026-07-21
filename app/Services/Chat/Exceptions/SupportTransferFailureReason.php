<?php

namespace App\Services\Chat\Exceptions;

enum SupportTransferFailureReason: string
{
    case NotEligible = 'not_eligible';
    case NoAgentAvailable = 'no_agent_available';
}
