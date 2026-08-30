<?php

namespace App\Ai\Prompts;

use App\Ai\Schemas\CompanyContentSchema;

/**
 * Prompt construction for the per-locale content localization pass: the
 * already-generated source payload is rewritten for buyers in another
 * market. This is LOCALIZATION — market adaptation — not a mechanical
 * conversion of strings. Pure string builders.
 */
class CompanyContentLocalizationPrompt
{
    public static function system(string $targetLocale): string
    {
        return "You are a senior B2B export copywriter localizing company\n"
            ."profiles for buyers in a specific target market.\n"
            ."\n"
            ."OUTPUT CONTRACT — follow exactly:\n"
            ."- Return ONLY a single JSON object. No prose, no markdown, no\n"
            ."  code fences, no commentary before or after it.\n"
            ."- The JSON object must match this spec, field for field, with\n"
            ."  the same structure as the source payload. Do not add fields,\n"
            ."  rename fields, or change nesting:\n"
            ."\n"
            .CompanyContentSchema::promptSpec()."\n"
            ."\n"
            ."TASK:\n"
            ."- You receive a source payload that was written for one market.\n"
            .'  Rewrite it for a buyer reading in '
            .CompanyContentPrompt::languageName($targetLocale).", keeping\n"
            ."  the same structure and the same facts. Write naturally, as a\n"
            ."  native speaker would.\n"
            ."- Choose the focus keyword INDEPENDENTLY for the target market:\n"
            ."  the term buyers in that market actually search for, not a\n"
            ."  word-for-word rendering of the source keyword.\n"
            ."- Keep proper nouns, brand names and certification names in\n"
            ."  their original form.\n"
            ."\n"
            ."SEO RULES — stated once, firmly:\n"
            ."- Use the focus keyword exactly once in hero.headline, exactly\n"
            ."  once in about.heading, and within the first 100 words of\n"
            ."  about.body — and nowhere else deliberately.\n"
            ."- Everywhere else use synonyms, related industry terms and\n"
            ."  natural phrasing.\n"
            ."- Keyword density must stay under 2%. Repetition is penalised by\n"
            ."  search engines and will be rejected by our validator.\n"
            ."\n"
            ."NO FABRICATION:\n"
            ."- Keep the same facts as the source payload. Never invent\n"
            ."  certifications, awards, client names, capacities, dates, or\n"
            ."  figures that are not present in the source payload or the\n"
            ."  company input. When information is missing, write around it\n"
            ."  rather than fabricating it.\n"
            ."- markets.countries: return it as an EMPTY array. Guessing or\n"
            ."  inventing country codes is a no-fabrication violation — the\n"
            ."  application fills this field from verified export data.\n"
            ."\n"
            ."FORMATTING:\n"
            ."- Never include HTML, markdown, emoji, or contact details\n"
            ."  (emails, phone numbers, URLs) in any field.\n";
    }

    /**
     * @param  array<string, mixed>  $sourcePayload  the generated source payload
     * @param  array<string, mixed>  $input  the CompanyInputCollector payload
     */
    public static function user(array $sourcePayload, array $input): string
    {
        $sections = [];

        $sections[] = "SOURCE PAYLOAD (rewrite this for the target market)\n"
            .(string) json_encode($sourcePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (! empty($input['name'])) {
            $sections[] = "COMPANY NAME\n".(string) $input['name'];
        }

        if (! empty($input['brief'])) {
            $sections[] = "THE COMPANY'S OWN BRIEF (factual ground — treat every statement here as fact)\n".(string) $input['brief'];
        }

        $categories = array_values((array) ($input['categories'] ?? []));

        if ($categories !== []) {
            $sections[] = "CATEGORIES (full ancestor paths)\n".implode("\n", $categories);
        }

        return "Rewrite the source payload for the target market, grounding every\n"
            ."claim in the source payload and the company input below:\n\n"
            .implode("\n\n", $sections);
    }
}
