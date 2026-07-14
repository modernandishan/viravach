<?php

namespace App\Services\Otp\Exceptions;

enum OtpFailureReason: string
{
    case Cooldown = 'cooldown';
    case DeliveryFailed = 'delivery_failed';
    case NotFound = 'not_found';
    case Expired = 'expired';
    case TooManyAttempts = 'too_many_attempts';
    case Invalid = 'invalid';
}
