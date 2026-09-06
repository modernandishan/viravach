<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Outcome of the last "Test connection" run against the company's own
 * WordPress install. Stored on the draft Company so the confirmation stays
 * visible across page loads without re-hitting the remote site every time.
 */
enum WordPressConnectionStatus: string implements HasLabel
{
    case NotTested = 'not_tested';
    case Connected = 'connected';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return __('settings.wp_status_'.$this->value);
    }
}
