<?php

namespace App\Jobs\Ai;

use App\Ai\Exceptions\ContentGenerationException;
use App\Ai\Prompts\CompanyContentPrompt;

/**
 * Step 3: reserve the English focus keyword. The candidate walk, the
 * qualifier fallback and the unique-index arbiter all live in
 * AbstractAiContentJob::reserveSeoKeyword(); a collision never fails the
 * chain.
 */
class ReserveKeyword extends AbstractAiContentJob
{
    public const STEP = 3;

    public $timeout = 120;

    public function handle(): void
    {
        $content = $this->contentRow();

        if ($content->step >= static::STEP) {
            return;
        }

        $this->advanceStep($content);

        $aiPayload = $content->ai_payload ?? [];
        $seo = $aiPayload['seo'][CompanyContentPrompt::SOURCE_LOCALE] ?? null;

        if ($seo === null) {
            throw new ContentGenerationException('No SEO block to reserve a keyword from.');
        }

        $aiPayload['seo'][CompanyContentPrompt::SOURCE_LOCALE] = $this->reserveSeoKeyword(
            $this->company(),
            CompanyContentPrompt::SOURCE_LOCALE,
            $seo,
        );

        $content->forceFill(['ai_payload' => $aiPayload])->save();
    }
}
