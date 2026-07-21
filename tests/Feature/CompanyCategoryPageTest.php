<?php

namespace Tests\Feature;

use App\Models\CompanyCategory;
use App\Models\CompanyPublication;
use App\Models\Country;
use App\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyCategoryPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeActivePublication(CompanyCategory $category, ?State $state = null): CompanyPublication
    {
        $publication = CompanyPublication::factory()->create([
            'published_at' => now()->subDay(),
            'state_id' => $state?->id,
        ]);

        $publication->categories()->attach($category);

        return $publication;
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

    public function test_it_renders_translated_category_content(): void
    {
        $category = CompanyCategory::factory()->create([
            'slug' => 'agri-industries',
            'title' => ['en' => 'Agriculture', 'fa' => 'کشاورزی'],
            'description' => ['en' => '<p>About agriculture</p>', 'fa' => '<p>درباره کشاورزی</p>'],
        ]);

        $response = $this->get(route('companies.category', ['slug' => $category->slug]));

        $response->assertOk();
        $response->assertSee('Agriculture');
        $response->assertSee('About agriculture', false);
    }

    public function test_it_404s_for_unknown_or_inactive_category(): void
    {
        $this->get(route('companies.category', ['slug' => 'does-not-exist']))->assertNotFound();

        $inactive = CompanyCategory::factory()->create(['slug' => 'inactive-cat', 'is_active' => false]);

        $this->get(route('companies.category', ['slug' => $inactive->slug]))->assertNotFound();
    }

    public function test_it_lists_only_companies_within_the_category_subtree(): void
    {
        $parent = CompanyCategory::factory()->create(['slug' => 'parent-cat']);
        $child = CompanyCategory::factory()->create(['slug' => 'child-cat', 'parent_id' => $parent->id]);
        $other = CompanyCategory::factory()->create(['slug' => 'other-cat']);
        $state = $this->makeState();

        $inChild = $this->makeActivePublication($child, $state);
        $inOther = $this->makeActivePublication($other);

        Livewire::test('pages::company-category', ['slug' => $parent->slug])
            ->assertSeeText($inChild->name)
            ->assertSeeText($child->title)
            ->assertSeeText($state->name)
            ->assertDontSeeText($inOther->name);
    }

    public function test_it_paginates_companies_at_twelve_per_page(): void
    {
        $category = CompanyCategory::factory()->create(['slug' => 'paginated-cat']);

        for ($i = 0; $i < 13; $i++) {
            $this->makeActivePublication($category);
        }

        $component = Livewire::test('pages::company-category', ['slug' => $category->slug]);

        $this->assertCount(12, $component->instance()->companies);

        $component->call('nextPage');

        $this->assertCount(1, $component->instance()->companies);
    }

    public function test_it_records_a_page_view(): void
    {
        $category = CompanyCategory::factory()->create(['slug' => 'viewed-cat']);

        $this->get(route('companies.category', ['slug' => $category->slug]));

        $this->assertDatabaseHas('views', [
            'viewable_type' => CompanyCategory::class,
            'viewable_id' => $category->id,
        ]);
    }
}
