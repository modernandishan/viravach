<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Canceled = 'canceled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار پرداخت',
            self::Paid => 'پرداخت شده',
            self::Failed => 'ناموفق',
            self::Canceled => 'لغو شده',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Failed => 'danger',
            self::Canceled => 'gray',
        };
    }
}
