<?php

namespace Tests\Feature;

use App\Models\CompanyCategory;
use App\Models\CompanyPublication;
use App\Models\Country;
use App\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyCardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A minimal but genuinely decodable 1x1 PNG so MediaLibrary's image
     * handling has real bytes to work with.
     */
    private const VALID_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');
    }

    private function content(string $subheadline = 'Reliable export quality for global buyers.'): array
    {
        return [
            'en' => [
                'hero' => ['headline' => 'Hero', 'subheadline' => $subheadline, 'image_alt' => 'Alt'],
                'about' => ['heading' => 'About', 'body' => 'Body'],
            ],
        ];
    }

    private function publication(array $overrides = []): CompanyPublication
    {
        return CompanyPublication::factory()->create(array_merge([
            'name' => ['en' => 'Acme Industrial Co', 'fa' => 'شرکت آکمی'],
            'content' => $this->content(),
        ], $overrides));
    }

    private function render(CompanyPublication $publication): string
    {
        return Livewire::test('company-elements.company-card', ['company' => $publication])
            ->html();
    }

    public function test_a_publication_with_a_featured_image_renders_the_img_with_localized_alt(): void
    {
        $publication = $this->publication();
        $publication->addMediaFromBase64(self::VALID_PNG_BASE64)
            ->withCustomProperties(['alt' => ['en' => 'Factory floor', 'fa' => 'کارخانه']])
            ->toMediaCollection('featured_image', 's3');

        $html = $this->render($publication);

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('alt="Factory floor"', $html);
        // Substring-only, not the CSS selector: the stylesheet (now that it
        // actually renders — see cardStyles()) legitimately mentions the
        // class name in its own rules regardless of which markup path runs.
        $this->assertStringNotContainsString('class="vv-media vv-media-placeholder"', $html);
    }

    public function test_a_publication_without_a_featured_image_renders_the_initial_placeholder_without_a_broken_img(): void
    {
        $publication = $this->publication();

        $html = $this->render($publication);

        $this->assertStringContainsString('vv-media-placeholder', $html);
        $this->assertStringContainsString('A', $html); // initial of "Acme Industrial Co"
        $this->assertStringNotContainsString('<img', $html);
    }

    public function test_the_title_category_eyebrow_and_excerpt_appear(): void
    {
        $category = CompanyCategory::create(['title' => ['en' => 'Industrial Equipment'], 'is_active' => true]);
        $publication = $this->publication();
        $publication->categories()->attach($category);

        $html = $this->render($publication);

        $this->assertStringContainsString('Acme Industrial Co', $html);
        $this->assertStringContainsString('vv-eyebrow', $html);
        $this->assertStringContainsString('Industrial Equipment', $html);
        $this->assertStringContainsString('Reliable export quality for global buyers.', $html);
        $this->assertStringContainsString('vv-excerpt', $html);
    }

    public function test_the_verified_badge_appears_only_when_is_verified(): void
    {
        $verified = $this->publication(['is_verified' => true]);
        $plain = $this->publication(['is_verified' => false]);

        $this->assertStringContainsString(__('companies.verified'), $this->render($verified));
        $this->assertStringNotContainsString(__('companies.verified'), $this->render($plain));
    }

    public function test_the_card_links_to_the_company_page(): void
    {
        $publication = $this->publication();

        $html = $this->render($publication);
        $href = route('companies.show', ['slug' => $publication->slug]);

        $this->assertStringContainsString('href="'.$href.'"', $html);
    }

    public function test_the_category_and_state_pages_render_the_new_component_for_each_result(): void
    {
        $category = CompanyCategory::create(['title' => ['en' => 'Industrial Equipment'], 'is_active' => true]);
        $p1 = $this->publication(['slug' => 'first-co']);
        $p2 = $this->publication(['slug' => 'second-co']);
        $p1->categories()->attach($category);
        $p2->categories()->attach($category);

        $categoryResponse = $this->get(route('companies.category', ['slug' => $category->slug]));

        $categoryResponse->assertOk();
        $categoryResponse->assertSee('vv-card', false);
        $categoryResponse->assertSee('first-co', false);
        $categoryResponse->assertSee('second-co', false);

        $country = Country::create([
            'name' => ['en' => 'Iran'], 'official_name' => ['en' => 'Iran'], 'capital' => ['en' => 'Tehran'],
            'currency_name' => ['en' => 'Rial'], 'slug' => 'iran-'.uniqid(), 'phone_code' => '98',
            'currency' => 'IRR', 'currency_symbol' => 'IRR', 'is_active' => true,
        ]);
        $state = State::create([
            'country_id' => $country->id, 'name' => ['en' => 'Tehran'], 'type' => ['en' => 'Province'],
            'slug' => 'tehran-'.uniqid(), 'code' => 'THR', 'is_active' => true,
        ]);
        $p1->forceFill(['state_id' => $state->id])->save();
        $p2->forceFill(['state_id' => $state->id])->save();

        $stateResponse = $this->get(route('companies.state', ['slug' => $state->slug]));

        $stateResponse->assertOk();
        $stateResponse->assertSee('vv-card', false);
        $stateResponse->assertSee('first-co', false);
        $stateResponse->assertSee('second-co', false);
    }

    /**
     * Regression test for the reported bug: the card's <style> block, as a
     * literal tag in the .blade.php source, is silently never delivered —
     * Livewire's SFC compiler statically extracts it into a "styleModule"
     * asset this app registers no route to serve, so none of these rules
     * ever reached the page at all (confirmed by inspecting the raw
     * response: zero bytes of the stylesheet were present). cardStyles()
     * is assembled as a PHP string and raw-echoed instead, which is
     * invisible to that static scan.
     */
    public function test_the_card_has_a_visible_border_and_shadow_against_the_page(): void
    {
        $html = $this->render($this->publication());

        $this->assertStringContainsString('border: 1px solid var(--vv-ink-100)', $html);
        $this->assertStringContainsString('box-shadow: 0 1px 2px rgba(15, 23, 32, .06)', $html);
        $this->assertStringContainsString('background: #FFFFFF', $html);
        $this->assertStringContainsString('overflow: hidden', $html);
    }

    public function test_hover_raises_the_shadow_and_scales_the_image_while_respecting_reduced_motion(): void
    {
        $html = $this->render($this->publication());

        $this->assertStringContainsString('.vv-card:hover {', $html);
        $this->assertStringContainsString('box-shadow: 0 4px 12px rgba(15, 23, 32, .08)', $html);
        $this->assertStringContainsString('.vv-card:hover .vv-media img {', $html);
        $this->assertStringContainsString('transform: scale(1.04)', $html);
        $this->assertStringContainsString('filter: saturate(.85)', $html);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $html);

        $reducedMotionBlock = substr($html, (int) strpos($html, '@media (prefers-reduced-motion: reduce)'));
        $this->assertStringContainsString('transform: none', $reducedMotionBlock);
        $this->assertStringContainsString('filter: none', $reducedMotionBlock);
    }

    public function test_only_the_view_profile_action_uses_the_primary_blue(): void
    {
        $html = $this->render($this->publication());

        // Every other text rule (eyebrow, title, excerpt, meta) uses an
        // --vv-ink-* neutral; --vv-primary-700 is referenced exactly once,
        // for .vv-action.
        $this->assertSame(1, substr_count($html, 'var(--vv-primary-700)'));
        $this->assertStringContainsString('.vv-action {', $html);

        $actionRuleStart = (int) strpos($html, '.vv-action {');
        $actionRule = substr($html, $actionRuleStart, (int) strpos($html, '}', $actionRuleStart) - $actionRuleStart);
        $this->assertStringContainsString('var(--vv-primary-700)', $actionRule);
    }

    public function test_the_verified_badge_uses_success_tint_not_accent(): void
    {
        $html = $this->render($this->publication(['is_verified' => true]));

        $this->assertStringContainsString('.vv-badge-verified {', $html);

        $badgeRuleStart = (int) strpos($html, '.vv-badge-verified {');
        $badgeRule = substr($html, $badgeRuleStart, (int) strpos($html, '}', $badgeRuleStart) - $badgeRuleStart);
        $this->assertStringContainsString('var(--vv-success-050)', $badgeRule);
        $this->assertStringContainsString('var(--vv-success-700)', $badgeRule);
        $this->assertStringNotContainsString('accent', $badgeRule);
    }

    public function test_the_logo_plate_sits_on_the_image_offset_from_its_start_and_bottom_edges(): void
    {
        $publication = $this->publication();
        $publication->addMediaFromBase64(self::VALID_PNG_BASE64)->toMediaCollection('logo', 's3');

        $html = $this->render($publication);

        $this->assertStringContainsString('.vv-logo-plate {', $html);

        $plateRuleStart = (int) strpos($html, '.vv-logo-plate {');
        $plateRule = substr($html, $plateRuleStart, (int) strpos($html, '}', $plateRuleStart) - $plateRuleStart);
        $this->assertStringContainsString('position: absolute', $plateRule);
        $this->assertStringContainsString('inset-inline-start: 12px', $plateRule);
        $this->assertStringContainsString('inset-block-end: 12px', $plateRule);
        $this->assertStringContainsString('inline-size: 44px', $plateRule);
        $this->assertStringContainsString('block-size: 44px', $plateRule);

        // The plate markup itself sits inside .vv-media (on the image),
        // not as a sibling floating outside it.
        $mediaStart = (int) strpos($html, 'class="vv-media"');
        $mediaBlock = substr($html, $mediaStart, (int) strpos($html, '</div>', $mediaStart) + 200 - $mediaStart);
        $this->assertStringContainsString('vv-logo-plate', $mediaBlock);
    }

    public function test_the_stylesheet_renders_exactly_once_per_page_no_matter_how_many_cards(): void
    {
        $category = CompanyCategory::create(['title' => ['en' => 'Many Cards'], 'is_active' => true]);

        for ($i = 0; $i < 4; $i++) {
            $publication = $this->publication(['slug' => "many-cards-{$i}"]);
            $publication->categories()->attach($category);
        }

        $response = $this->get(route('companies.category', ['slug' => $category->slug]));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), '.vv-card {'));
    }

    public function test_the_listing_pages_use_a_responsive_equal_height_grid(): void
    {
        $category = CompanyCategory::create(['title' => ['en' => 'Grid Check'], 'is_active' => true]);
        $publication = $this->publication();
        $publication->categories()->attach($category);

        $categoryResponse = $this->get(route('companies.category', ['slug' => $category->slug]));
        $categoryResponse->assertOk();
        $categoryResponse->assertSee('row g-4', false);
        $categoryResponse->assertSee('col-12 col-sm-6 col-lg-4', false);

        $country = Country::create([
            'name' => ['en' => 'Iran'], 'official_name' => ['en' => 'Iran'], 'capital' => ['en' => 'Tehran'],
            'currency_name' => ['en' => 'Rial'], 'slug' => 'iran-'.uniqid(), 'phone_code' => '98',
            'currency' => 'IRR', 'currency_symbol' => 'IRR', 'is_active' => true,
        ]);
        $state = State::create([
            'country_id' => $country->id, 'name' => ['en' => 'Tehran'], 'type' => ['en' => 'Province'],
            'slug' => 'tehran-'.uniqid(), 'code' => 'THR', 'is_active' => true,
        ]);
        $publication->forceFill(['state_id' => $state->id])->save();

        $stateResponse = $this->get(route('companies.state', ['slug' => $state->slug]));
        $stateResponse->assertOk();
        $stateResponse->assertSee('row g-4', false);
        $stateResponse->assertSee('col-12 col-sm-6 col-lg-4', false);
    }
}
