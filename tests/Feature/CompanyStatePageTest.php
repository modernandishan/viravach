<?php

namespace Tests\Feature;

use App\Models\CompanyPublication;
use App\Models\Country;
use App\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyStatePageTest extends TestCase
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
        ]);
    }

    private function makeState(?Country $country = null, array $overrides = []): State
    {
        return State::create(array_merge([
            'country_id' => ($country ?? $this->makeCountry())->id,
            'name' => ['en' => 'Test Province', 'fa' => 'استان تست'],
            'type' => ['en' => 'Province', 'fa' => 'استان'],
            'slug' => 'test-province-'.uniqid(),
            'code' => 'TP',
            'is_active' => true,
        ], $overrides));
    }

    private function makeActivePublicationInState(State $state): CompanyPublication
    {
        return CompanyPublication::factory()->create([
            'published_at' => now()->subDay(),
            'state_id' => $state->id,
        ]);
    }

    public function test_it_renders_translated_state_content(): void
    {
        $state = $this->makeState();

        $response = $this->get(route('companies.state', ['slug' => $state->slug]));

        $response->assertOk();
        $response->assertSee('Test Province');
    }

    public function test_it_404s_for_unknown_or_inactive_state(): void
    {
        $this->get(route('companies.state', ['slug' => 'does-not-exist']))->assertNotFound();

        $inactive = $this->makeState(overrides: ['is_active' => false]);

        $this->get(route('companies.state', ['slug' => $inactive->slug]))->assertNotFound();
    }

    public function test_it_lists_only_companies_with_an_address_in_the_state(): void
    {
        $stateA = $this->makeState();
        $stateB = $this->makeState();

        $inA = $this->makeActivePublicationInState($stateA);
        $inB = $this->makeActivePublicationInState($stateB);

        Livewire::test('pages::company-state', ['slug' => $stateA->slug])
            ->assertSeeText($inA->name)
            ->assertDontSeeText($inB->name);
    }

    public function test_it_paginates_companies_at_twelve_per_page(): void
    {
        $state = $this->makeState();

        for ($i = 0; $i < 13; $i++) {
            $this->makeActivePublicationInState($state);
        }

        $component = Livewire::test('pages::company-state', ['slug' => $state->slug]);

        $this->assertCount(12, $component->instance()->companies);

        $component->call('nextPage');

        $this->assertCount(1, $component->instance()->companies);
    }

    public function test_it_records_a_page_view(): void
    {
        $state = $this->makeState();

        $this->get(route('companies.state', ['slug' => $state->slug]));

        $this->assertDatabaseHas('views', [
            'viewable_type' => State::class,
            'viewable_id' => $state->id,
        ]);
    }
}
