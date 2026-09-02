<?php

namespace Tests\Feature;

use App\Models\CompanyCategory;
use App\Models\CompanyPublication;
use App\Models\Country;
use App\Models\Page;
use App\Models\State;
use Database\Seeders\HomePageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Sections cache their payloads with Cache::rememberForever; start
        // every test from a cold cache so fixtures are actually counted.
        Cache::flush();

        $this->seed(HomePageSeeder::class);
    }

    private function makeCountry(): Country
    {
        return Country::create([
            'name' => ['en' => 'Testland', 'fa' => 'تست‌لند'],
            'official_name' => ['en' => 'Republic of Testland', 'fa' => 'جمهوری تست‌لند'],
            'capital' => ['en' => 'Test City', 'fa' => 'شهر تست'],
            'currency_name' => ['en' => 'Test Dollar', 'fa' => 'دلار تست'],
            'slug' => 'testland-'.uniqid(),
            'phone_code' => '+000',
            'currency' => 'TST',
            'currency_symbol' => 'T$',
        ]);
    }

    private function makeState(Country $country, string $slug): State
    {
        return State::create([
            'country_id' => $country->id,
            'name' => ['en' => 'Test Province', 'fa' => 'استان تست'],
            'type' => ['en' => 'Province', 'fa' => 'استان'],
            'slug' => $slug,
            'code' => 'TP',
            'is_active' => true,
        ]);
    }

    public function test_it_returns_200(): void
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_it_renders_exactly_one_h1_from_the_page_row(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();

        $this->assertSame(1, substr_count($response->getContent(), '<h1'), 'The homepage must have exactly one <h1>');

        $response->assertSee('The directory of Iranian exporters and suppliers', false);
    }

    public function test_editing_the_page_row_changes_the_rendered_output(): void
    {
        $page = Page::where('slug', '/')->firstOrFail();

        $page->setTranslation('h1', 'en', 'Buyers start here');
        $page->setTranslation('subheading', 'en', 'A new subheading.');
        $page->save();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Buyers start here', false);
        $response->assertSee('A new subheading.', false);
        $response->assertDontSee('The directory of Iranian exporters and suppliers', false);
    }

    public function test_the_seo_meta_title_and_description_render_in_the_head(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('<title>Viravach | Directory of Iranian exporters for international buyers', false);
        $response->assertSee('Viravach introduces Iranian companies and suppliers across categories and provinces to international buyers.', false);
    }

    public function test_the_category_grid_links_to_real_category_pages_with_counts(): void
    {
        $category = CompanyCategory::factory()->create([
            'title' => ['en' => 'Pistachios', 'fa' => 'پسته'],
            'slug' => 'pistachios',
        ]);

        $publication = CompanyPublication::factory()->create();
        $publication->categories()->attach($category->id);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Pistachios', false);
        $response->assertSee('href="'.route('companies.category', ['slug' => 'pistachios']).'"', false);
        $response->assertSee('1 companies', false);
    }

    public function test_recent_companies_render_via_the_card_component(): void
    {
        $publication = CompanyPublication::factory()->create([
            'name' => ['en' => 'Acme Trading Co', 'fa' => 'شرکت آکمی'],
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Acme Trading Co', false);
        $response->assertSee('vv-card', false);
        $response->assertSee('href="'.route('companies.show', ['slug' => $publication->slug]).'"', false);
    }

    public function test_the_province_grid_links_to_real_state_pages(): void
    {
        $state = $this->makeState($this->makeCountry(), 'test-province');

        CompanyPublication::factory()->create(['state_id' => $state->id]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Test Province', false);
        $response->assertSee('href="'.route('companies.state', ['country' => $state->country->slug, 'state' => 'test-province']).'"', false);
    }

    public function test_key_numbers_reflect_actual_published_data(): void
    {
        $category = CompanyCategory::factory()->create();
        $country = $this->makeCountry();
        $state = $this->makeState($country, 'test-province');

        $first = CompanyPublication::factory()->create(['state_id' => $state->id]);
        $second = CompanyPublication::factory()->create();
        $first->categories()->attach($category->id);
        $second->categories()->attach($category->id);
        $first->company->exportCountries()->attach($country->id);

        // A scheduled (not yet active) publication must not be counted.
        CompanyPublication::factory()->create(['published_at' => now()->addDay()]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('The directory at a glance', false);

        $component = Livewire::test('pages::home');

        $this->assertSame(2, $component->instance()->homeNumbers['companies']);
        $this->assertSame(1, $component->instance()->homeNumbers['categories']);
        $this->assertSame(1, $component->instance()->homeNumbers['states']);
        $this->assertSame(1, $component->instance()->homeNumbers['countries']);
    }

    public function test_empty_sections_render_nothing_instead_of_empty_containers(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringNotContainsString('vv-tile-grid', $content);
        $this->assertStringNotContainsString('vv-recent', $content);
        $this->assertStringNotContainsString('vv-states-grid', $content);
        $this->assertStringNotContainsString('vv-numbers-grid', $content);
    }

    public function test_the_page_renders_in_fa_and_en_without_error(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('The directory of Iranian exporters and suppliers', false);

        app()->setLocale('fa');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('دایرکتوری صادرکنندگان و تأمین‌کنندگان ایرانی', false);
    }

    public function test_search_assembles_the_keyword_into_the_query_parameters(): void
    {
        $component = Livewire::test('home.advance-search');

        $component->set('keyword', 'Acme')
            ->call('search');

        $this->assertSame('Acme', $component->instance()->appliedFilters['keyword']);
    }

    public function test_reset_filters_clears_every_filter(): void
    {
        $component = Livewire::test('home.advance-search');

        $component->set('keyword', 'Acme')
            ->set('categoryId', 3)
            ->set('stateId', 5)
            ->set('verifiedOnly', true)
            ->call('search')
            ->call('resetFilters');

        $this->assertSame('', $component->instance()->keyword);
        $this->assertNull($component->instance()->categoryId);
        $this->assertNull($component->instance()->stateId);
        $this->assertFalse($component->instance()->verifiedOnly);
        $this->assertSame([], $component->instance()->appliedFilters);
    }
}
