<?php

namespace Tests\Feature\Ai;

use App\Ai\Input\CompanyInputCollector;
use App\Enums\CompanyReviewStatus;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\CompanyBrand;
use App\Models\CompanyCategory;
use App\Models\Country;
use App\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyInputCollectorTest extends TestCase
{
    use RefreshDatabase;

    private function makeCountry(string $name): Country
    {
        return Country::create([
            'name' => ['en' => $name, 'fa' => $name.' فارسی'],
            'official_name' => ['en' => 'Republic of '.$name],
            'capital' => ['en' => 'Capital City'],
            'currency_name' => ['en' => 'Test Dollar'],
            'slug' => strtolower($name).'-'.uniqid(),
            'phone_code' => '+000',
            'currency' => 'TST',
            'currency_symbol' => 'T$',
        ]);
    }

    private function makeState(string $name): State
    {
        $country = $this->makeCountry('Testland');

        return State::create([
            'country_id' => $country->id,
            'name' => ['en' => $name, 'fa' => $name.' فارسی'],
            'type' => ['en' => 'Province'],
            'slug' => strtolower($name).'-'.uniqid(),
            'code' => 'TP',
            'is_active' => true,
        ]);
    }

    public function test_payload_contains_name_brief_categories_state_export_countries_and_brands(): void
    {
        $state = $this->makeState('Test Province');
        $city = City::create([
            'state_id' => $state->id,
            'name' => ['en' => 'Test City', 'fa' => 'شهر تست'],
            'slug' => 'test-city-'.uniqid(),
            'is_active' => true,
        ]);

        $root = CompanyCategory::factory()->create(['parent_id' => null, 'title' => ['en' => 'Industrial Equipment', 'fa' => 'تجهیزات صنعتی']]);
        $child = CompanyCategory::factory()->create(['parent_id' => $root->id, 'title' => ['en' => 'Insulation', 'fa' => 'عایق']]);

        $company = Company::factory()->create([
            'name' => ['en' => 'Acme Co', 'fa' => 'شرکت آکمی'],
            'brief' => 'We manufacture industrial insulation panels.',
            'brief_locale' => 'en',
            'established_at' => '1998-04-12',
        ]);
        $company->categories()->attach([$child->id, $root->id]);
        $company->exportCountries()->attach([
            $this->makeCountry('Beta Republic')->id,
            $this->makeCountry('Alpha Republic')->id,
        ]);
        CompanyBrand::factory()->create([
            'company_id' => $company->id,
            'name' => ['en' => 'ThermoPan', 'fa' => 'ترموپن'],
        ]);
        CompanyAddress::create([
            'company_id' => $company->id,
            'country_id' => $state->country_id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'type' => 'office',
            'address_line' => ['fa' => 'خیابان نمونه ۱', 'en' => 'Sample Street 1'],
            'is_primary' => true,
        ]);

        $collector = new CompanyInputCollector;
        $payload = $collector->collect($company->fresh());

        $this->assertSame('Acme Co', $payload['name']);
        $this->assertSame('We manufacture industrial insulation panels.', $payload['brief']);
        $this->assertSame('en', $payload['brief_locale']);
        $this->assertSame('1998-04-12', $payload['established_at']);

        // Ancestor path is joined with ' > ', root first.
        $this->assertSame(
            ['Industrial Equipment', 'Industrial Equipment > Insulation'],
            $payload['categories'],
        );

        $this->assertSame('Test Province', $payload['state']);
        $this->assertSame('Test City', $payload['city']);

        // Sorted, so attach order never reaches the payload.
        $this->assertSame(['Alpha Republic', 'Beta Republic'], $payload['export_countries']);
        $this->assertSame(['ThermoPan'], $payload['brands']);

        // Nothing that changes without a user content edit may appear.
        foreach (['id', 'user_id', 'slug', 'created_at', 'updated_at', 'review_status', 'reviewed_at', 'is_verified', 'is_featured'] as $key) {
            $this->assertArrayNotHasKey($key, $payload);
        }
    }

    public function test_reattaching_categories_in_a_different_order_produces_the_same_hash(): void
    {
        $root = CompanyCategory::factory()->create(['parent_id' => null, 'title' => ['en' => 'Root Category']]);
        $child = CompanyCategory::factory()->create(['parent_id' => $root->id, 'title' => ['en' => 'Child Category']]);
        $other = CompanyCategory::factory()->create(['parent_id' => null, 'title' => ['en' => 'Other Category']]);

        $company = Company::factory()->create();
        $company->categories()->attach([$root->id, $other->id, $child->id]);

        $collector = new CompanyInputCollector;
        $originalHash = $collector->hash($collector->collect($company->fresh()));

        $company->categories()->sync([$child->id, $other->id, $root->id]);

        $this->assertSame($originalHash, $collector->hash($collector->collect($company->fresh())));
    }

    public function test_editing_brief_changes_the_hash(): void
    {
        $company = Company::factory()->create(['brief' => 'Original brief.']);

        $collector = new CompanyInputCollector;
        $originalHash = $collector->hash($collector->collect($company->fresh()));

        $company->update(['brief' => 'Rewritten brief with new positioning.']);

        $this->assertNotSame($originalHash, $collector->hash($collector->collect($company->fresh())));
    }

    public function test_irrelevant_changes_do_not_change_the_hash(): void
    {
        $company = Company::factory()->create();

        $collector = new CompanyInputCollector;
        $originalHash = $collector->hash($collector->collect($company->fresh()));

        // Review state, verification flags and the row timestamp all change
        // through the admin workflow, never through generation-relevant input.
        $company->forceFill([
            'review_status' => CompanyReviewStatus::Approved,
            'reviewed_at' => now(),
            'is_verified' => true,
            'is_featured' => true,
        ])->save();
        $company->touch();

        $this->assertSame($originalHash, $collector->hash($collector->collect($company->fresh())));
    }

    public function test_collects_without_error_when_the_company_has_no_relations(): void
    {
        $company = Company::factory()->create();

        $payload = (new CompanyInputCollector)->collect($company->fresh());

        $this->assertNotEmpty($payload['name']);
        $this->assertArrayNotHasKey('categories', $payload);
        $this->assertArrayNotHasKey('brands', $payload);
        $this->assertArrayNotHasKey('state', $payload);
        $this->assertArrayNotHasKey('city', $payload);
        $this->assertArrayNotHasKey('export_countries', $payload);
    }
}
