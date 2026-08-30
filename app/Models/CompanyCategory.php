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

    protected function getSeoFallbackDescription(string $locale): ?string
    {
        $description = $this->getTranslation('description', $locale, false);

        return $description ? Str::limit(strip_tags($description), 160) : null;
    }
}
