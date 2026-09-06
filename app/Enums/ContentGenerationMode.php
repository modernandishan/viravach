<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How topics for the company's generated content are chosen: from the
 * company's own industry, or from what is currently trending on Google.
 */
enum ContentGenerationMode: string implements HasLabel
{
    case Industry = 'industry';
    case Trending = 'trending';

    public function getLabel(): string
    {
        return __('settings.content_mode_'.$this->value);
    }
}
