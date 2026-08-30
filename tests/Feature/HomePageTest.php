<?php

namespace Tests\Feature;

use Database\Seeders\HomePageSeeder;
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

    public function test_it_returns_200(): void
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_it_sets_seo_meta(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        // The landing layout builds the title from the seeded Page row's
        // title plus the site tagline.
        $response->assertSee('<title>Viravach', false);
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
