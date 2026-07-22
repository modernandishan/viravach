<?php

namespace Tests\Feature;

use App\Models\CompanyPublication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyPageSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_full_seo_output_from_the_seo_meta_row(): void
    {
        $publication = CompanyPublication::factory()->create([
            'name' => ['en' => 'Acme Trading Co', 'fa' => 'شرکت آکمی'],
        ]);

        $publication->seo()->create([
            'meta_title' => ['en' => 'Acme SEO Title'],
            'meta_description' => ['en' => 'Acme SEO description'],
            'meta_keywords' => ['en' => 'export, trade , iran'],
            'robots_index' => false,
            'robots_follow' => true,
            'og_type' => 'article',
            'og_title' => ['en' => 'Acme OG Title'],
            'og_description' => ['en' => 'Acme OG description'],
            'twitter_card_type' => 'summary_large_image',
            'twitter_title' => ['en' => 'Acme Twitter Title'],
            'twitter_description' => ['en' => 'Acme Twitter description'],
            'schema_type' => 'Organization',
            'schema_extra' => ['foundingDate' => '2010'],
        ]);

        $response = $this->get(route('companies.show', ['slug' => $publication->slug]));

        $response->assertOk();

        $response->assertSee('property="og:title"', false);
        $response->assertSee('Acme OG Title', false);
        $response->assertSee('property="og:description"', false);
        $response->assertSee('Acme OG description', false);
        $response->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
        $response->assertSee('<meta name="robots" content="noindex, follow">', false);
        $response->assertSee('<meta name="keywords" content="export, trade, iran">', false);
        $response->assertSee('<script type="application/ld+json">', false);
        $response->assertSee('"@type":"Organization"', false);
        $response->assertSee('"foundingDate":"2010"', false);
    }

    public function test_it_renders_no_optional_seo_output_without_a_seo_meta_row(): void
    {
        $publication = CompanyPublication::factory()->create([
            'name' => ['en' => 'Fallback Title Co', 'fa' => 'شرکت بدون سئو'],
        ]);

        $response = $this->get(route('companies.show', ['slug' => $publication->slug]));

        $response->assertOk();

        $response->assertDontSee('property="og:title"', false);
        $response->assertDontSee('property="og:description"', false);
        $response->assertDontSee('name="twitter:card"', false);
        $response->assertDontSee('name="robots"', false);
        $response->assertDontSee('name="keywords"', false);
        $response->assertDontSee('application/ld+json', false);

        // The plain <title> still renders, falling back to the company name.
        $response->assertSee('<title>Fallback Title Co</title>', false);
    }

    public function test_cornerstone_overrides_a_manual_noindex_to_index_follow(): void
    {
        $publication = CompanyPublication::factory()->create();

        $publication->seo()->create([
            'robots_index' => false,
            'robots_follow' => true,
            'is_cornerstone' => true,
        ]);

        $response = $this->get(route('companies.show', ['slug' => $publication->slug]));

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="index, follow">', false);
        $response->assertDontSee('noindex', false);
    }
}
