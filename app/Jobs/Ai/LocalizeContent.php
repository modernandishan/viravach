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
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Step 4: localize the source payload and its SEO block into every
 * supported locale except the source one, using the translation model. A
 * single locale failing must not abort the others — failures are collected
 * and only escalate when EVERY locale failed.
 */
class LocalizeContent extends AbstractAiContentJob
{
    public const STEP = 4;

    public $timeout = 900;

    public function handle(): void
    {
        $content = $this->contentRow();

        if ($content->step >= static::STEP) {
            return;
        }

        $this->advanceStep($content);

        $company = $this->company();
        $input = app(CompanyInputCollector::class)->collect($company);
        $siteText = app(WebsiteTextExtractor::class)->extract($input['website'] ?? null);

        $aiPayload = $content->ai_payload ?? [];
        $source = $aiPayload[CompanyContentPrompt::SOURCE_LOCALE]
            ?? throw new ContentGenerationException('No source content payload to localize.');

        $countries = $source['markets']['countries'] ?? [];
        $settings = $this->settings();

        // Never hardcode the locale list — the project's supportedLocales
        // config is the single source.
        $targets = array_keys((array) config('laravellocalization.supportedLocales'));
        $targets = array_values(array_filter(
            $targets,
            fn (string $locale): bool => $locale !== CompanyContentPrompt::SOURCE_LOCALE,
        ));

        $failures = [];

        foreach ($targets as $locale) {
            try {
                $contentPayload = $this->completeValidated(
                    CompanyContentLocalizationPrompt::system($locale),
                    CompanyContentLocalizationPrompt::user($source, $input),
                    $settings->translation_model,
                    fn (array $candidate): array => CompanyContentSchema::validate($candidate),
                    fn (array $candidate): array => CompanyContentSchema::repair($candidate),
                );

                // The same verified export data backs every locale.
                $contentPayload['markets']['countries'] = $countries;

                $seo = $this->completeValidated(
                    CompanySeoPrompt::system($locale),
                    CompanySeoPrompt::user($input, $siteText),
                    $settings->translation_model,
                    fn (array $candidate): array => CompanySeoSchema::validate($candidate),
                    fn (array $candidate): array => CompanySeoSchema::repair($candidate),
                );

                $seo = $this->reserveSeoKeyword($company, $locale, $seo);

                $aiPayload[$locale] = $contentPayload;
                $aiPayload['seo'][$locale] = $seo;

                $content->forceFill(['ai_payload' => $aiPayload])->save();
            } catch (Throwable $exception) {
                $failures[$locale] = $exception->getMessage();

                // NEVER silent: a swallowed locale failure looks like a
                // successful run with a missing language. Log it and leave
                // the evidence on the row (below) for the admin.
                Log::warning('Content localization failed for locale.', [
                    'company_id' => $this->companyId,
                    'locale' => $locale,
                    'reason' => $exception->getMessage(),
                ]);
            }
        }

        // Partial results are surfaced on the row even though the job
        // succeeds overall — FinalizeContent's markReady leaves this field
        // untouched, so the admin sees exactly which locales are missing.
        if ($failures !== []) {
            $content->forceFill(['failure_reason' => 'Partial localization — failed locale(s): '.implode(
                ' | ',
                array_map(
                    fn (string $locale, string $reason): string => "{$locale}: {$reason}",
                    array_keys($failures),
                    $failures,
                ),
            )])->save();
        }

        if ($failures !== [] && count($failures) === count($targets)) {
            throw new ContentGenerationException(
                'All locale localizations failed: '.implode(' | ', array_map(
                    fn (string $locale, string $reason): string => "{$locale}: {$reason}",
                    array_keys($failures),
                    $failures,
                )),
            );
        }
    }
}
