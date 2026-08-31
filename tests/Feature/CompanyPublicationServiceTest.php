<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyCategory;
use App\Models\CompanyPublication;
use App\Models\Country;
use App\Models\State;
use App\Services\CompanyPublicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyPublicationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');
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

    public function test_publish_snapshots_fields_categories_state_and_media(): void
    {
        $state = $this->makeState();
        $category = CompanyCategory::factory()->create();

        $company = Company::factory()->create([
            'name' => ['en' => 'Acme Co', 'fa' => 'شرکت آکمی'],
            'content' => ['en' => ['v' => 1], 'fa' => ['v' => 1]],
            'website' => 'https://acme.test',
            'phones' => ['02100000000'],
        ]);
        $company->categories()->attach($category);
        $company->addresses()->create([
            'country_id' => $state->country_id,
            'state_id' => $state->id,
            'type' => 'office',
            'address_line' => ['en' => ''],
            'is_primary' => true,
        ]);
        $company->addMedia(UploadedFile::fake()->image('logo.png', 10, 10))
            ->toMediaCollection('logo', 's3');

        $publication = app(CompanyPublicationService::class)->publish($company);

        $this->assertSame($company->slug, $publication->slug);
        $this->assertSame('Acme Co', $publication->getTranslation('name', 'en'));
        $this->assertSame('شرکت آکمی', $publication->getTranslation('name', 'fa'));
        $this->assertSame(['en' => ['v' => 1], 'fa' => ['v' => 1]], $publication->content);
        $this->assertSame('https://acme.test', $publication->website);
        $this->assertSame(['02100000000'], $publication->phones);
        $this->assertSame($state->id, $publication->state_id);
        $this->assertNotNull($publication->published_at);
        $this->assertTrue($publication->categories()->whereKey($category->id)->exists());

        // Media must be an independent copy, not a shared row.
        $this->assertCount(1, $publication->getMedia('logo'));
        $this->assertNotSame(
            $company->getFirstMedia('logo')->id,
            $publication->getFirstMedia('logo')->id,
        );
    }

    public function test_republish_upserts_the_same_row_and_drops_removed_media(): void
    {
        $company = Company::factory()->create(['name' => ['fa' => 'قدیمی']]);
        $company->addMedia(UploadedFile::fake()->image('one.png', 10, 10))
            ->toMediaCollection('certificates', 's3');

        $service = app(CompanyPublicationService::class);

        $first = $service->publish($company);
        $this->assertCount(1, $first->getMedia('certificates'));

        // Owner removes the certificate image and renames the company.
        $company->clearMediaCollection('certificates');
        $company->update(['name' => ['fa' => 'جدید']]);

        $second = $service->publish($company->fresh());

        $this->assertTrue($first->is($second));
        $this->assertSame(1, CompanyPublication::count());
        $this->assertSame('جدید', $second->getTranslation('name', 'fa'));
        $this->assertCount(0, $second->fresh()->getMedia('certificates'));
    }

    public function test_publish_copies_the_seo_meta_snapshot(): void
    {
        $company = Company::factory()->create();
        $company->seo()->create([
            'meta_title' => ['en' => 'SEO Title', 'fa' => 'عنوان سئو'],
            'meta_description' => ['en' => 'SEO Description'],
        ]);

        $publication = app(CompanyPublicationService::class)->publish($company);

        $this->assertSame('SEO Title', $publication->seoTitle('en'));

        // Later draft SEO edits must not leak into the published snapshot.
        $company->seo->setTranslation('meta_title', 'en', 'Changed Title')->save();

        $this->assertSame('SEO Title', $publication->fresh()->seoTitle('en'));
    }

    public function test_publication_and_media_survive_force_deleting_the_company(): void
    {
        $company = Company::factory()->create();
        $company->addMedia(UploadedFile::fake()->image('logo.png', 10, 10))
            ->toMediaCollection('logo', 's3');

        $publication = app(CompanyPublicationService::class)->publish($company);

        $company->forceDelete();

        $publication->refresh();

        $this->assertNull($publication->company_id);
        $this->assertCount(1, $publication->getMedia('logo'));
    }
}
