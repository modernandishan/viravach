<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CompanyContentStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Queued = 'queued';
    case Generating = 'generating';
    case Ready = 'ready';
    case Failed = 'failed';

    public function isProcessing(): bool
    {
        return in_array($this, [self::Queued, self::Generating], true);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'پیش‌نویس',
            self::Queued => 'در صف تولید',
            self::Generating => 'در حال تولید',
            self::Ready => 'آماده',
            self::Failed => 'ناموفق',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Queued => 'warning',
            self::Generating => 'info',
            self::Ready => 'success',
            self::Failed => 'danger',
        };
    }
}
