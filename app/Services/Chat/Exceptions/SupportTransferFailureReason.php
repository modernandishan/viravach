<?php

namespace App\Services\Chat\Exceptions;

enum SupportTransferFailureReason: string
{
    case NoAgentAvailable = 'no_agent_available';
}
