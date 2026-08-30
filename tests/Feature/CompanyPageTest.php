<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyPublication;
use App\Services\CompanyPublicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_the_published_snapshot(): void
    {
        $publication = CompanyPublication::factory()->create([
            'name' => ['en' => 'Acme Trading Co', 'fa' => 'شرکت آکمی'],
            'website' => 'https://acme.test',
        ]);

        $response = $this->get(route('companies.show', ['slug' => $publication->slug]));

        $response->assertOk();
        $response->assertSee('Acme Trading Co');
        $response->assertSee('https://acme.test');
    }

    public function test_a_company_without_a_publication_is_not_publicly_visible(): void
    {
        $company = Company::factory()->create();

        $this->get(route('companies.show', ['slug' => $company->slug]))->assertNotFound();
    }

    public function test_a_scheduled_publication_is_not_visible_yet(): void
    {
        $publication = CompanyPublication::factory()->create([
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('companies.show', ['slug' => $publication->slug]))->assertNotFound();
    }

    public function test_it_records_a_page_view_against_the_publication(): void
    {
        $publication = CompanyPublication::factory()->create();

        $this->get(route('companies.show', ['slug' => $publication->slug]));

        $this->assertDatabaseHas('views', [
            'viewable_type' => CompanyPublication::class,
            'viewable_id' => $publication->id,
        ]);
    }

    public function test_the_public_page_keeps_serving_the_snapshot_after_owner_edits(): void
    {
        $company = Company::factory()->create(['name' => ['en' => 'Approved Name']]);

        $publication = app(CompanyPublicationService::class)->publish($company);

        $company->update(['name' => ['en' => 'Edited Draft Name']]);

        $response = $this->get(route('companies.show', ['slug' => $publication->slug]));

        $response->assertOk();
        $response->assertSee('Approved Name');
        $response->assertDontSee('Edited Draft Name');
    }

    public function test_the_public_page_survives_deleting_the_company(): void
    {
        $company = Company::factory()->create(['name' => ['en' => 'Everlasting Co']]);

        $publication = app(CompanyPublicationService::class)->publish($company);

        $company->forceDelete();

        $response = $this->get(route('companies.show', ['slug' => $publication->slug]));

        $response->assertOk();
        $response->assertSee('Everlasting Co');
    }

    /**
     * A minimal AI-content payload for the English locale.
     *
     * @param  array<string, mixed>  $overrides  merged over the 'en' payload
     * @return array<string, mixed>
     */
    private function contentPayload(array $overrides = []): array
    {
        return [
            'en' => array_merge([
                'hero' => [
                    'headline' => 'Industrial Insulation Panels Supplier',
                    'subheadline' => 'Reliable export quality for global buyers.',
                ],
                'about' => [
                    'heading' => 'About This Industrial Company',
                    'body' => "First paragraph of the about body.\n\nSecond paragraph of the about body.",
                ],
                'offerings' => [
                    ['title' => 'Insulation Panels', 'body' => 'Export grade panels for pipelines.'],
                ],
                'strengths' => [
                    ['title' => 'Export Experience', 'body' => 'Decades of export operations.'],
                ],
                'markets' => [
                    'heading' => 'Export Markets',
                    'body' => 'Active buyers across several regions.',
                    'countries' => ['DE'],
                ],
                'specs' => [
                    ['label' => 'Thickness', 'value' => '50 mm'],
                ],
                'faq' => [
                    ['q' => 'What is the minimum order?', 'a' => 'One full container per order.'],
                    ['q' => 'Do you ship worldwide?', 'a' => 'We ship to most major ports.'],
                ],
                'cta' => [
                    'heading' => 'Request a Quote Today',
                    'body' => 'Contact our export desk.',
                ],
            ], $overrides),
        ];
    }

    public function test_a_publication_with_content_renders_the_generated_sections(): void
    {
        $publication = CompanyPublication::factory()->create([
            'content' => $this->contentPayload(),
        ]);

        $response = $this->get(route('companies.show', ['slug' => $publication->slug]));

        $response->assertOk();
        $response->assertSee('Industrial Insulation Panels Supplier');
        $response->assertSee('First paragraph of the about body.');
        $response->assertSee('Insulation Panels');
        $response->assertSee('What is the minimum order?');
        $response->assertSee('50 mm');
        // The old description-based about card is gone; the AI one replaces it.
        $response->assertSee('About This Industrial Company');
    }

    public function test_the_company_page_renders_exactly_one_h1(): void
    {
        $publication = CompanyPublication::factory()->create([
            'name' => ['en' => 'Acme Trading Co', 'fa' => 'شرکت آکمی'],
        ]);

        $response = $this->get(route('companies.show', ['slug' => $publication->slug]));

        $response->assertOk();
        // The toolbar heading is demoted to a span on this page, so only the
        // header card's h1 remains.
        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
    }

    public function test_a_publication_without_content_renders_without_errors_or_an_about_card(): void
    {
        $publication = CompanyPublication::factory()->create();

        $response = $this->get(route('companies.show', ['slug' => $publication->slug]));

        $response->assertOk();
        $response->assertDontSee('About the company');
        $response->assertDontSee('Insulation Panels');
    }

    public function test_generated_text_with_html_looking_characters_is_escaped(): void
    {
        $publication = CompanyPublication::factory()->create([
            'content' => $this->contentPayload([
                'about' => [
                    'heading' => 'About <b>Acme</b>',
                    'body' => 'We are safe. <script>alert(1)</script>',
                ],
            ]),
        ]);

        $response = $this->get(route('companies.show', ['slug' => $publication->slug]));

        $response->assertOk();
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        // The injected payload is never rendered as live markup (the page
        // legitimately contains its own <script> tags, so match the payload).
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;b&gt;Acme&lt;/b&gt;', false);
    }

    // Separate test methods on purpose: SEOTools is a per-process singleton,
    // so the FAQPage block added by one request would leak into a second
    // in-process request of the same test.
    public function test_the_faqpage_json_ld_is_present_when_faq_has_entries(): void
    {
        $publication = CompanyPublication::factory()->create(['content' => $this->contentPayload()]);

        $this->get(route('companies.show', ['slug' => $publication->slug]))
            ->assertOk()
            ->assertSee('FAQPage', false);
    }

    public function test_the_faqpage_json_ld_is_absent_without_faq_entries(): void
    {
        $withoutFaq = CompanyPublication::factory()->create([
            'content' => $this->contentPayload(['faq' => []]),
        ]);

        $this->get(route('companies.show', ['slug' => $withoutFaq->slug]))
            ->assertOk()
            ->assertDontSee('FAQPage', false);
    }

    public function test_the_faqpage_json_ld_is_absent_without_generated_content(): void
    {
        $publication = CompanyPublication::factory()->create();

        $this->get(route('companies.show', ['slug' => $publication->slug]))
            ->assertOk()
            ->assertDontSee('FAQPage', false);
    }
}
