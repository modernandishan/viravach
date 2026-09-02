<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CompanyReviewStatus: string implements HasColor, HasLabel
{
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Translated rather than hardcoded Persian: this enum is rendered on the
     * multilingual dashboard as well as in the Persian-only Filament panel.
     *
     * The keys already existed — ⚡my-companies has been rendering
     * companies.status_* directly for a while — and the `fa` values are
     * byte-identical to the Persian strings this method used to hardcode, so
     * the admin panel (pinned to `fa` by SetFilamentLocale) renders exactly
     * as before.
     */
    public function getLabel(): string
    {
        return __('companies.status_'.$this->value);
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
