<?php

namespace App\Models;

use App\Enums\CompanyType;
use App\Models\Concerns\HasSeo;
use CyrildeWit\EloquentViewable\Contracts\Viewable;
use CyrildeWit\EloquentViewable\InteractsWithViews;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * Public snapshot of an approved Company. Everything the public site serves
 * (profile page, listings, SEO, view recording) reads from this model, never
 * from the live Company draft. Rows are only written by
 * CompanyPublicationService and survive deletion of the source company.
 */
#[Fillable([
    'company_id',
    'slug',
    'name',
    'legal_name',
    'legal_type',
    'registration_number',
    'national_id',
    'established_at',
    'summary',
    'content',
    'website',
    'email',
    'phones',
    'social_links',
    'is_verified',
    'is_featured',
    'employee_range',
    'state_id',
    'published_at',
])]
#[Translatable([
    'name',
    'legal_name',
    'summary',
])]
class CompanyPublication extends Model implements HasMedia, Viewable
{
    use HasFactory,
        HasSeo,
        HasTranslations,
        InteractsWithMedia,
        InteractsWithViews;

    protected function casts(): array
    {
        return [
            'established_at' => 'date',
            'published_at' => 'datetime',
            'is_verified' => 'boolean',
            'is_featured' => 'boolean',
            'legal_type' => CompanyType::class,
            'phones' => 'array',
            'social_links' => 'array',
            'content' => 'array',
        ];
    }

    /**
     * The AI-generated content payload for the given locale, falling back
     * to the site default locale. Stored as a plain locale-keyed map (not
     * spatie-translatable) because the payload is a nested structure.
     */
    public function contentFor(?string $locale = null): ?array
    {
        $locale ??= app()->getLocale();

        $content = $this->content;

        if (! is_array($content)) {
            return null;
        }

        return $content[$locale] ?? $content[config('app.fallback_locale')] ?? null;
    }

    /**
     * Short excerpt for listing cards: the AI-generated hero subheadline,
     * falling back to the first sentence of the about section, then to the
     * legacy summary field. Returns null when none of those are available,
     * so callers can omit the excerpt element entirely.
     */
    public function excerpt(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $content = $this->contentFor($locale);

        $subheadline = trim(strip_tags((string) ($content['hero']['subheadline'] ?? '')));

        if ($subheadline !== '') {
            return $subheadline;
        }

        $body = trim(strip_tags((string) ($content['about']['body'] ?? '')));

        if ($body !== '') {
            return Str::limit(self::firstSentence($body), 160);
        }

        $summary = trim(strip_tags((string) $this->getTranslation('summary', $locale, false)));

        return $summary !== '' ? $summary : null;
    }

    private static function firstSentence(string $text): string
    {
        $sentences = preg_split('/(?<=[.!?؟])\s+/u', $text, 2) ?: [$text];

        return trim((string) ($sentences[0] ?? ''));
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CompanyCategory::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Publicly visible publications (excludes snapshots whose published_at
     * is still scheduled in the future).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('published_at', '<=', now());
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
