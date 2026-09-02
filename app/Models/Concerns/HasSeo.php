<?php

namespace App\Models\Concerns;

use App\Models\SeoMeta;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasSeo
{
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    /**
     * Push this model's full SEO output (title/description/canonical,
     * keywords, robots, Open Graph, Twitter Card, JSON-LD) into SEOTools,
     * to be rendered by the layout's SEOTools::generate() call. Meant to be
     * called once from a public page's mount(). Everything beyond
     * title/description/canonical only renders when a SeoMeta row exists.
     */
    public function applySeoTags(): void
    {
        // Deliberately scoped to metatags(): the SEOTools::setTitle()/
        // setDescription() facade shortcuts would also push the values into
        // OpenGraph/Twitter/JSON-LD, making those blocks render even
        // without a SeoMeta row. OG/Twitter/JSON-LD are fed explicitly
        // below, only when SeoMeta exists.
        SEOTools::metatags()->setTitle($this->seoTitle());

        if ($description = $this->seoDescription()) {
            SEOTools::metatags()->setDescription($description);
        }

        $canonical = $this->seo?->canonical_url ?: $this->currentCanonicalUrl();

        SEOTools::setCanonical($canonical);

        if (! $seo = $this->seo) {
            // No SeoMeta row means no administrator has made an explicit
            // robots choice for this record — that is also the only case
            // isThinPage() is allowed to act in, so a real SeoMeta row's
            // robots_index/robots_follow (below) always wins over it.
            if ($this->isThinPage()) {
                SEOTools::metatags()->setRobots('noindex, follow');
            }

            return;
        }

        $locale = app()->getLocale();

        $keywords = collect(explode(',', (string) $seo->getTranslation('meta_keywords', $locale, false)))
            ->map(fn (string $keyword) => trim($keyword))
            ->filter()
            ->values();

        if ($keywords->isNotEmpty()) {
            SEOTools::metatags()->setKeywords($keywords->all());
        }

        // Cornerstone (pillar) content must never end up accidentally
        // noindexed, so it overrides the manual robots toggles.
        $robots = $seo->is_cornerstone
            ? 'index, follow'
            : ($seo->robots_index ? 'index' : 'noindex').', '.($seo->robots_follow ? 'follow' : 'nofollow');

        SEOTools::metatags()->setRobots($robots);

        $ogImageUrl = $seo->getFirstMediaUrl('og_image', 'webp') ?: null;

        $opengraph = SEOTools::opengraph();
        $opengraph->setType($seo->og_type ?: 'website');

        if ($ogTitle = $seo->getTranslation('og_title', $locale, false) ?: $this->seoTitle()) {
            $opengraph->setTitle($ogTitle);
        }

        if ($ogDescription = $seo->getTranslation('og_description', $locale, false) ?: $this->seoDescription()) {
            $opengraph->setDescription($ogDescription);
        }

        if ($ogImageUrl) {
            $opengraph->addImage($ogImageUrl);
        }

        $twitter = SEOTools::twitter();
        $twitter->setType($seo->twitter_card_type ?: 'summary_large_image');

        if ($twitterTitle = $seo->getTranslation('twitter_title', $locale, false) ?: $this->seoTitle()) {
            $twitter->setTitle($twitterTitle);
        }

        if ($twitterDescription = $seo->getTranslation('twitter_description', $locale, false) ?: $this->seoDescription()) {
            $twitter->setDescription($twitterDescription);
        }

        if ($twitterImageUrl = $seo->getFirstMediaUrl('twitter_image', 'webp') ?: null) {
            $twitter->addImage($twitterImageUrl);
        }

        // schema.org output requires an @type, so schema_type doubles as
        // the on/off switch for the whole JSON-LD block. The layout's
        // SEOTools::generate() renders jsonLdMulti(), not jsonLd(), so the
        // values go there.
        if ($seo->schema_type) {
            $jsonLd = SEOTools::jsonLdMulti();
            $jsonLd->setType($seo->schema_type);
            $jsonLd->setUrl($canonical);

            if ($title = $this->seoTitle()) {
                $jsonLd->setTitle($title);
            }

            if ($description = $this->seoDescription()) {
                $jsonLd->setDescription($description);
            }

            if ($ogImageUrl) {
                $jsonLd->addImage($ogImageUrl);
            }

            foreach ($seo->schema_extra ?? [] as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $jsonLd->addValue($key, $value);
            }
        }
    }

    /**
     * url()->current() drops the query string entirely, which would make
     * every paginated page canonicalise back to page 1 — telling search
     * engines pages 2+ are duplicates and dropping every company listed on
     * them out of the index.
     *
     * `page` is the one parameter that genuinely changes what the page
     * contains, so it alone is preserved. Filter parameters (category,
     * state, verified, sort) are deliberately NOT included: a filtered view
     * is a slice of the same collection and should consolidate onto the
     * clean URL rather than compete with it.
     */
    protected function currentCanonicalUrl(): string
    {
        $page = (int) request()->query('page', 1);

        return $page > 1
            ? url()->current().'?page='.$page
            : url()->current();
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

    /**
     * Whether this record's public page is currently "thin" (no real
     * content behind it — e.g. a taxonomy node with zero published
     * companies) and should therefore be forced to noindex,follow when no
     * SeoMeta row exists to override it. Stable taxonomy URLs (country/
     * state/category) stay live and linkable — they must not 404 — but
     * must not be indexed while empty. Default false: page types that
     * don't have a "thin" concept (Page, Company, Plan) are unaffected.
     */
    protected function isThinPage(): bool
    {
        return false;
    }
}
