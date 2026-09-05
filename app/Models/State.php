<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use CyrildeWit\EloquentViewable\Contracts\Viewable;
use CyrildeWit\EloquentViewable\InteractsWithViews;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Fillable([
    'country_id',
    'name',
    'slug',
    'code',
    'geo_id',
    'type',
    'latitude',
    'longitude',
    'is_active',
])]
#[Translatable([
    'name',
    'type',
])]
class State extends Model implements Viewable
{
    use HasFactory,
        HasSeo,
        HasTranslations,
        InteractsWithViews,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'country_id' => 'integer',
            'is_active' => 'boolean',
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected function getSeoFallbackTitle(string $locale): ?string
    {
        return $this->getTranslation('name', $locale, false);
    }

    /**
     * No SeoMeta row exists for most states today (StateForm only just
     * gained a SeoMetaSection), so this is the description that actually
     * ships on /countries/{country}/{state} pages. Built from the
     * translated state name, its parent country's translated name, and the
     * published-company count — mirrors Country::getSeoFallbackDescription()
     * — so the Persian (and other non-English) site never falls back to an
     * empty or English-only meta description.
     */
    protected function getSeoFallbackDescription(string $locale): ?string
    {
        $count = $this->publishedCompaniesCount();

        if ($count === 0) {
            return null;
        }

        $countryName = $this->country?->getTranslation('name', $locale, false);

        if ($countryName === null) {
            return null;
        }

        return __('states.seo_fallback_description', [
            'count' => number_format($count),
            'state' => $this->getTranslation('name', $locale, false),
            'country' => $countryName,
        ], $locale);
    }

    /**
     * A state with no published companies is a thin page: it must stay
     * live, but should not be indexed until it has real content behind it.
     */
    protected function isThinPage(): bool
    {
        return $this->publishedCompaniesCount() === 0;
    }

    /**
     * Published companies located directly in this state.
     */
    protected function publishedCompaniesCount(): int
    {
        return CompanyPublication::query()
            ->active()
            ->where('state_id', $this->id)
            ->count();
    }
}
