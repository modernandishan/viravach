<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasTranslatableSlug;
use CyrildeWit\EloquentViewable\Contracts\Viewable;
use CyrildeWit\EloquentViewable\InteractsWithViews;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

#[Fillable([
    'parent_id',
    'slug',
    'title',
    'description',
    'sort_order',
    'is_active',
])]
#[Translatable([
    'title',
    'description',
])]
class CompanyCategory extends Model implements HasMedia, Viewable
{
    use HasFactory,
        HasRecursiveRelationships,
        HasSeo,
        HasTranslatableSlug,
        HasTranslations,
        InteractsWithMedia,
        InteractsWithViews,
        SoftDeletes;
    use InteractsWithRichContent;

    /**
     * Categories are labelled by `title`, not `name`
     * ({@see HasTranslatableSlug}).
     */
    protected function slugSourceAttribute(): string
    {
        return 'title';
    }

    // public array $translatable = ['title', 'description'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')->singleFile();
        $this->addMediaCollection('logo')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->queued();
    }

    protected function getSeoFallbackTitle(string $locale): ?string
    {
        return $this->getTranslation('title', $locale, false);
    }

    /**
     * Layered: (1) the category's own translated `description`, stripped of
     * markup and truncated at a word boundary — unique prose outperforms a
     * templated string for SEO; (2) if that locale has no description, the
     * count-based string (mirrors State/Country); (3) null if the published
     * count is also zero. No SeoMeta row exists for most categories today
     * (CompanyCategoryForm only just gained a SeoMetaSection), so this is
     * the description that actually ships on /category/{slug} pages.
     */
    protected function getSeoFallbackDescription(string $locale): ?string
    {
        $description = trim(strip_tags((string) $this->getTranslation('description', $locale, false)));

        if ($description !== '') {
            // preserveWords: true also strips tags internally, but only on
            // the truncation path — the early-return path for a description
            // already shorter than the limit would otherwise return it with
            // its HTML still intact, which is why it's stripped above too.
            return Str::limit($description, 160, '...', preserveWords: true);
        }

        $count = $this->publishedCompaniesCount();

        if ($count === 0) {
            return null;
        }

        return __('company_categories.seo_fallback_description', [
            'count' => number_format($count),
            'category' => $this->getTranslation('title', $locale, false),
        ], $locale);
    }

    /**
     * A category with no published companies (in itself or any descendant)
     * is a thin page: it must stay live (it's linked from navigation), but
     * should not be indexed until it has real content behind it.
     */
    protected function isThinPage(): bool
    {
        return $this->publishedCompaniesCount() === 0;
    }

    /**
     * Published companies attached to this category or any of its
     * descendants — same category-subtree shape ⚡company-category.blade.php
     * already lists companies with, so the count matches what the page
     * actually shows.
     */
    protected function publishedCompaniesCount(): int
    {
        $categoryIds = $this->descendantsAndSelf()->pluck('id');

        return CompanyPublication::query()
            ->active()
            ->whereHas('categories', fn ($query) => $query->whereIn('company_categories.id', $categoryIds))
            ->count();
    }
}
