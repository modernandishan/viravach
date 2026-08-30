<?php

namespace App\Ai\Prompts;

use App\Ai\Schemas\CompanySeoSchema;

/**
 * Prompt construction for the per-locale SEO metadata pass (meta title and
 * description, focus keyword, keyword candidates). Pure string builders.
 */
class CompanySeoPrompt
{
    public static function system(string $locale): string
    {
        return "You are an SEO specialist optimising B2B company profiles for\n"
            ."international buyers using search engines.\n"
            ."\n"
            ."OUTPUT CONTRACT — follow exactly:\n"
            ."- Return ONLY a single JSON object. No prose, no markdown, no\n"
            ."  code fences, no commentary before or after it.\n"
            ."- The JSON object must match this spec, field for field. Do not\n"
            ."  add fields, rename fields, or change nesting:\n"
            ."\n"
            .CompanySeoSchema::promptSpec()."\n"
            ."\n"
            ."LANGUAGE:\n"
            .'- Write every value in '
            .CompanyContentPrompt::languageName($locale).", naturally,\n"
            ."  as a native speaker would.\n"
            ."\n"
            ."FIELD GUIDANCE:\n"
            ."- focus_keyword: ONE term a real buyer in this industry would\n"
            ."  actually type into a search engine.\n"
            ."- keyword_candidates: 3-5 alternative keywords, in DESCENDING\n"
            ."  order of preference — the application picks the first one not\n"
            ."  already reserved by another company. They must be genuinely\n"
            ."  useful search terms, not filler variations of the same string.\n"
            ."- meta_title and meta_description: compelling, factual, aligned\n"
            ."  with the focus keyword.\n"
            ."- meta_keywords: 3-5 supporting terms.\n"
            ."\n"
            ."NO FABRICATION:\n"
            ."- Base everything on the company input provided. Never invent\n"
            ."  certifications, awards, client names, capacities, dates, or\n"
            ."  figures that are not present in the input.\n"
            ."\n"
            ."FORMATTING:\n"
            ."- Never include HTML, markdown, emoji, or contact details in any\n"
            ."  field.\n";
    }

    /**
     * @param  array<string, mixed>  $input  the CompanyInputCollector payload
     */
    public static function user(array $input, ?string $siteText): string
    {
        return CompanyContentPrompt::user($input, $siteText);
    }
}
