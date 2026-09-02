<?php

namespace Tests\Feature;

use App\Models\CompanyCategory;
use App\Models\CompanyPublication;
use App\Models\Country;
use App\Models\GeneralSetting;
use App\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FooterTest extends TestCase
{
    use RefreshDatabase;

    private function country(): Country
    {
        return Country::create([
            'name' => ['en' => 'Iran'], 'official_name' => ['en' => 'Iran'], 'capital' => ['en' => 'Tehran'],
            'currency_name' => ['en' => 'Rial'], 'slug' => 'iran-'.uniqid(), 'phone_code' => '98',
            'currency' => 'IRR', 'currency_symbol' => 'IRR', 'is_active' => true,
        ]);
    }

    private function state(string $name, string $slug): State
    {
        return State::create([
            'country_id' => $this->country()->id, 'name' => ['en' => $name], 'type' => ['en' => 'Province'],
            'slug' => $slug, 'code' => strtoupper(substr($slug, 0, 3)), 'is_active' => true,
        ]);
    }

    private function category(string $title): CompanyCategory
    {
        return CompanyCategory::create(['title' => ['en' => $title], 'is_active' => true]);
    }

    private function publishedCompany(array $overrides = []): CompanyPublication
    {
        return CompanyPublication::factory()->create($overrides);
    }

    private function render(): string
    {
        return Livewire::test('footer')->html();
    }

    public function test_the_four_columns_render_with_their_links(): void
    {
        $category = $this->category('Industrial Equipment');
        $state = $this->state('Tehran', 'tehran-'.uniqid());

        $company = $this->publishedCompany(['state_id' => $state->id]);
        $company->categories()->attach($category);

        $html = $this->render();

        // Column 1 — brand.
        $this->assertStringContainsString('vv-footer-brand-logo', $html);

        // Column 2 — top categories.
        $this->assertStringContainsString(__('footer.categories_heading'), $html);
        $this->assertStringContainsString(route('companies.category', ['slug' => $category->slug]), $html);
        $this->assertStringContainsString('Industrial Equipment', $html);

        // Column 3 — provinces.
        $this->assertStringContainsString(__('footer.states_heading'), $html);
        $this->assertStringContainsString(route('companies.state', ['country' => $state->country->slug, 'state' => $state->slug]), $html);
        $this->assertStringContainsString('Tehran', $html);

        // Column 4 — company & contact.
        $this->assertStringContainsString(e(__('footer.company_heading')), $html);
        $this->assertStringContainsString('href="'.route('pricing').'"', $html);
        $this->assertStringContainsString('href="'.route('terms-and-conditions').'"', $html);
        $this->assertStringContainsString('href="'.route('auth.sign-up').'"', $html);
    }

    public function test_categories_are_ordered_by_published_company_count_descending(): void
    {
        $few = $this->category('Textiles');
        $many = $this->category('Chemicals');
        $none = $this->category('Empty Category');

        $this->publishedCompany()->categories()->attach($few);

        foreach (range(1, 3) as $i) {
            $this->publishedCompany(['slug' => "chemicals-{$i}"])->categories()->attach($many);
        }

        $html = $this->render();

        $this->assertStringNotContainsString('Empty Category', $html);

        $manyPosition = strpos($html, 'Chemicals');
        $fewPosition = strpos($html, 'Textiles');

        $this->assertNotFalse($manyPosition);
        $this->assertNotFalse($fewPosition);
        $this->assertLessThan($fewPosition, $manyPosition);
    }

    public function test_states_are_ordered_by_published_company_count_descending(): void
    {
        $few = $this->state('Fars', 'fars-'.uniqid());
        $many = $this->state('Alborz', 'alborz-'.uniqid());

        $this->publishedCompany(['state_id' => $few->id]);

        foreach (range(1, 3) as $i) {
            $this->publishedCompany(['slug' => "alborz-co-{$i}", 'state_id' => $many->id]);
        }

        $html = $this->render();

        $manyPosition = strpos($html, 'Alborz');
        $fewPosition = strpos($html, 'Fars');

        $this->assertNotFalse($manyPosition);
        $this->assertNotFalse($fewPosition);
        $this->assertLessThan($fewPosition, $manyPosition);
    }

    public function test_an_empty_enamad_setting_renders_no_trust_strip(): void
    {
        GeneralSetting::current()->update(['enamad_html' => null]);

        $html = $this->render();

        // Substring-only, not the CSS selector: the stylesheet legitimately
        // mentions the class name in its own rules regardless of whether any
        // badge markup renders (see CompanyCardTest for the same caveat).
        $this->assertStringNotContainsString('class="vv-footer-trust-plate"', $html);
    }

    public function test_a_set_enamad_setting_renders_sanitised_markup_and_strips_a_script_tag(): void
    {
        GeneralSetting::current()->update([
            'enamad_html' => '<a href="https://trustseal.enamad.ir/?id=123" target="_blank" onclick="steal()">'
                .'<img src="https://trustseal.enamad.ir/logo.aspx?id=123" alt="نماد اعتماد" onerror="steal()">'
                .'</a><script>alert(1)</script>',
        ]);

        $html = $this->render();

        $this->assertStringContainsString('class="vv-footer-trust-plate"', $html);
        $this->assertStringContainsString('trustseal.enamad.ir', $html);
        $this->assertStringContainsString('نماد اعتماد', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('onerror', $html);

        $plateStart = (int) strpos($html, 'class="vv-footer-trust-plate"');
        $plateBlock = substr($html, $plateStart, (int) strpos($html, '</div>', $plateStart) - $plateStart);
        $this->assertStringNotContainsString('target=', $plateBlock);
    }

    public function test_the_copyright_line_links_to_hktp(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('href="https://hktp.ir"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener"', $html);
        $this->assertStringContainsString((string) now()->year, $html);
    }

    public function test_the_locale_switcher_lists_all_five_locales_and_marks_the_current_one(): void
    {
        app()->setLocale('fa');

        $html = $this->render();

        $this->assertStringContainsString('English', $html);
        $this->assertStringContainsString('فارسی', $html);
        $this->assertStringContainsString('العربية', $html);
        $this->assertStringContainsString('русский', $html);
        $this->assertStringContainsString('Türkçe', $html);

        $activePosition = strpos($html, 'is-active');
        $this->assertNotFalse($activePosition);

        $faPosition = strpos($html, 'فارسی');
        // The active class and the Persian label should be part of the same anchor.
        $anchorStart = strrpos(substr($html, 0, $faPosition), '<a');
        $anchorBlock = substr($html, $anchorStart, $faPosition - $anchorStart);
        $this->assertStringContainsString('is-active', $anchorBlock);
    }

    public function test_the_footer_renders_in_fa_and_en_without_layout_errors(): void
    {
        app()->setLocale('en');
        $enResponse = $this->get(route('pricing'));
        $enResponse->assertOk();
        $enResponse->assertSee(__('footer.company_heading'));

        app()->setLocale('fa');
        $faResponse = $this->get(route('pricing'));
        $faResponse->assertOk();
        $faResponse->assertSee(__('footer.company_heading'));
    }
}
