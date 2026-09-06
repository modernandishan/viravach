<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Admin-editable configuration for the AUTOMATIC WordPress article
 * pipeline, deliberately separate from ContentSettings: the profile
 * pipeline and this one must never disable each other through one shared
 * generic toggle. Rows are seeded by the matching settings migration in
 * database/settings/ and edited on the ManageWordPressContentSettings page.
 *
 * `enabled` defaults to false — this pipeline fires unattended from the
 * nightly scheduler, so it must never start implicitly; an admin switches
 * it on deliberately after reviewing the seeded prompt defaults.
 *
 * The prompt fields are per-locale arrays ({locale} => text). The prompt
 * builder (App\Ai\Prompts\WordPressPostPrompt) treats them as editable
 * template TEXT only: the JSON output contract, the schema spec, the
 * language rule and the trend-candidate list are always (re)built in code,
 * so a garbled admin edit can change tone but never break the pipeline.
 * Placeholders: {topic} (and its alias {fallback_topic}) plus {candidates}
 * — the code substitutes every one of them even if an edit removes them.
 */
class WordPressContentSettings extends Settings
{
    public bool $enabled;

    /** @var array<string, string> */
    public array $article_system_prompt;

    /** @var array<string, string> */
    public array $mode_brief_industry;

    /** @var array<string, string> */
    public array $mode_brief_trending;

    public static function group(): string
    {
        return 'wordpress_content';
    }
}
