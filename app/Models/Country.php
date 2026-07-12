<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Fillable([
    'name',
    'official_name',
    'slug',
    'iso2',
    'iso3',
    'numeric_code',
    'phone_code',
    'capital',
    'currency',
    'currency_symbol',
    'currency_name',
    'tld',
    'region',
    'subregion',
    'latitude',
    'longitude',
    'bounding_box',
    'area',
    'population',
    'flag_emoji',
    'is_active',
])]
#[Translatable([
    'name',
    'official_name',
    'capital',
    'currency_name',
])]
class Country extends Model implements HasMedia
{
    use HasFactory,
        HasTranslations,
        InteractsWithMedia,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'bounding_box' => 'array',
            'is_active'    => 'boolean',
            'latitude'     => 'decimal:6',
            'longitude'    => 'decimal:6',
            'area'         => 'integer',
            'population'   => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('flag')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->performOnCollections('flag')
            ->queued();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }
}
