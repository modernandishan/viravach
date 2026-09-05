<?php

namespace Tests\Feature;

use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountryPagesH1Test extends TestCase
{
    use RefreshDatabase;

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
            'is_active' => true,
        ]);
    }

    public function test_the_countries_index_renders_exactly_one_h1(): void
    {
        $response = $this->get(route('companies.countries'));

        $response->assertOk();
        // The toolbar heading is demoted to a span on this route, so only
        // the page card's own h1 remains.
        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
    }

    public function test_the_country_page_renders_exactly_one_h1(): void
    {
        $country = $this->makeCountry();

        $response = $this->get(route('companies.country', ['country' => $country->slug]));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
    }

    public function test_the_country_page_renders_exactly_one_h1_in_rtl(): void
    {
        $country = $this->makeCountry();

        app()->setLocale('fa');

        $response = $this->get(route('companies.country', ['country' => $country->slug]));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
    }
}
