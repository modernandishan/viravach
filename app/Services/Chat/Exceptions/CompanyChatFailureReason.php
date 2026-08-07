<?php

namespace App\Services\Chat\Exceptions;

enum CompanyChatFailureReason: string
{
    case NotEligible = 'not_eligible';
    case NoOwner = 'no_owner';
}
