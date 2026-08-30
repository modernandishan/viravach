<?php

namespace Tests\Feature\Models;

use App\Models\Company;
use App\Models\CompanyBrand;
use App\Models\CompanyCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlugGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_english_named_company_gets_a_readable_latin_slug_plus_a_suffix(): void
    {
        $company = Company::create([
            'user_id' => User::factory()->create()->id,
            'name' => ['en' => 'Acme Industrial Co', 'fa' => 'شرکت آکمی'],
            'brief' => 'b',
            'brief_locale' => 'en',
        ]);

        $this->assertMatchesRegularExpression('/^acme-industrial-co-[a-z0-9]{6}$/', $company->slug);
    }

    public function test_a_persian_only_named_company_gets_a_latin_slug_never_empty_or_persian(): void
    {
        $company = Company::create([
            'user_id' => User::factory()->create()->id,
            'name' => ['fa' => 'شرکت آکمی صنعتی'],
            'brief' => 'b',
            'brief_locale' => 'fa',
        ]);

        // No English translation exists; the fallback-locale one is Persian.
        // Str::slug transliterates it to latin characters where iconv is
        // available, or the trait falls back to a random latin string —
        // either way: non-empty, latin-only, with Company's random suffix.
        $this->assertNotSame('', $company->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $company->slug);
        $this->assertMatchesRegularExpression('/-[a-z0-9]{6}$/', $company->slug);
        $this->assertDoesNotMatchRegularExpression('/[\x{0600}-\x{06FF}]/u', $company->slug);
    }

    public function test_a_category_and_a_brand_each_get_a_slug_without_one_being_supplied(): void
    {
        $category = CompanyCategory::create([
            'title' => ['en' => 'Industrial Equipment', 'fa' => 'تجهیزات صنعتی'],
            'is_active' => true,
        ]);

        $company = Company::factory()->create();
        $brand = CompanyBrand::create([
            'company_id' => $company->id,
            'name' => ['en' => 'ThermoPan', 'fa' => 'ترموپن'],
        ]);

        $this->assertSame('industrial-equipment', $category->slug);
        $this->assertSame('thermopan', $brand->slug);
    }

    public function test_a_second_category_with_the_same_title_gets_a_distinct_slug(): void
    {
        $first = CompanyCategory::create([
            'title' => ['en' => 'Industrial Equipment'],
            'is_active' => true,
        ]);
        $second = CompanyCategory::create([
            'title' => ['en' => 'Industrial Equipment'],
            'is_active' => true,
        ]);

        $this->assertSame('industrial-equipment', $first->slug);
        $this->assertMatchesRegularExpression('/^industrial-equipment-[a-z0-9]{6}$/', $second->slug);
        $this->assertNotSame($first->slug, $second->slug);
    }

    public function test_an_explicitly_supplied_slug_is_never_overwritten(): void
    {
        $company = Company::create([
            'user_id' => User::factory()->create()->id,
            'slug' => 'my-custom-slug',
            'name' => ['en' => 'Acme Industrial Co'],
            'brief' => 'b',
            'brief_locale' => 'en',
        ]);

        $category = CompanyCategory::create([
            'slug' => 'my-category-slug',
            'title' => ['en' => 'Industrial Equipment'],
            'is_active' => true,
        ]);

        $this->assertSame('my-custom-slug', $company->slug);
        $this->assertSame('my-category-slug', $category->slug);
    }

    public function test_updating_a_name_does_not_change_an_existing_slug(): void
    {
        $company = Company::create([
            'user_id' => User::factory()->create()->id,
            'name' => ['en' => 'Original Name Co'],
            'brief' => 'b',
            'brief_locale' => 'en',
        ]);

        $slug = $company->slug;

        $company->update(['name' => ['en' => 'Renamed Industrial Co']]);

        $this->assertSame($slug, $company->fresh()->slug);
    }
}
