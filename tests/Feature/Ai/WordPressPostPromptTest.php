<?php

namespace Tests\Feature\Ai;

use App\Ai\Prompts\WordPressPostPrompt;
use App\Ai\Schemas\WordPressPostSchema;
use App\Enums\ContentGenerationMode;
use App\Settings\WordPressContentSettings;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pins the split between admin-editable prompt TEXT (per-locale fields on
 * WordPressContentSettings) and the code-owned prompt logic (JSON output
 * contract, schema spec, language rule, candidate list, mode branching).
 * Also pins the standalone-trend-article rule: a trend prompt carries no
 * company context and never asks the model to bridge the topic to the
 * company's industry.
 */
class WordPressPostPromptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /** @param array<string, string> $overrides */
    protected function setPrompts(array $overrides = []): void
    {
        app(WordPressContentSettings::class)->fill($overrides + [
            'article_system_prompt' => [],
            'mode_brief_industry' => [],
            'mode_brief_trending' => [],
        ])->save();
    }

    public function test_the_system_prompt_always_carries_the_schema_contract(): void
    {
        // Even with the admin guidance field blanked, the pipeline-critical
        // parts are rebuilt in code and must be present.
        $this->setPrompts(['article_system_prompt' => ['en' => '']]);

        $system = WordPressPostPrompt::system('en');

        $this->assertStringContainsString(WordPressPostSchema::promptSpec(), $system);
        $this->assertStringContainsString('OUTPUT CONTRACT', $system);
        $this->assertStringContainsString('English', $system);
        $this->assertStringContainsString(WordPressPostPrompt::DEFAULT_SYSTEM_GUIDANCE, $system);
    }

    public function test_admin_edited_guidance_replaces_the_default_body(): void
    {
        $this->setPrompts(['article_system_prompt' => ['en' => 'Write like a friendly tech columnist.']]);

        $system = WordPressPostPrompt::system('en');

        $this->assertStringContainsString('Write like a friendly tech columnist.', $system);
        $this->assertStringNotContainsString(WordPressPostPrompt::DEFAULT_SYSTEM_GUIDANCE, $system);
        // The contract survives the edit regardless.
        $this->assertStringContainsString(WordPressPostSchema::promptSpec(), $system);
    }

    public function test_the_industry_brief_keeps_company_context_and_topic(): void
    {
        $this->setPrompts();

        $user = WordPressPostPrompt::user(
            ['name' => 'Acme Machinery'],
            'How to choose an industrial pump',
            ContentGenerationMode::Industry,
            'en',
        );

        $this->assertStringContainsString('How to choose an industrial pump', $user);
        $this->assertStringContainsString('COMPANY:', $user);
        $this->assertStringContainsString('Acme Machinery', $user);
    }

    public function test_the_trending_brief_is_standalone_and_carries_no_company_context(): void
    {
        $this->setPrompts();

        $user = WordPressPostPrompt::user(
            ['name' => 'Acme Machinery', 'category' => 'Machinery and Equipment'],
            'Poco X8 Power phone',
            ContentGenerationMode::Trending,
            'en',
            ['Poco X8 Power phone', 'New metro line opens'],
        );

        // The Poco-phone bug: the old prompt asked the model to bridge the
        // trending topic to the company's industry, and handed it the
        // company payload to do it with. Neither may happen any more.
        $this->assertStringNotContainsString('Acme Machinery', $user);
        $this->assertStringNotContainsString('COMPANY:', $user);
        $this->assertStringNotContainsString("company's field", $user);
        $this->assertStringNotContainsString('specialised mode', $user);
        $this->assertStringNotContainsString('connection', $user);

        // The topic and the candidate shortlist are still offered — the
        // model picks purely on article merit.
        $this->assertStringContainsString('Poco X8 Power phone', $user);
        $this->assertStringContainsString('- "New metro line opens"', $user);
        $this->assertStringContainsString('standalone', $user);
    }

    public function test_the_trending_brief_omits_the_candidates_block_when_the_feed_gave_none(): void
    {
        $this->setPrompts();

        $user = WordPressPostPrompt::user(
            [],
            'Poco X8 Power phone',
            ContentGenerationMode::Trending,
            'en',
            [],
        );

        $this->assertStringContainsString('Poco X8 Power phone', $user);
        $this->assertStringNotContainsString('Pick the ONE query', $user);
        $this->assertStringNotContainsString('{candidates}', $user);
    }

    public function test_the_per_locale_trending_template_is_used_for_its_locale(): void
    {
        $this->setPrompts([
            'mode_brief_trending' => ['fa' => 'مقاله‌ای مستقل درباره «{topic}» بنویس.'],
        ]);

        $user = WordPressPostPrompt::user([], 'گوشی پوکو', ContentGenerationMode::Trending, 'fa', []);

        $this->assertStringContainsString('مقاله‌ای مستقل درباره «گوشی پوکو» بنویس.', $user);
    }

    public function test_placeholders_removed_by_an_admin_edit_are_still_substituted(): void
    {
        // An admin deleted every placeholder: the code must still inject the
        // topic somewhere the model can use, rather than shipping a literal
        // "{topic}" or an empty brief.
        $this->setPrompts(['mode_brief_trending' => ['en' => 'Write a standalone article.']]);

        $user = WordPressPostPrompt::user([], 'Poco X8 Power phone', ContentGenerationMode::Trending, 'en', []);

        $this->assertStringContainsString('Poco X8 Power phone', $user);
        $this->assertStringNotContainsString('{topic}', $user);
        $this->assertStringNotContainsString('{candidates}', $user);
    }
}
