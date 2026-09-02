<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use CyrildeWit\EloquentViewable\Contracts\Viewable;
use CyrildeWit\EloquentViewable\InteractsWithViews;
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
class Country extends Model implements HasMedia, Viewable
{
    use HasFactory,
        HasSeo,
        HasTranslations,
        InteractsWithMedia,
        InteractsWithViews,
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

    protected function getSeoFallbackTitle(string $locale): ?string
    {
        return $this->getTranslation('name', $locale, false);
    }

    /**
     * No SeoMeta row exists for most countries today (there is no Filament
     * UI to create one — see StateForm, which has the same gap), so this is
     * the description that actually ships on /countries/{country} pages.
     * Built from the translated name and the published-company count so the
     * Persian (and other non-English) site never falls back to an empty or
     * English-only meta description.
     */
    protected function getSeoFallbackDescription(string $locale): ?string
    {
        $count = $this->publishedCompaniesCount();

        if ($count === 0) {
            return null;
        }

        return __('countries.seo_fallback_description', [
            'count' => number_format($count),
            'country' => $this->getTranslation('name', $locale, false),
        ], $locale);
    }

    /**
     * A country with no published companies is a thin page: it must stay
     * live (⚡country.blade.php no longer 404s on this — see isThinPage()
     * callers in HasSeo::applySeoTags()), but should not be indexed until
     * it has real content behind it.
     */
    protected function isThinPage(): bool
    {
        return $this->publishedCompaniesCount() === 0;
    }

    /**
     * Published companies located in any of this country's active states.
     * Also the single source of truth for whether the country page has any
     * content — ⚡country.blade.php no longer runs its own existence check.
     */
    protected function publishedCompaniesCount(): int
    {
        return CompanyPublication::query()
            ->active()
            ->whereIn('state_id', $this->states()->pluck('id'))
            ->count();
    }
}
