<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use CyrildeWit\EloquentViewable\Contracts\Viewable;
use CyrildeWit\EloquentViewable\InteractsWithViews;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Fillable([
    'slug',
    'title',
    'h1',
    'subheading',
    'intro_body',
    // 'content',
    'is_active',
    'published_at',
    // 'renderer',
    'sort_order',
])]
#[Translatable([
    'title',
    'h1',
    'subheading',
    'intro_body',
    // 'content',
])]
class Page extends Model implements Viewable
{
    use HasSeo, HasTranslations, InteractsWithRichContent, SoftDeletes;
    use InteractsWithViews;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected function getSeoFallbackTitle(string $locale): ?string
    {
        return $this->getTranslation('title', $locale, false);
    }
}
