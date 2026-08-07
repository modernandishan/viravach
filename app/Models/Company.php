<?php

namespace App\Models;

use App\Enums\CompanyReviewStatus;
use App\Enums\CompanyType;
use App\Models\Concerns\HasSeo;
use App\Observers\CompanyObserver;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravelcm\Subscriptions\Models\Subscription;
use Laravelcm\Subscriptions\Traits\HasPlanSubscriptions;
use Musonza\Chat\Traits\Messageable;
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
    'website',
    'email',
    'phones',
    'social_links',
    'review_status',
    'reviewed_at',
    'is_verified',
    'is_featured',
    'employee_range',
])]
#[Translatable([
    'name',
    'legal_name',
    'description',
    'summary',
])]
#[ObservedBy(CompanyObserver::class)]
class Company extends Model implements HasMedia
{
    use HasFactory,
        HasPlanSubscriptions,
        HasTranslations,
        InteractsWithMedia,
        InteractsWithRichContent,
        SoftDeletes;
    use HasSeo;
    use Messageable;

    /**
     * Owner-editable attributes that are subject to admin review; changing
     * any of them sends the draft back to pending review (the public
     * publication snapshot stays untouched until the next approval).
     *
     * @var list<string>
     */
    public const REVIEWED_ATTRIBUTES = [
        'name',
        'legal_name',
        'legal_type',
        'description',
        'summary',
        'website',
        'email',
        'phones',
        'social_links',
        'employee_range',
        'established_at',
    ];

    protected function casts(): array
    {
        return [
            'established_at' => 'date',
            'reviewed_at' => 'datetime',
            'is_verified' => 'boolean',
            'is_featured' => 'boolean',
            'review_status' => CompanyReviewStatus::class,
            'legal_type' => CompanyType::class,
            'phones' => 'array',
            'social_links' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('featured_image')->singleFile();
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

    /**
     * Chat participant identity. The display name comes from the PUBLISHED
     * snapshot (in the current request locale) because only published
     * companies have public pages and are therefore chattable; the draft name
     * is only a defensive fallback.
     *
     * @return array{name: string, type: string, avatar_url: ?string}
     */
    public function getParticipantDetailsAttribute(): array
    {
        $publication = $this->publication;

        return [
            'name' => (string) ($publication?->name ?? $this->name),
            'type' => 'company',
            'avatar_url' => $publication?->getFirstMediaUrl('logo', 'webp') ?: null,
        ];
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

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function publication(): HasOne
    {
        return $this->hasOne(CompanyPublication::class);
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->activePlanSubscriptions()->first();
    }

    public function primaryAddress(): HasOne
    {
        return $this->hasOne(CompanyAddress::class)->where('is_primary', true);
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
