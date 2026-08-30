<?php

namespace Tests\Feature\Ai;

use App\Ai\Schemas\CompanyContentSchema;
use App\Ai\Schemas\CompanySeoSchema;
use Tests\TestCase;

class SchemaRepairTest extends TestCase
{
    public function test_overlong_strings_are_truncated_on_a_word_boundary_and_become_valid(): void
    {
        $payload = [
            'v' => 1,
            'hero' => [
                'headline' => 'Industrial Insulation Panels Supplier',
                'subheadline' => str_repeat('Reliable export quality. ', 8), // 200 > 160
                'image_alt' => str_repeat('Factory view ', 12),              // 156 ok
            ],
            'about' => [
                'heading' => 'About This Industrial Company',
                'body' => str_repeat('We manufacture industrial insulation panels for export markets. ', 30), // 1950 > 2500? no — 1950
            ],
            'offerings' => [
                ['title' => 'Panels', 'body' => str_repeat('Export grade panels for pipelines. ', 30)], // 1050 > 800
                ['title' => 'Boards', 'body' => str_repeat('Thermal boards in many thicknesses. ', 8)],
                ['title' => 'Fabrication', 'body' => str_repeat('Custom orders shipped worldwide. ', 20)],
            ],
            'strengths' => [
                ['title' => 'Experience', 'body' => str_repeat('Decades of export operations. ', 6)],
                ['title' => 'Quality', 'body' => str_repeat('Every batch is pressure tested. ', 6)],
                ['title' => 'Logistics', 'body' => str_repeat('Containers leave the port weekly. ', 6)],
            ],
            'markets' => [
                'heading' => 'Export Markets',
                'body' => str_repeat('Active buyers across several regions today. ', 6),
                'countries' => [],
            ],
            'specs' => [],
            'faq' => [
                ['q' => 'What is the minimum order?', 'a' => str_repeat('One full container per order. ', 40)], // 1200 > 600
                ['q' => 'Do you ship worldwide?', 'a' => str_repeat('We ship to most major ports. ', 5)],
                ['q' => 'What is the lead time?', 'a' => str_repeat('Usually four to six weeks. ', 5)],
                ['q' => 'Are samples available?', 'a' => str_repeat('Samples ship within one week. ', 5)],
            ],
            'cta' => [
                'heading' => 'Request a Quote Today',
                'body' => str_repeat('Contact our export desk. ', 20),
            ],
        ];

        $repaired = CompanyContentSchema::repair($payload);

        // Every overlong string now fits; every value stays plain text.
        $this->assertLessThanOrEqual(160, mb_strlen($repaired['hero']['subheadline']));
        $this->assertLessThanOrEqual(800, mb_strlen($repaired['offerings'][0]['body']));
        $this->assertLessThanOrEqual(600, mb_strlen($repaired['faq'][0]['a']));

        // Word boundary: no truncated fragment.
        $this->assertStringEndsNotWith('Reliable', $repaired['hero']['subheadline']);
        $this->assertStringEndsNotWith('export', $repaired['offerings'][0]['body']);

        // Sentence end kept whole where one existed in the final stretch.
        $this->assertMatchesRegularExpression('/[.!?]$/u', $repaired['hero']['subheadline']);

        // Under-length fields are untouched — they remain real failures.
        $this->assertSame('Panels', $repaired['offerings'][0]['title']);

        // And the repair makes the whole payload pass validation.
        $this->assertSame([], CompanyContentSchema::validate($repaired));
    }

    public function test_seo_overlong_strings_are_truncated_and_become_valid(): void
    {
        $payload = [
            'meta_title' => str_repeat('Industrial insulation supplier title ', 3), // 111 > 60
            'meta_description' => str_repeat('d', 200),                                  // > 160
            'focus_keyword' => 'industrial insulation',
            'keyword_candidates' => [str_repeat('candidate keyword ', 6), 'insulation panels', 'thermal boards'],
            'meta_keywords' => ['insulation supplier', 'export panels', 'thermal boards'],
        ];

        $repaired = CompanySeoSchema::repair($payload);

        $this->assertLessThanOrEqual(60, mb_strlen($repaired['meta_title']));
        $this->assertLessThanOrEqual(160, mb_strlen($repaired['meta_description']));
        $this->assertLessThanOrEqual(60, mb_strlen($repaired['keyword_candidates'][0]));

        $this->assertSame([], CompanySeoSchema::validate($repaired));
    }
}
