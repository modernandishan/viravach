<?php

namespace Tests\Feature\Ai;

use App\Enums\CompanyContentStatus;
use App\Enums\CompanyReviewStatus;
use App\Jobs\Ai\FinalizeContent;
use App\Jobs\Ai\GenerateSeoBlock;
use App\Jobs\Ai\GenerateSourceContent;
use App\Jobs\Ai\LocalizeContent;
use App\Jobs\Ai\ReserveKeyword;
use App\Models\Company;
use App\Models\CompanyCategory;
use App\Models\CompanyContent;
use App\Models\Country;
use App\Models\SeoKeywordReservation;
use App\Settings\ContentSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiContentPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(ContentSettings::class)->fill([
            'enabled' => true,
            'base_url' => 'https://ai.example/v1',
            'api_key' => 'secret-key',
            'model' => 'content-model',
            'translation_model' => 'translate-model',
        ])->save();

        // Site text is deliberately absent: with no website, the extractor
        // must return null and the chain must proceed without it.
    }

    /**
     * A payload that satisfies CompanyContentSchema::validate().
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function contentPayload(array $overrides = []): array
    {
        $body = str_repeat('We manufacture industrial insulation panels for export markets. ', 14);

        return array_merge([
            'v' => 1,
            'hero' => [
                'headline' => 'Industrial Insulation Panels Supplier',
                'subheadline' => str_repeat('Reliable export quality for global buyers. ', 3),
                'image_alt' => str_repeat('Factory production line view ', 3),
            ],
            'about' => [
                'heading' => 'About This Industrial Company',
                'body' => $body,
            ],
            'offerings' => [
                ['title' => 'Insulation Panels', 'body' => str_repeat('Export grade panels for pipelines. ', 8)],
                ['title' => 'Thermal Boards', 'body' => str_repeat('Thermal boards in many thicknesses. ', 8)],
                ['title' => 'Custom Fabrication', 'body' => str_repeat('Custom orders shipped worldwide. ', 8)],
            ],
            'strengths' => [
                ['title' => 'Export Experience', 'body' => str_repeat('Decades of export operations. ', 6)],
                ['title' => 'Quality Control', 'body' => str_repeat('Every batch is pressure tested. ', 6)],
                ['title' => 'Fast Logistics', 'body' => str_repeat('Containers leave the port weekly. ', 6)],
            ],
            'markets' => [
                'heading' => 'Export Markets',
                'body' => str_repeat('Active buyers across several regions today. ', 6),
                'countries' => ['ZZ'],
            ],
            'specs' => [
                ['label' => 'Thickness', 'value' => '50 mm'],
            ],
            'faq' => [
                ['q' => 'What is the minimum order?', 'a' => str_repeat('One full container per order. ', 5)],
                ['q' => 'Do you ship worldwide?', 'a' => str_repeat('We ship to most major ports. ', 5)],
                ['q' => 'What is the lead time?', 'a' => str_repeat('Usually four to six weeks. ', 5)],
                ['q' => 'Are samples available?', 'a' => str_repeat('Samples ship within one week. ', 5)],
            ],
            'cta' => [
                'heading' => 'Request a Quote Today',
                'body' => str_repeat('Contact our export desk. ', 4),
            ],
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function seoPayload(): array
    {
        return [
            'meta_title' => str_repeat('t', 35),
            'meta_description' => str_repeat('d', 130),
            'focus_keyword' => 'industrial insulation',
            'keyword_candidates' => ['industrial insulation', 'insulation panels', 'thermal insulation'],
            'meta_keywords' => ['insulation supplier', 'export panels', 'thermal boards'],
        ];
    }

    /**
     * One chat-completion response per gateway call, in the exact order the
     * chain consumes them: source content, source SEO, then per-locale
     * content+SEO following the config's locale order. $failingLocale gets
     * two invalid responses (initial + correction retry).
     *
     * @return list<array<string, mixed>>
     */
    private function chainSequence(?string $failingLocale = null): array
    {
        $sequence = [
            $this->chatResponse($this->contentPayload()),
            $this->chatResponse($this->seoPayload()),
        ];

        $targets = array_values(array_filter(
            array_keys((array) config('laravellocalization.supportedLocales')),
            fn (string $locale): bool => $locale !== 'en',
        ));

        foreach ($targets as $locale) {
            if ($locale === $failingLocale) {
                $sequence[] = $this->chatResponse(['v' => 1]);
                $sequence[] = $this->chatResponse(['v' => 1]);
            } else {
                $sequence[] = $this->chatResponse($this->contentPayload());
                $sequence[] = $this->chatResponse($this->seoPayload());
            }
        }

        return $sequence;
    }

    private function makeCompany(): Company
    {
        $company = Company::factory()->create([
            'name' => ['en' => 'Acme Industrial Co', 'fa' => 'شرکت آکمی'],
            'brief' => 'We manufacture industrial insulation panels.',
            'brief_locale' => 'en',
        ]);

        $category = CompanyCategory::create([
            'title' => ['en' => 'Industrial Equipment'],
            'is_active' => true,
        ]);
        $company->categories()->attach($category);

        // The pipeline state row: request() normally creates it, but the
        // tests drive the jobs directly.
        CompanyContent::firstOrCreate(['company_id' => $company->id]);

        return $company;
    }

    /**
     * Queue a chat completion returning the given array as assistant content.
     */
    private function chatResponse(array $payload): array
    {
        return [
            'choices' => [
                ['message' => ['content' => json_encode($payload, JSON_UNESCAPED_UNICODE)]],
            ],
            'usage' => ['total_tokens' => 10],
        ];
    }

    private function runChain(Company $company): void
    {
        (new GenerateSourceContent($company->id))->handle();
        (new GenerateSeoBlock($company->id))->handle();
        (new ReserveKeyword($company->id))->handle();
        (new LocalizeContent($company->id))->handle();
        (new FinalizeContent($company->id))->handle();
    }

    public function test_a_full_chain_run_produces_content_for_all_five_locales_and_a_ready_row(): void
    {
        Http::fake([
            'https://ai.example/v1/chat/completions*' => Http::sequence($this->chainSequence()),
        ]);

        $company = $this->makeCompany();
        $this->runChain($company);

        $content = CompanyContent::query()->where('company_id', $company->id)->firstOrFail();

        $this->assertSame(CompanyContentStatus::Ready, $content->status);
        $this->assertNull($content->locked_at);
        $this->assertSame(1, $content->generations_count);

        foreach (['en', 'fa', 'ar', 'ru', 'tr'] as $locale) {
            $this->assertArrayHasKey($locale, $content->ai_payload);
            $this->assertArrayHasKey($locale, $content->ai_payload['seo']);
        }

        // Company content carries the locale payloads, not the seo sub-array.
        $this->assertSame('Industrial Insulation Panels Supplier', $company->fresh()->content['en']['hero']['headline']);
        $this->assertArrayNotHasKey('seo', $company->fresh()->content);
    }

    public function test_an_invalid_first_response_triggers_exactly_one_retry(): void
    {
        Http::fake([
            'https://ai.example/v1/chat/completions*' => Http::sequence([
                // First response: missing required fields entirely.
                $this->chatResponse(['v' => 1]),
                // Correction retry: valid.
                $this->chatResponse($this->contentPayload()),
                $this->chatResponse($this->seoPayload()),
            ]),
        ]);

        $company = $this->makeCompany();

        (new GenerateSourceContent($company->id))->handle();
        (new GenerateSeoBlock($company->id))->handle();

        Http::assertSentCount(3); // 1 content + 1 retry + 1 seo
        $this->assertArrayHasKey('en', CompanyContent::firstOrFail()->ai_payload);
    }

    public function test_a_taken_keyword_falls_through_to_the_next_candidate(): void
    {
        Http::fake([
            'https://ai.example/v1/chat/completions*' => Http::sequence($this->chainSequence()),
        ]);

        $company = $this->makeCompany();

        $other = Company::factory()->create();
        SeoKeywordReservation::create([
            'locale' => 'en',
            'keyword' => 'industrial insulation',
            'seoable_type' => $other->getMorphClass(),
            'seoable_id' => $other->id,
        ]);

        $this->runChain($company);

        $enSeo = CompanyContent::firstOrFail()->ai_payload['seo']['en'];

        $this->assertSame('insulation panels', $enSeo['focus_keyword']);
        $this->assertDatabaseHas('seo_keyword_reservations', [
            'locale' => 'en',
            'keyword' => 'insulation panels',
            'seoable_id' => $company->id,
        ]);
    }

    public function test_one_locale_failing_still_finishes_the_others(): void
    {
        // The Arabic localization fails both attempts; the chain must
        // still finish every other locale. Locale order follows the config.
        Http::fake([
            'https://ai.example/v1/chat/completions*' => Http::sequence($this->chainSequence('ar')),
        ]);

        $company = $this->makeCompany();
        $this->runChain($company);

        $content = CompanyContent::firstOrFail();

        $this->assertSame(CompanyContentStatus::Ready, $content->status);
        $this->assertArrayHasKey('fa', $content->ai_payload);
        $this->assertArrayHasKey('ru', $content->ai_payload);
        $this->assertArrayHasKey('tr', $content->ai_payload);
        $this->assertArrayNotHasKey('ar', $content->ai_payload);
    }

    public function test_markets_countries_come_from_export_countries_not_from_the_model(): void
    {
        $germany = Country::create([
            'name' => ['en' => 'Germany', 'fa' => 'آلمان'],
            'official_name' => ['en' => 'Federal Republic of Germany'],
            'capital' => ['en' => 'Berlin'],
            'currency_name' => ['en' => 'Euro'],
            'slug' => 'germany-'.uniqid(),
            'iso2' => 'DE',
            'phone_code' => '+49',
            'currency' => 'EUR',
            'currency_symbol' => '€',
        ]);

        // The model returns the fake code 'ZZ' (baked into contentPayload());
        // the pipeline must replace it with the verified ISO code.

        Http::fake([
            'https://ai.example/v1/chat/completions*' => Http::sequence([
                $this->chatResponse($this->contentPayload()),
                $this->chatResponse($this->seoPayload()),
            ]),
        ]);

        $company = $this->makeCompany();
        $company->exportCountries()->attach($germany->id);

        (new GenerateSourceContent($company->id))->handle();

        $payload = CompanyContent::firstOrFail()->ai_payload['en'];

        $this->assertSame(['DE'], $payload['markets']['countries']);
    }

    public function test_finalize_sets_review_status_to_pending_and_does_not_publish(): void
    {
        Http::fake([
            'https://ai.example/v1/chat/completions*' => Http::sequence($this->chainSequence()),
        ]);

        $company = $this->makeCompany();
        $company->seo()->firstOrCreate([
            'meta_title' => ['en' => 'Old title'],
        ]);
        $this->runChain($company);

        $company->refresh();

        $this->assertSame(CompanyReviewStatus::PendingReview, $company->review_status);
        $this->assertNull($company->publication);
        // The generated blocks overwrite the pre-existing SEO row per locale.
        $this->assertSame(str_repeat('t', 35), $company->seo->getTranslation('meta_title', 'en', false));
        $this->assertSame(str_repeat('t', 35), $company->seo->getTranslation('meta_title', 'fa', false));
        $this->assertSame('insulation supplier,export panels,thermal boards', $company->seo->getTranslation('meta_keywords', 'en', false));
    }
}
