<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CompanyReviewStatus: string implements HasColor, HasLabel
{
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::PendingReview => 'در انتظار بررسی',
            self::Approved => 'تأیید شده',
            self::Rejected => 'رد شده',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PendingReview => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
