<?php

namespace App\Jobs\Ai;

use App\Enums\CompanyReviewStatus;
use App\Services\Ai\ContentGenerationService;

/**
 * Step 5, the chain terminator: copy the validated payloads onto the
 * Company (locale-keyed content, per-locale SEO metas), bump the generation
 * counter, mark the row ready, release the lock, and send the draft back to
 * admin review. Never publishes — that is CompanyPublicationService's and
 * the admin's job alone.
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

        $content->forceFill([
            'generations_count' => $content->generations_count + 1,
        ])->save();

        app(ContentGenerationService::class)->markReady($content);
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
