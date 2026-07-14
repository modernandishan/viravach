<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use Laravelcm\Subscriptions\Models\Plan as BasePlan;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Plan extends BasePlan implements HasMedia
{
    use HasSeo, InteractsWithMedia;

    protected function getSeoFallbackTitle(string $locale): ?string
    {
        return $this->getTranslation('name', $locale, false);
    }

    protected function getSeoFallbackDescription(string $locale): ?string
    {
        return $this->getTranslation('description', $locale, false);
    }

    /**
     * The package's HasTranslations trait flattens translatable attributes down
     * to the current app locale in attributesToArray() (for API-style output),
     * which breaks Filament's dot-notation translatable form fields (e.g.
     * "name.{locale}") since EditRecord hydrates the form from this method.
     * Restore the full per-locale translations array here so every locale tab
     * fills correctly.
     */
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        foreach ($this->getTranslatableAttributes() as $key) {
            $attributes[$key] = $this->getTranslations($key);
        }

        return $attributes;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->performOnCollections('image')
            ->queued();
    }
}
