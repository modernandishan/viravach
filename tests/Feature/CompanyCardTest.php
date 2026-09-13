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

        $stateResponse = $this->get(route('companies.state', ['country' => $country->slug, 'state' => $state->slug]));

        $stateResponse->assertOk();
        $stateResponse->assertSee('vv-card', false);
        $stateResponse->assertSee('first-co', false);
        $stateResponse->assertSee('second-co', false);
    }

    /**
     * The card's design rules used to be asserted against the component's own
     * rendered HTML, because they were emitted inline: a literal <style> tag
     * in the .blade.php source is silently swallowed by Livewire's SFC
     * compiler (statically extracted into a "styleModule" asset this app
     * registers no route to serve), so the workaround at the time was to
     * assemble the stylesheet as a PHP string and raw-echo it into every card.
     *
     * That workaround is gone. The whole block was lifted verbatim into
     * resources/css/app.css under the "company-elements/⚡company-card"
     * marker and is now delivered once by the Vite bundle, so the rules are
     * asserted at their new source. Scoping to that one section matters:
     * several tokens (--vv-primary-700 above all) also appear in the dark-mode
     * overrides and in other components' sections, so a file-wide match would
     * be meaningless.
     */
    private function cardStyles(): string
    {
        $css = file_get_contents(base_path('resources/css/app.css'));

        $this->assertIsString($css, 'resources/css/app.css could not be read.');

        $marker = '/* ===== company-elements/⚡company-card ===== */';
        $start = strpos($css, $marker);

        $this->assertNotFalse(
            $start,
            "The [{$marker}] section is gone from resources/css/app.css — the card stylesheet moved again.",
        );

        // Up to the next section marker, or EOF if this is the last one.
        $next = strpos($css, '/* ===== ', $start + strlen($marker));

        return $next === false
            ? substr($css, $start)
            : substr($css, $start, $next - $start);
    }

    public function test_the_card_has_a_visible_border_and_shadow_against_the_page(): void
    {
        $styles = $this->cardStyles();

        $this->assertStringContainsString('border: 1px solid var(--vv-ink-100)', $styles);
        $this->assertStringContainsString('box-shadow: 0 1px 2px rgba(15, 23, 32, .06)', $styles);
        $this->assertStringContainsString('background: #FFFFFF', $styles);
        $this->assertStringContainsString('overflow: hidden', $styles);
    }

    public function test_hover_raises_the_shadow_and_scales_the_image_while_respecting_reduced_motion(): void
    {
        $styles = $this->cardStyles();

        $this->assertStringContainsString('.vv-card:hover {', $styles);
        $this->assertStringContainsString('box-shadow: 0 4px 12px rgba(15, 23, 32, .08)', $styles);
        $this->assertStringContainsString('.vv-card:hover .vv-media img {', $styles);
        $this->assertStringContainsString('transform: scale(1.04)', $styles);
        $this->assertStringContainsString('filter: saturate(.85)', $styles);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $styles);

        $reducedMotionBlock = substr($styles, (int) strpos($styles, '@media (prefers-reduced-motion: reduce)'));
        $this->assertStringContainsString('transform: none', $reducedMotionBlock);
        $this->assertStringContainsString('filter: none', $reducedMotionBlock);
    }

    public function test_only_the_view_profile_action_uses_the_primary_blue(): void
    {
        $styles = $this->cardStyles();

        // Every other text rule (eyebrow, title, excerpt, meta) uses an
        // --vv-ink-* neutral; --vv-primary-700 is referenced exactly once,
        // for .vv-action. Counted within the card section only — the token is
        // declared and re-referenced elsewhere in app.css.
        $this->assertSame(1, substr_count($styles, 'var(--vv-primary-700)'));
        $this->assertStringContainsString('.vv-action {', $styles);

        $actionRuleStart = (int) strpos($styles, '.vv-action {');
        $actionRule = substr($styles, $actionRuleStart, (int) strpos($styles, '}', $actionRuleStart) - $actionRuleStart);
        $this->assertStringContainsString('var(--vv-primary-700)', $actionRule);
    }

    public function test_the_verified_badge_uses_success_tint_not_accent(): void
    {
        $styles = $this->cardStyles();

        $this->assertStringContainsString('.vv-badge-verified {', $styles);

        $badgeRuleStart = (int) strpos($styles, '.vv-badge-verified {');
        $badgeRule = substr($styles, $badgeRuleStart, (int) strpos($styles, '}', $badgeRuleStart) - $badgeRuleStart);
        $this->assertStringContainsString('var(--vv-success-050)', $badgeRule);
        $this->assertStringContainsString('var(--vv-success-700)', $badgeRule);
        $this->assertStringNotContainsString('accent', $badgeRule);
    }

    public function test_the_logo_plate_sits_on_the_image_offset_from_its_start_and_bottom_edges(): void
    {
        $styles = $this->cardStyles();

        $this->assertStringContainsString('.vv-logo-plate {', $styles);

        $plateRuleStart = (int) strpos($styles, '.vv-logo-plate {');
        $plateRule = substr($styles, $plateRuleStart, (int) strpos($styles, '}', $plateRuleStart) - $plateRuleStart);
        $this->assertStringContainsString('position: absolute', $plateRule);
        $this->assertStringContainsString('inset-inline-start: 12px', $plateRule);
        $this->assertStringContainsString('inset-block-end: 12px', $plateRule);
        $this->assertStringContainsString('inline-size: 44px', $plateRule);
        $this->assertStringContainsString('block-size: 44px', $plateRule);

        // The plate markup itself sits inside .vv-media (on the image),
        // not as a sibling floating outside it. Still asserted against the
        // rendered component: this half is markup, not styling.
        $publication = $this->publication();
        $publication->addMediaFromBase64(self::VALID_PNG_BASE64)->toMediaCollection('featured_image', 's3');
        $publication->addMediaFromBase64(self::VALID_PNG_BASE64)->toMediaCollection('logo', 's3');

        $html = $this->render($publication);

        $mediaStart = (int) strpos($html, 'class="vv-media"');
        $mediaBlock = substr($html, $mediaStart, (int) strpos($html, '</div>', $mediaStart) + 200 - $mediaStart);
        $this->assertStringContainsString('vv-logo-plate', $mediaBlock);
    }

    /**
     * Replaces the old "the stylesheet renders exactly once per page no matter
     * how many cards" test, which the move to app.css made vacuous — inline
     * copies are now always zero, so the old assertion of "exactly 1" could
     * only ever fail, and asserting "exactly 0" proves nothing on its own.
     *
     * What still means something is the invariant the old test was protecting:
     * a listing page's weight must not grow by one copy of the stylesheet per
     * card. So this asserts the page really does render many cards while
     * inlining the rules zero times — which is what would break the moment
     * anyone re-inlines a <style> block or a cardStyles() echo into the
     * component — and that the section in app.css declares the base rule once,
     * keeping a single source of truth for it.
     */
    public function test_the_card_stylesheet_is_never_inlined_per_card(): void
    {
        $category = CompanyCategory::create(['title' => ['en' => 'Many Cards'], 'is_active' => true]);

        for ($i = 0; $i < 4; $i++) {
            $publication = $this->publication(['slug' => "many-cards-{$i}"]);
            $publication->categories()->attach($category);
        }

        $response = $this->get(route('companies.category', ['slug' => $category->slug]));

        $response->assertOk();
        $content = $response->getContent();

        // The page genuinely carries four cards...
        $this->assertSame(4, substr_count($content, 'class="vv-card"'));

        // ...and not one byte of their stylesheet, however many there are.
        $this->assertSame(0, substr_count($content, '.vv-card {'));
        $this->assertSame(0, substr_count($content, 'box-shadow: 0 1px 2px rgba(15, 23, 32, .06)'));

        // One base rule in the shared stylesheet, not one per usage.
        $this->assertSame(1, substr_count($this->cardStyles(), '.vv-card {'));
    }

    /**
     * The original bug was that the rules never reached the browser at all.
     * app.css only fixes that if the compiled bundle actually carries them,
     * which the PHP suite cannot take for granted: public/build is gitignored,
     * so it is absent until someone runs `npm run build`.
     */
    public function test_the_built_bundle_ships_the_card_rules(): void
    {
        $bundles = glob(public_path('build/assets/app-*.css'));

        if ($bundles === false || $bundles === []) {
            $this->markTestSkipped('No compiled bundle in public/build — run `npm run build` to cover this.');
        }

        $css = implode('', array_map(file_get_contents(...), $bundles));

        $this->assertStringContainsString('.vv-card{', $css);
        $this->assertStringContainsString('.vv-logo-plate{', $css);
        $this->assertStringContainsString('.vv-badge-verified{', $css);
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

        $stateResponse = $this->get(route('companies.state', ['country' => $country->slug, 'state' => $state->slug]));
        $stateResponse->assertOk();
        $stateResponse->assertSee('row g-4', false);
        $stateResponse->assertSee('col-12 col-sm-6 col-lg-4', false);
    }
}
