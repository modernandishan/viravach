<?php

namespace App\Services\Chat\Exceptions;

use RuntimeException;

class TranslationException extends RuntimeException
{
    public static function requestFailed(): self
    {
        return new self('The translation request failed.');
    }
}
