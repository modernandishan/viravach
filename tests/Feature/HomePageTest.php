<?php

namespace Tests\Feature;

use App\Models\CompanyCategory;
use App\Models\CompanyPublication;
use App\Models\Country;
use App\Models\State;
use Database\Seeders\HomePageSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HomePageSeeder::class);
    }

    private function makeState(): State
    {
        $country = Country::create([
            'name' => ['en' => 'Testland'],
            'official_name' => ['en' => 'Republic of Testland'],
            'capital' => ['en' => 'Test City'],
            'currency_name' => ['en' => 'Test Dollar'],
            'slug' => 'testland-'.uniqid(),
            'phone_code' => '+000',
            'currency' => 'TST',
            'currency_symbol' => 'T$',
            'is_active' => true,
        ]);

        return State::create([
            'country_id' => $country->id,
            'name' => ['en' => 'Test Province'],
            'type' => ['en' => 'Province'],
            'slug' => 'test-province-'.uniqid(),
            'code' => 'TP',
            'is_active' => true,
        ]);
    }

    private function makeActivePublication(?CompanyCategory $category = null, ?State $state = null, ?\DateTimeInterface $publishedAt = null): CompanyPublication
    {
        $publication = CompanyPublication::factory()->create([
            'published_at' => $publishedAt ?? now()->subDay(),
            'state_id' => $state?->id,
        ]);

        if ($category) {
            $publication->categories()->attach($category);
        }

        return $publication;
    }

    public function test_it_returns_200(): void
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_it_sets_seo_meta(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(__('home.hero_title'), false);
        $response->assertSee(__('home.meta_description'), false);
    }

    public function test_it_renders_every_section_with_empty_state_fallbacks_when_there_is_no_data(): void
    {
        Livewire::test('pages::home')
            ->assertSeeText(__('home.hero_title'))
            ->assertSeeText(__('home.stats_companies'))
            ->assertSeeText(__('home.categories_title'))
            ->assertSeeText(__('home.categories_empty'))
            ->assertSeeText(__('home.featured_title'))
            ->assertSeeText(__('home.featured_empty'))
            ->assertSeeText(__('home.states_title'))
            ->assertSeeText(__('home.states_empty'))
            ->assertSeeText(__('home.pricing_title'))
            ->assertSeeText(__('home.pricing_empty'))
            ->assertSeeText(__('home.cta_title'));
    }

    public function test_it_shows_real_stats_and_a_root_category_with_a_rolled_up_descendant_count(): void
    {
        $root = CompanyCategory::factory()->create(['parent_id' => null, 'title' => ['en' => 'Root Category']]);
        $child = CompanyCategory::factory()->create(['parent_id' => $root->id]);
        $grandchild = CompanyCategory::factory()->create(['parent_id' => $child->id]);
        $state = $this->makeState();

        // One company attached directly to the grandchild — should still
        // count toward the root's rolled-up total.
        $this->makeActivePublication($grandchild, $state);

        $component = Livewire::test('pages::home');

        $component->assertSeeText('Root Category');
        $component->assertSeeText(__('home.categories_count', ['count' => '1']));
        $this->assertSame(1, $component->instance()->stats['companies']);
        $this->assertSame(1, $component->instance()->stats['states']);
    }

    public function test_it_lists_the_latest_and_most_viewed_companies(): void
    {
        $category = CompanyCategory::factory()->create();
        $older = $this->makeActivePublication($category, publishedAt: now()->subWeek());
        $newest = $this->makeActivePublication($category, publishedAt: now()->subHour());

        Livewire::test('pages::home')
            ->assertSeeTextInOrder([$newest->name, $older->name]);
    }

    public function test_it_shows_states_with_published_companies_and_their_counts(): void
    {
        $category = CompanyCategory::factory()->create();
        $state = $this->makeState();
        $this->makeActivePublication($category, $state);

        Livewire::test('pages::home')
            ->assertSeeText($state->name)
            ->assertSeeText(__('home.states_count', ['count' => '1']));
    }

    public function test_it_shows_pricing_plans_when_seeded(): void
    {
        $this->seed(PlanSeeder::class);

        Livewire::test('pages::home')
            ->assertDontSeeText(__('home.pricing_empty'))
            ->assertSeeText(__('subscriptions.free_badge'));
    }

    public function test_search_finds_a_company_by_name(): void
    {
        $publication = CompanyPublication::factory()->create([
            'published_at' => now()->subDay(),
            'name' => ['en' => 'Acme Trading Co'],
        ]);

        Livewire::test('pages::home')
            ->set('search', 'Acme')
            ->assertSeeText('Acme Trading Co');
    }

    public function test_search_with_no_matches_shows_the_no_results_message_and_a_categories_cta(): void
    {
        Livewire::test('pages::home')
            ->set('search', 'Nonexistent Company Name')
            ->assertSeeText(__('home.hero_search_no_results', ['term' => 'Nonexistent Company Name']))
            ->assertSeeText(__('home.hero_browse_categories_cta'));
    }
}
