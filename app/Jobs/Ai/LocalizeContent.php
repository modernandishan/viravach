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
                );

                // The same verified export data backs every locale.
                $contentPayload['markets']['countries'] = $countries;

                $seo = $this->completeValidated(
                    CompanySeoPrompt::system($locale),
                    CompanySeoPrompt::user($input, $siteText),
                    $settings->translation_model,
                    fn (array $candidate): array => CompanySeoSchema::validate($candidate),
                );

                $seo = $this->reserveSeoKeyword($company, $locale, $seo);

                $aiPayload[$locale] = $contentPayload;
                $aiPayload['seo'][$locale] = $seo;

                $content->forceFill(['ai_payload' => $aiPayload])->save();
            } catch (Throwable $exception) {
                $failures[$locale] = $exception->getMessage();
            }
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
