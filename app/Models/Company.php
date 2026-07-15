<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Models\Concerns\HasSeo;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Fillable([
    'user_id',
    'slug',
    'name',
    'legal_name',
    'legal_type',
    'registration_number',
    'national_id',
    'established_at',
    'description',
    'summary',
    'main_products',
    'website',
    'email',
    'phones',
    'social_links',
    'status',
    'rejection_reason',
    'is_verified',
    'is_featured',
    'employee_range',
    'published_at',
])]
#[Translatable([
    'name',
    'legal_name',
    'description',
    'summary',
    'main_products',
])]
class Company extends Model implements HasMedia
{
    use HasFactory,
        HasTranslations,
        InteractsWithMedia,
        InteractsWithRichContent,
        SoftDeletes;
    use HasSeo;

    protected function casts(): array
    {
        return [
            'established_at' => 'date',
            'published_at' => 'datetime',
            'is_verified' => 'boolean',
            'is_featured' => 'boolean',
            'status' => CompanyStatus::class,
            // main_products is translatable and must not also carry an array
            // cast — HasTranslations already handles its JSON encoding.
            'phones' => 'array',
            'social_links' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('featured_image')->singleFile();
        $this->addMediaCollection('gallery');
        $this->addMediaCollection('certificates');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->queued();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CompanyAddress::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(CompanyBrand::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CompanyCategory::class);
    }

    public function exportCountries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'company_export_countries');
    }

    public function primaryAddress(): HasOne
    {
        return $this->hasOne(CompanyAddress::class)->where('is_primary', true);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', CompanyStatus::Approved)->whereNotNull('published_at');
    }

    /**
     * Publicly visible companies: approved and already published (excludes
     * approved companies whose published_at is still scheduled in the future).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->approved()->where('published_at', '<=', now());
    }

    protected function getSeoFallbackTitle(string $locale): ?string
    {
        return $this->getTranslation('name', $locale, false);
    }

    protected function getSeoFallbackDescription(string $locale): ?string
    {
        return $this->getTranslation('summary', $locale, false);
    }
}
