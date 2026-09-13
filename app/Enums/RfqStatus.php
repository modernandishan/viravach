<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Lifecycle of one quote request, from the buyer's submission to the point
 * where the company owner is done with it.
 */
enum RfqStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Responded = 'responded';
    case Expired = 'expired';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return __('rfq.status_'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Responded => 'success',
            self::Expired => 'danger',
            self::Closed => 'secondary',
        };
    }

    /** Still waiting on the company — what the inbox badge counts. */
    public function needsAttention(): bool
    {
        return $this === self::Pending;
    }
}
