<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Lifecycle of one generated WordPress article, from the moment the owner
 * asks for it to the moment it exists on their own site.
 */
enum WordPressPostStatus: string implements HasColor, HasLabel
{
    case Queued = 'queued';
    case Generating = 'generating';
    case Published = 'published';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return __('wordpress_content.status_'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Queued => 'gray',
            self::Generating => 'warning',
            self::Published => 'success',
            self::Failed => 'danger',
        };
    }

    /** Still moving through the pipeline, so the page keeps polling. */
    public function isProcessing(): bool
    {
        return $this === self::Queued || $this === self::Generating;
    }
}
