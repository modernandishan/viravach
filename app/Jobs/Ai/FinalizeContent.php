<?php

namespace App\Jobs\Ai;

use App\Ai\Prompts\CompanyContentPrompt;
use App\Enums\CompanyReviewStatus;
use App\Models\Company;
use App\Services\Ai\ContentGenerationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Step 5, the chain terminator: copy the validated payloads onto the
 * Company (locale-keyed content, per-locale SEO metas), regenerate a
 * readable slug when the old one was machine-made, bump the generation
 * counter, mark the row ready, release the lock, and send the draft back
 * to admin review. Never publishes — that is CompanyPublicationService's
 * and the admin's job alone.
 */
class FinalizeContent extends AbstractAiContentJob
{
    public const STEP = 5;

    public $timeout = 120;

    public function handle(): void
    {
        $content = $this->contentRow();

        if ($content->step >= static::STEP) {
            return;
        }

        $this->advanceStep($content);

        $company = $this->company();
        $aiPayload = $content->ai_payload ?? [];

        // Locale-keyed content only — the seo sub-array is NOT company content.
        $company->forceFill([
            'content' => collect($aiPayload)->except('seo')->all(),
            'review_status' => CompanyReviewStatus::PendingReview,
        ])->save();

        $this->writeSeoMetas($company, (array) ($aiPayload['seo'] ?? []));

        $this->regenerateSlug($company);

        $content->forceFill([
            'generations_count' => $content->generations_count + 1,
        ])->save();

        app(ContentGenerationService::class)->markReady($content);
    }

    /**
     * Slug regeneration rule: only ever touch a slug the machine itself
     * made (a transliteration of a Persian name — unreadable and worthless
     * for search), and only while the company has never been published.
     * Once a page is live its URL is public and must not change, and a
     * slug that does not match the name-derived form was chosen by a human
     * and is kept no matter what.
     */
    protected function regenerateSlug(Company $company): void
    {
        if (! self::slugShouldBeRegenerated($company)) {
            return;
        }

        $base = self::deriveContentSlugBase($company);

        if ($base === null) {
            return;
        }

        $newSlug = $company->regenerateSlugFromBase($base);

        if ($newSlug === $company->slug) {
            return;
        }

        Log::info('Company slug regenerated from generated content.', [
            'company_id' => $company->id,
            'old' => $company->slug,
            'new' => $newSlug,
        ]);

        $company->forceFill(['slug' => $newSlug])->save();
    }

    /**
     * Whether this company's slug was machine-generated and may be
     * replaced: never-published only, and the current slug must be exactly
     * what HasTranslatableSlug would have derived from the name (base, or
     * base + random suffix) — anything else was chosen by a human.
     */
    public static function slugShouldBeRegenerated(Company $company): bool
    {
        // Once a page is live its URL is public and must not change.
        if ($company->publication()->exists()) {
            return false;
        }

        $nameBase = self::nameDerivedSlugBase($company);

        if ($nameBase === '') {
            // No name-derived base exists: the slug was a random fallback,
            // i.e. machine-made.
            return true;
        }

        return Str::startsWith($company->slug, [$nameBase, $nameBase.'-']);
    }

    /**
     * The best slug base the generated English content offers, or null when
     * there is nothing usable: the English name translation first, then the
     * English payload's hero headline trimmed to its first meaningful words.
     */
    public static function deriveContentSlugBase(Company $company): ?string
    {
        $englishName = trim((string) $company->getTranslation('name', CompanyContentPrompt::SOURCE_LOCALE, false));

        if ($englishName !== '') {
            return $englishName;
        }

        $headline = trim((string) data_get($company->content, CompanyContentPrompt::SOURCE_LOCALE.'.hero.headline', ''));

        if ($headline === '') {
            return null;
        }

        return self::meaningfulHeadlineWords($headline);
    }

    /**
     * What the slug trait would have derived from the name itself (before
     * any suffix), so a machine-made slug can be recognised later.
     */
    protected static function nameDerivedSlugBase(Company $company): string
    {
        $translations = $company->getTranslations('name');

        $base = Str::slug((string) ($translations['en'] ?? ''));

        if ($base === '') {
            $base = Str::slug((string) ($translations[(string) config('app.fallback_locale')] ?? ''));
        }

        return $base;
    }

    /**
     * First 5-6 meaningful words of the headline: leading articles dropped,
     * trailing prepositions/conjunctions dropped.
     */
    protected static function meaningfulHeadlineWords(string $headline): string
    {
        $words = preg_split('/\s+/u', trim($headline)) ?: [];

        $articles = ['the', 'a', 'an'];
        while ($words !== [] && in_array(mb_strtolower((string) $words[0]), $articles, true)) {
            array_shift($words);
        }

        $words = array_slice($words, 0, 5);

        $trailers = ['for', 'of', 'in', 'to', 'with', 'from', 'by', 'at', 'on', 'and', 'or'];
        while (count($words) > 1 && in_array(mb_strtolower((string) end($words)), $trailers, true)) {
            array_pop($words);
        }

        return implode(' ', $words);
    }

    /**
     * Written through the existing HasSeo morphOne relation — the same
     * mechanism every other consumer uses; no new persistence path.
     *
     * @param  array<string, array<string, mixed>>  $seoByLocale
     */
    protected function writeSeoMetas($company, array $seoByLocale): void
    {
        $seoMeta = $company->seo()->firstOrCreate([]);

        foreach ($seoByLocale as $locale => $block) {
            $seoMeta->setTranslation('meta_title', $locale, (string) ($block['meta_title'] ?? ''));
            $seoMeta->setTranslation('meta_description', $locale, (string) ($block['meta_description'] ?? ''));
            $seoMeta->setTranslation('focus_keyword', $locale, (string) ($block['focus_keyword'] ?? ''));
            // meta_keywords is consumed as a comma-separated string in HasSeo.
            $seoMeta->setTranslation('meta_keywords', $locale, implode(',', (array) ($block['meta_keywords'] ?? [])));
        }

        $seoMeta->save();
    }
}
