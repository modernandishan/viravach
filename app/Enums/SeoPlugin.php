<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The WordPress SEO plugin installed on the company's own site. It decides
 * which meta fields the publisher writes when pushing generated content,
 * so the two plugins' field names never have to be guessed at publish time.
 */
enum SeoPlugin: string implements HasLabel
{
    case Yoast = 'yoast';
    case RankMath = 'rank_math';

    public function getLabel(): string
    {
        return __('settings.seo_plugin_'.$this->value);
    }
}
