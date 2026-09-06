<?php

use App\Ai\Prompts\WordPressPostPrompt;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * The WordPress article pipeline gets its own settings, so its kill
     * switch can never silently disable (or be disabled by) the
     * company-profile pipeline's `content.enabled`.
     *
     * enabled=false: the pipeline runs unattended from the nightly
     * scheduler, so it must never fire implicitly — an admin reviews the
     * seeded prompt defaults first, then switches it on.
     *
     * The prompt fields are seeded from WordPressPostPrompt's current
     * hardcoded strings (all five locales start with the same English
     * master text, exactly what the hardcoded prompt used), so prompt
     * behaviour is unchanged on deploy until an admin actually edits one.
     * Trend mode's seeded brief asks for a STANDALONE article about the
     * trending topic — the old bridging instruction is gone, not migrated.
     */
    public function up(): void
    {
        $this->migrator->add('wordpress_content.enabled', false);

        // One array row per field ({locale} => text) — spatie settings keys
        // are strictly group.name, so the per-locale map is the value.
        $this->migrator->add('wordpress_content.article_system_prompt', $this->perLocale(
            WordPressPostPrompt::DEFAULT_SYSTEM_GUIDANCE,
        ));
        $this->migrator->add('wordpress_content.mode_brief_industry', $this->perLocale(
            WordPressPostPrompt::DEFAULT_INDUSTRY_BRIEF,
        ));
        $this->migrator->add('wordpress_content.mode_brief_trending', $this->perLocale(
            WordPressPostPrompt::DEFAULT_TRENDING_BRIEF,
        ));
    }

    /**
     * @return array<string, string>
     */
    private function perLocale(string $text): array
    {
        return array_fill_keys(
            array_keys((array) config('laravellocalization.supportedLocales')),
            $text,
        );
    }
};
