<?php

namespace App\Jobs\Ai;

use App\Ai\Exceptions\ContentGenerationException;
use App\Ai\Input\CompanyInputCollector;
use App\Ai\Input\WebsiteTextExtractor;
use App\Ai\Prompts\CompanyContentLocalizationPrompt;
use App\Ai\Prompts\CompanyContentPrompt;
use App\Ai\Prompts\CompanySeoPrompt;
use App\Ai\Schemas\CompanyContentSchema;
use App\Ai\Schemas\CompanySeoSchema;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * One target locale's slice of step 4, dispatched as a sibling job within
 * LocalizeContent's Bus::batch (see LocalizeContent) so the four non-source
 * locales run concurrently instead of one after another. Runs entirely
 * independently of the other locales: re-derives its own input/site-text
 * context, does the content call and the SEO call, validates with repair
 * and the one correction retry, reserves the keyword, then merges the
 * result into ai_payload under a row lock (AbstractAiContentJob).
 *
 * A failure here is caught internally rather than left to throw: with the
 * `sync` queue connection (used in tests, and a legitimate fallback
 * connection), an uncaught exception from one job in a Bus::batch aborts
 * the dispatch of the jobs still queued behind it in the same bulk() call,
 * which would silently skip the remaining locales instead of merely
 * failing this one. Catching internally guarantees every sibling locale
 * always gets its turn, on every queue driver.
 */
class LocalizeContentLocale extends AbstractAiContentJob
{
    use Batchable;

    public $timeout = 450;

    public $uniqueFor = 450;

    public function __construct(
        int $companyId,
        public readonly string $locale,
    ) {
        parent::__construct($companyId);
    }

    public function uniqueId(): string
    {
        return 'ai-content-'.$this->companyId.'-locale-'.$this->locale;
    }

    public function handle(): void
    {
        $content = $this->contentRow();
        $company = $this->company();

        try {
            $input = app(CompanyInputCollector::class)->collect($company);

            // Re-reads hit the extractor's 24-hour cache from step 1.
            $siteText = app(WebsiteTextExtractor::class)->extract($input['website'] ?? null);

            $aiPayload = $content->ai_payload ?? [];
            $source = $aiPayload[CompanyContentPrompt::SOURCE_LOCALE]
                ?? throw new ContentGenerationException('No source content payload to localize.');

            $countries = $source['markets']['countries'] ?? [];
            $settings = $this->settings();

            $contentPayload = $this->completeValidated(
                CompanyContentLocalizationPrompt::system($this->locale),
                CompanyContentLocalizationPrompt::user($source, $input),
                $settings->translation_model,
                fn (array $candidate): array => CompanyContentSchema::validate($candidate),
                fn (array $candidate): array => CompanyContentSchema::repair($candidate),
            );

            // The same verified export data backs every locale.
            $contentPayload['markets']['countries'] = $countries;

            $seo = $this->completeValidated(
                CompanySeoPrompt::system($this->locale),
                CompanySeoPrompt::user($input, $siteText),
                $settings->translation_model,
                fn (array $candidate): array => CompanySeoSchema::validate($candidate),
                fn (array $candidate): array => CompanySeoSchema::repair($candidate),
            );

            $seo = $this->reserveSeoKeyword($company, $this->locale, $seo);

            $this->mergePayload($content, $this->locale, $contentPayload);
            $this->mergeSeoPayload($content, $this->locale, $seo);
        } catch (Throwable $exception) {
            // NEVER silent: a swallowed locale failure looks like a
            // successful run with a missing language. Log it and leave the
            // evidence on the row (recordLocaleFailure) for the admin.
            Log::warning('Content localization failed for locale.', [
                'company_id' => $this->companyId,
                'locale' => $this->locale,
                'reason' => $exception->getMessage(),
            ]);

            $this->recordLocaleFailure($this->companyId, $this->locale, $exception->getMessage());
        }
    }

    /**
     * Defense in depth for anything that escapes the try/catch in handle()
     * (e.g. the queue infrastructure itself). Never fail the whole
     * CompanyContent row for one locale's crash — that is exactly the
     * "must not abort the others" rule this job exists to uphold.
     */
    public function failed(Throwable $exception): void
    {
        Log::warning('Content localization failed for locale.', [
            'company_id' => $this->companyId,
            'locale' => $this->locale,
            'reason' => $exception->getMessage(),
        ]);

        $this->recordLocaleFailure($this->companyId, $this->locale, $exception->getMessage());
    }
}
