<?php

namespace App\Jobs\Ai;

use App\Ai\Exceptions\ContentGenerationException;
use App\Ai\Input\CompanyInputCollector;
use App\Ai\Input\WebsiteTextExtractor;
use App\Ai\Prompts\CompanyContentPrompt;
use App\Ai\Prompts\CompanySeoPrompt;
use App\Ai\Schemas\CompanySeoSchema;

/**
 * Step 2: generate the English SEO block (meta title/description, focus
 * keyword, keyword candidates) against the source content.
 */
class GenerateSeoBlock extends AbstractAiContentJob
{
    public const STEP = 2;

    public $timeout = 300;

    public function handle(): void
    {
        $content = $this->contentRow();

        if ($content->step >= static::STEP) {
            return;
        }

        $this->advanceStep($content);

        if (($content->ai_payload[CompanyContentPrompt::SOURCE_LOCALE] ?? null) === null) {
            throw new ContentGenerationException('No source content payload to derive SEO from.');
        }

        $input = app(CompanyInputCollector::class)->collect($this->company());

        // Re-reads hit the extractor's 24-hour cache from step 1.
        $siteText = app(WebsiteTextExtractor::class)->extract($input['website'] ?? null);

        $seo = $this->completeValidated(
            CompanySeoPrompt::system(CompanyContentPrompt::SOURCE_LOCALE),
            CompanySeoPrompt::user($input, $siteText),
            $this->settings()->model,
            fn (array $candidate): array => CompanySeoSchema::validate($candidate),
            fn (array $candidate): array => CompanySeoSchema::repair($candidate),
        );

        $this->mergeSeoPayload($content, CompanyContentPrompt::SOURCE_LOCALE, $seo);
    }
}
