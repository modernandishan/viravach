<?php

namespace App\Ai\Prompts;

use App\Ai\Schemas\WordPressPostSchema;
use App\Enums\ContentGenerationMode;
use App\Settings\WordPressContentSettings;

/**
 * Prompt construction for a single WordPress article. The field contract
 * lives only in WordPressPostSchema::promptSpec() and is embedded verbatim.
 *
 * Split of responsibilities: everything an admin edit could garble into a
 * broken pipeline — the JSON output contract, the schema spec, the language
 * rule, the trend-candidate list rendering and the mode branching — is
 * built here in code. The admin-editable per-locale fields on
 * WordPressContentSettings (see the defaults below, which the settings
 * migration seeds) are guidance TEXT only, and every placeholder is
 * substituted by code even if an edit removes it. A blank or missing
 * locale entry falls back to the matching default constant.
 *
 * TREND MODE IS STANDALONE: the article is about the trending topic on its
 * own merits. The prompt carries no company context at all and never asks
 * the model to bridge the topic to the company's industry — an article
 * about a phone launch must not become "…and its impact on the machinery
 * industry". There is no fall back to specialised mode: the specialised
 * fallback exists only when NO usable topic exists at all (a dead trends
 * feed), which the generation service handles as its own failure reason.
 *
 * Unlike the company-profile prompts there is no source locale — the
 * article is written directly in the company's chosen content language.
 */
class WordPressPostPrompt
{
    /**
     * Seeded into WordPressContentSettings.article_system_prompt by the
     * settings migration; used at runtime when an admin blanks the field.
     */
    public const DEFAULT_SYSTEM_GUIDANCE = <<<'TXT'
        You are a senior content writer producing a blog article for a company's
        own website. The article must be genuinely useful to its readers, not an
        advertisement.

        SEO RULES:
        - Choose ONE focus keyword a real reader would type into a search
          engine, and use it in the title, in the first paragraph, and in one
          <h2>. Everywhere else use synonyms and natural phrasing.
        - Keyword density must stay under 2%.
        - image_alt must contain the focus keyword and describe a real scene,
          because it is published as the featured image's alt text.

        BODY RULES:
        - Open with the answer, not with a preamble about the topic's
          importance.
        - Use <h2> sections with <h3> subsections where it helps, short
          paragraphs, and at least one <ul> list.
        - Do not include a title heading in the body; the title field is
          rendered separately by WordPress.

        NO FABRICATION:
        - Never invent certifications, awards, client names, capacities,
          prices, dates or figures that are not in the input. Write around
          missing information rather than fabricating it.
        TXT;

    /**
     * Seeded into WordPressContentSettings.mode_brief_industry. Placeholders:
     * {topic} (alias {fallback_topic}).
     */
    public const DEFAULT_INDUSTRY_BRIEF = <<<'TXT'
        MODE: specialised to this company's field.
        Write about "{topic}", staying inside the company's own industry and
        product range as described below. The reader is a buyer evaluating
        suppliers in this field.
        TXT;

    /**
     * Seeded into WordPressContentSettings.mode_brief_trending. Placeholders:
     * {topic} (alias {fallback_topic}) and {candidates} — the code renders
     * the candidate shortlist there (or nothing when the feed gave none).
     * Standalone by design: no company, no industry, no bridging.
     */
    public const DEFAULT_TRENDING_BRIEF = <<<'TXT'
        MODE: trending topic.
        "{topic}" is currently rising in search. Write a genuinely useful,
        well-researched article about it on its own merits, for readers
        interested in the topic itself. Do NOT connect the article to any
        company, industry, product range or category, and do not mention any
        company's business: the article must stand entirely on its own.
        {candidates}
        TXT;

    public static function system(string $locale): string
    {
        $settings = app(WordPressContentSettings::class);

        $guidance = trim((string) ($settings->article_system_prompt[$locale] ?? ''));

        if ($guidance === '') {
            $guidance = self::DEFAULT_SYSTEM_GUIDANCE;
        }

        return "You are a content writer. The article must follow this\n"
            ."output contract.\n"
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
            .$guidance;
    }

    /**
     * @param  array<string, mixed>  $input  CompanyInputCollector output.
     *                                       Empty for trend mode: a standalone trending article carries no
     *                                       company context at all, so the model cannot bridge to it.
     * @param  list<string>  $trendCandidates  Trend mode only: currently-trending
     *                                         queries in the target market, title only. The model picks the one
     *                                         that makes the best standalone article — relevance to any company
     *                                         plays no part.
     */
    public static function user(
        array $input,
        string $topic,
        ContentGenerationMode $mode,
        string $locale,
        array $trendCandidates = [],
    ): string {
        $settings = app(WordPressContentSettings::class);

        $brief = match ($mode) {
            ContentGenerationMode::Industry => self::fill(
                (string) ($settings->mode_brief_industry[$locale] ?? self::DEFAULT_INDUSTRY_BRIEF),
                ['topic' => $topic],
            ),

            ContentGenerationMode::Trending => self::fill(
                (string) ($settings->mode_brief_trending[$locale] ?? self::DEFAULT_TRENDING_BRIEF),
                [
                    'topic' => $topic,
                    'candidates' => $trendCandidates === [] ? '' : self::candidatesBlock($trendCandidates),
                ],
            ),
        };

        // Company context goes out ONLY in specialised mode. A standalone
        // trending article carries none, even if a caller passed input —
        // the model cannot bridge to a business it never sees.
        if ($mode !== ContentGenerationMode::Industry || $input === []) {
            return $brief;
        }

        return $brief."\n\nCOMPANY:\n"
            .json_encode($input, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Code-owned rendering of the candidate shortlist: the model still picks
     * WHICH rising query to write about, but purely on article merit — the
     * old "most plausible connection to the company's field" instruction,
     * and the fall back to specialised mode, are gone for good.
     *
     * @param  list<string>  $trendCandidates
     */
    private static function candidatesBlock(array $trendCandidates): string
    {
        $list = implode("\n", array_map(fn (string $candidate): string => '- "'.$candidate.'"', $trendCandidates));

        return "These queries are also currently rising in search:\n"
            ."\n"
            .$list."\n"
            ."\n"
            .'Pick the ONE query above that would make the most engaging, '
            ."informative standalone article, and write about that one instead\n"
            .'of the fallback topic — still with no company or industry angle.';
    }

    /**
     * Substitutes every placeholder, tolerating edits that removed one:
     * {fallback_topic} is kept as an alias of {topic}, and a template that
     * lost its {topic} placeholder entirely still gets the topic appended —
     * the article must never be generated without its subject.
     *
     * @param  array<string, string>  $replacements
     */
    private static function fill(string $template, array $replacements): string
    {
        $topic = $replacements['topic'] ?? null;

        if ($topic !== null && ! isset($replacements['fallback_topic'])) {
            $replacements['fallback_topic'] = $topic;
        }

        $hasTopicPlaceholder = str_contains($template, '{topic}')
            || str_contains($template, '{fallback_topic}');

        $filled = str_replace(
            array_map(fn (string $key): string => '{'.$key.'}', array_keys($replacements)),
            array_values($replacements),
            $template,
        );

        if ($topic !== null && ! $hasTopicPlaceholder) {
            $filled .= "\n\nThe topic to write about: {$topic}";
        }

        return $filled;
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
