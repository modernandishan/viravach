<?php

namespace App\Ai\Prompts;

use App\Ai\Schemas\WordPressPostSchema;
use App\Enums\ContentGenerationMode;

/**
 * Prompt construction for a single WordPress article. Pure string builders,
 * matching CompanyContentPrompt: the field contract lives only in
 * WordPressPostSchema::promptSpec() and is embedded verbatim.
 *
 * Unlike the company-profile prompts there is no source locale — the owner
 * picks the language, and the article is written in it directly.
 */
class WordPressPostPrompt
{
    public static function system(string $locale): string
    {
        return "You are a senior B2B content writer producing a blog article for\n"
            ."a company's own website. The article must be genuinely useful to\n"
            ."the company's buyers, not an advertisement.\n"
            ."\n"
            ."OUTPUT CONTRACT — follow exactly:\n"
            ."- Return ONLY a single JSON object. No prose, no markdown, no\n"
            ."  code fences, no commentary before or after it.\n"
            ."- The JSON object must match this spec, field for field. Do not\n"
            ."  add fields, rename fields, or change nesting:\n"
            ."\n"
            .WordPressPostSchema::promptSpec()."\n"
            ."\n"
            ."LANGUAGE:\n"
            .'- Write every value in '.self::languageName($locale).". Write\n"
            ."  naturally, as a native speaker would — not as a word-for-word\n"
            ."  conversion from another language.\n"
            ."\n"
            ."SEO RULES:\n"
            ."- Choose ONE focus keyword a real buyer would type into a search\n"
            ."  engine, and use it in the title, in the first paragraph, and in\n"
            ."  one <h2>. Everywhere else use synonyms and natural phrasing.\n"
            ."- Keyword density must stay under 2%.\n"
            ."- image_alt must contain the focus keyword and describe a real\n"
            ."  scene, because it is published as the featured image's alt text.\n"
            ."\n"
            ."BODY RULES:\n"
            ."- Open with the answer, not with a preamble about the topic's\n"
            ."  importance.\n"
            ."- Use <h2> sections with <h3> subsections where it helps, short\n"
            ."  paragraphs, and at least one <ul> list.\n"
            ."- Do not include a title heading in the body; the title field is\n"
            ."  rendered separately by WordPress.\n"
            ."\n"
            ."NO FABRICATION:\n"
            ."- Never invent certifications, awards, client names, capacities,\n"
            ."  prices, dates or figures that are not in the input. Write around\n"
            ."  missing information rather than fabricating it.\n"
            ."- Do not claim the company sells something the input does not say\n"
            .'  it sells.';
    }

    /**
     * @param  array<string, mixed>  $input  CompanyInputCollector output.
     * @param  list<string>  $trendCandidates  Trend mode only: a handful of
     *                                         currently-trending queries in the target market, title only. The
     *                                         feed behind these is country-wide and has no industry filter, so
     *                                         the model — not the trend picker — decides which one (if any)
     *                                         has a genuine connection to this company.
     */
    public static function user(
        array $input,
        string $topic,
        ContentGenerationMode $mode,
        array $trendCandidates = [],
    ): string {
        $company = json_encode($input, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $brief = match ($mode) {
            // Specialized: the company's own field is the subject.
            ContentGenerationMode::Industry => "MODE: specialised to this company's field.\n"
                ."Write about \"{$topic}\", staying inside the company's own\n"
                ."industry and product range as described below. The reader is a\n"
                .'buyer evaluating suppliers in this field.',

            // Trend: a shortlist of currently-rising queries is offered, not
            // a single pre-chosen one — the feed behind it has no industry
            // filter, so relevance has to be judged here, by the model that
            // can actually read the company's field and the topics together.
            ContentGenerationMode::Trending => $trendCandidates !== []
                ? self::trendingBrief($topic, $trendCandidates)
                : "MODE: trending topic.\n"
                    ."\"{$topic}\" is currently rising in search. Write an article\n"
                    ."that genuinely serves someone searching for it, while\n"
                    ."connecting it to this company's field of business below. Do\n"
                    ."not force the connection: if the link is thin, keep the\n"
                    ."article about the topic and mention the company's field only\n"
                    .'where it is actually relevant.',
        };

        return $brief."\n\nCOMPANY:\n".$company;
    }

    /**
     * @param  list<string>  $trendCandidates
     */
    private static function trendingBrief(string $fallbackTopic, array $trendCandidates): string
    {
        $list = implode("\n", array_map(fn (string $candidate): string => '- "'.$candidate.'"', $trendCandidates));

        return "MODE: trending topic.\n"
            ."These queries are currently rising in search, in this company's\n"
            ."market:\n\n{$list}\n\n"
            ."Pick the ONE query above with the most plausible, genuine\n"
            ."connection to this company's field of business (described\n"
            ."below). Write the article bridging that trending query to the\n"
            ."company's business — do not force a weak or superficial link.\n"
            ."\n"
            ."If truly NONE of the queries above has any reasonable connection\n"
            ."to this company's field, ignore all of them and instead write\n"
            ."as if in specialised mode: choose your own topic from strictly\n"
            ."within the company's own field, the same way you would for\n"
            .'"'.$fallbackTopic.'".';
    }

    private static function languageName(string $locale): string
    {
        return match ($locale) {
            'fa' => 'Persian (فارسی)',
            'ar' => 'Arabic (العربية)',
            'ru' => 'Russian (Русский)',
            'tr' => 'Turkish (Türkçe)',
            default => 'English',
        };
    }
}
