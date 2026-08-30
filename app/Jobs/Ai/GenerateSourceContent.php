<?php

namespace App\Jobs\Ai;

use App\Ai\Input\CompanyInputCollector;
use App\Ai\Input\WebsiteTextExtractor;
use App\Ai\Prompts\CompanyContentPrompt;
use App\Ai\Schemas\CompanyContentSchema;
use App\Models\Country;
use Illuminate\Support\Collection;

/**
 * Step 1: generate the English (source) content payload from the company's
 * collected input, with the user's website text as optional untrusted
 * context. The model's markets.countries output is always discarded and
 * rebuilt from verified export-country data.
 */
class GenerateSourceContent extends AbstractAiContentJob
{
    public const STEP = 1;

    public $timeout = 600;

    public function handle(): void
    {
        $content = $this->contentRow();

        if ($content->step >= static::STEP) {
            return;
        }

        $this->advanceStep($content);

        $company = $this->company();
        $input = app(CompanyInputCollector::class)->collect($company);

        // A null result (down site, non-HTML, empty page) is normal — the
        // pipeline simply proceeds without site context.
        $siteText = app(WebsiteTextExtractor::class)->extract($input['website'] ?? null);

        $payload = $this->completeValidated(
            CompanyContentPrompt::system(CompanyContentPrompt::SOURCE_LOCALE),
            CompanyContentPrompt::user($input, $siteText),
            $this->settings()->model,
            fn (array $candidate): array => CompanyContentSchema::validate($candidate),
        );

        // Never trust the model's country guesses: rebuild the field from
        // verified export data, mapped to ISO alpha-2 codes.
        $payload['markets']['countries'] = $this->resolveCountryCodes($input);

        $this->mergePayload($content, CompanyContentPrompt::SOURCE_LOCALE, $payload);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<string>
     */
    protected function resolveCountryCodes(array $input): array
    {
        $names = array_values((array) ($input['export_countries'] ?? []));

        if ($names === []) {
            return [];
        }

        // The collector resolved the names in the brief's locale, so the
        // lookup uses that same locale translation.
        $locale = (string) ($input['brief_locale'] ?: CompanyContentPrompt::SOURCE_LOCALE);

        $countries = Country::query()
            ->whereIn("name->{$locale}", $names)
            ->get()
            ->keyBy(fn (Country $country): string => (string) $country->getTranslation('name', $locale));

        $codes = [];

        foreach ($names as $name) {
            /** @var Country|null $match */
            $match = $countries->get((string) $name);

            if ($match !== null && ($code = $match->iso2) !== null) {
                $codes[] = $code;
            }
        }

        return array_values(array_unique((new Collection($codes))->all()));
    }
}
