<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Fillable([
    'meta_title',
    'meta_description',
    'meta_keywords',
    'focus_keyword',
    'robots_index',
    'robots_follow',
    'canonical_url',
    'og_type',
    'og_title',
    'og_description',
    'twitter_card_type',
    'twitter_title',
    'twitter_description',
    'schema_type',
    'schema_extra',
    'sitemap_include',
    'sitemap_priority',
    'sitemap_change_freq',
    'is_cornerstone',
])]
#[Translatable([
    'meta_title',
    'meta_description',
    'meta_keywords',
    'focus_keyword',
    'og_title',
    'og_description',
    'twitter_title',
    'twitter_description',
])]
class SeoMeta extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia;

    protected function casts(): array
    {
        return [
            'schema_extra' => 'array',
            'robots_index' => 'boolean',
            'robots_follow' => 'boolean',
            'sitemap_include' => 'boolean',
            'sitemap_priority' => 'decimal:1',
            'is_cornerstone' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('og_image')->singleFile();
        $this->addMediaCollection('twitter_image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->performOnCollections('og_image', 'twitter_image')
            ->queued();
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
