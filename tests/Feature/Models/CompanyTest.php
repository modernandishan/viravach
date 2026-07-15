<?php

namespace Tests\Feature\Models;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\CompanyBrand;
use App\Models\CompanyCategory;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
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

    public function test_name_and_description_are_translatable(): void
    {
        $company = Company::factory()->create([
            'name' => ['en' => 'Acme Co', 'fa' => 'شرکت آکمی'],
            'description' => ['en' => '<p>About us</p>', 'fa' => '<p>درباره ما</p>'],
        ]);

        $this->assertSame('Acme Co', $company->getTranslation('name', 'en'));
        $this->assertSame('شرکت آکمی', $company->getTranslation('name', 'fa'));
        $this->assertSame('<p>About us</p>', $company->getTranslation('description', 'en'));
    }

    public function test_status_is_cast_to_enum(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Pending]);

        $this->assertSame(CompanyStatus::Pending, $company->status);
    }

    public function test_belongs_to_user(): void
    {
        $company = Company::factory()->create();

        $this->assertTrue($company->user->is($company->user));
        $this->assertTrue($company->user->companies->contains($company));
    }

    public function test_categories_relationship(): void
    {
        $company = Company::factory()->create();
        $category = CompanyCategory::factory()->create();

        $company->categories()->attach($category);

        $this->assertTrue($company->categories()->whereKey($category->id)->exists());
        $this->assertDatabaseHas('company_company_category', [
            'company_id' => $company->id,
            'company_category_id' => $category->id,
        ]);
    }

    public function test_export_countries_relationship(): void
    {
        $company = Company::factory()->create();
        $country = $this->makeCountry();

        $company->exportCountries()->attach($country);

        $this->assertTrue($company->exportCountries()->whereKey($country->id)->exists());
        $this->assertDatabaseHas('company_export_countries', [
            'company_id' => $company->id,
            'country_id' => $country->id,
        ]);
    }

    public function test_primary_address_relation_returns_the_flagged_address(): void
    {
        $company = Company::factory()->create();
        $country = $this->makeCountry();

        CompanyAddress::factory()->create([
            'company_id' => $company->id,
            'country_id' => $country->id,
            'is_primary' => false,
        ]);

        $primary = CompanyAddress::factory()->create([
            'company_id' => $company->id,
            'country_id' => $country->id,
            'is_primary' => true,
        ]);

        $this->assertTrue($company->primaryAddress->is($primary));
    }

    public function test_brands_relationship(): void
    {
        $company = Company::factory()->create();
        CompanyBrand::factory()->count(2)->create(['company_id' => $company->id]);

        $this->assertCount(2, $company->brands);
    }

    public function test_scope_approved_requires_status_and_published_at(): void
    {
        Company::factory()->create(['status' => CompanyStatus::Draft, 'published_at' => now()]);
        Company::factory()->create(['status' => CompanyStatus::Approved, 'published_at' => null]);
        $approved = Company::factory()->create(['status' => CompanyStatus::Approved, 'published_at' => now()]);

        $result = Company::approved()->get();

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($approved));
    }

    public function test_scope_active_excludes_companies_scheduled_in_the_future(): void
    {
        Company::factory()->create([
            'status' => CompanyStatus::Approved,
            'published_at' => now()->addDay(),
        ]);
        $live = Company::factory()->create([
            'status' => CompanyStatus::Approved,
            'published_at' => now()->subDay(),
        ]);

        $result = Company::active()->get();

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($live));
    }
}
