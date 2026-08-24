<?php

namespace App\Services\Chat\Exceptions;

enum CompanyChatFailureReason: string
{
    case NoOwner = 'no_owner';
    case IsOwner = 'is_owner';
}
