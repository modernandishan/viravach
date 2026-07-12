<?php

namespace App\Models\Concerns;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasSeo
{
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    public function seoTitle(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->seo?->getTranslation('meta_title', $locale, false)
            ?: $this->getSeoFallbackTitle($locale);
    }

    public function seoDescription(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->seo?->getTranslation('meta_description', $locale, false)
            ?: $this->getSeoFallbackDescription($locale);
    }

    /**
     * مدل مصرف‌کننده (مثلا Company) این دو متد را override می‌کند
     * تا fallback مناسب خودش (name، description خلاصه‌شده و ...) را بدهد.
     */
    protected function getSeoFallbackTitle(string $locale): ?string
    {
        return method_exists($this, 'getTranslation')
            ? $this->getTranslation('name', $locale, false)
            : null;
    }

    protected function getSeoFallbackDescription(string $locale): ?string
    {
        return null;
    }
}
