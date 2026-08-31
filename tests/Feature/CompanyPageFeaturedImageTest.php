<?php

namespace Tests\Feature;

use App\Models\CompanyPublication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyPageFeaturedImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');
    }

    private function attachFeaturedImage(CompanyPublication $publication, array $customProperties = []): void
    {
        $publication->addMedia(UploadedFile::fake()->image('featured.jpg', 1024, 576))
            ->withCustomProperties($customProperties)
            ->toMediaCollection('featured_image', 's3');
    }

    public function test_it_renders_the_featured_image_with_localized_alt_text(): void
    {
        $publication = CompanyPublication::factory()->create(['name' => ['en' => 'Acme Co']]);

        $this->attachFeaturedImage($publication, [
            'alt' => ['en' => 'Alt text in English', 'fa' => 'متن جایگزین فارسی'],
            'title' => ['en' => 'Title in English', 'fa' => 'عنوان فارسی'],
        ]);

        $response = $this->get(route('companies.show', ['slug' => $publication->slug]));

        $response->assertOk();
        $response->assertSee('alt="Alt text in English"', false);
        $response->assertSee('title="Title in English"', false);
        $response->assertSee('object-fit: cover', false);
        $response->assertSee('aspect-ratio: 16 / 9', false);
        $response->assertSee('loading="eager"', false);
        $response->assertSee('fetchpriority="high"', false);
    }

    public function test_it_renders_no_image_element_when_there_is_no_featured_image(): void
    {
        $publication = CompanyPublication::factory()->create();

        $response = $this->get(route('companies.show', ['slug' => $publication->slug]));

        $response->assertOk();
        $response->assertDontSee('fetchpriority="high"', false);
        $response->assertDontSee('aspect-ratio: 16 / 9', false);
    }

    public function test_alt_text_falls_back_to_the_fallback_locale_then_to_the_company_name(): void
    {
        $publication = CompanyPublication::factory()->create(['name' => ['ar' => 'اسم الشركة']]);

        // APP_FALLBACK_LOCALE is 'fa' in this project (not 'en') — only
        // that locale has an alt; the current locale (ar) does not.
        $this->attachFeaturedImage($publication, [
            'alt' => ['fa' => 'متن جایگزین فارسی'],
        ]);

        app()->setLocale('ar');

        $response = $this->get(route('companies.show', ['slug' => $publication->slug]));

        $response->assertOk();
        $response->assertSee('alt="متن جایگزین فارسی"', false);

        // Now with no alt at all in any locale: falls all the way back
        // to the company name.
        $noAltPublication = CompanyPublication::factory()->create(['name' => ['ar' => 'شركة بدون صورة']]);
        $noAltPublication->addMedia(UploadedFile::fake()->image('featured.jpg', 1024, 576))
            ->toMediaCollection('featured_image', 's3');

        $response = $this->get(route('companies.show', ['slug' => $noAltPublication->slug]));

        $response->assertOk();
        $response->assertSee('alt="شركة بدون صورة"', false);
    }

    public function test_it_renders_the_caption_and_omits_it_when_empty(): void
    {
        $publication = CompanyPublication::factory()->create();

        $this->attachFeaturedImage($publication, [
            'caption' => ['en' => 'A descriptive caption'],
        ]);

        $response = $this->get(route('companies.show', ['slug' => $publication->slug]));

        $response->assertOk();
        $response->assertSee('A descriptive caption');

        $withoutCaption = CompanyPublication::factory()->create();
        $this->attachFeaturedImage($withoutCaption);

        $response = $this->get(route('companies.show', ['slug' => $withoutCaption->slug]));

        $response->assertOk();
        $response->assertDontSee('A descriptive caption');
    }

    public function test_it_emits_an_imageobject_in_the_existing_jsonld_block(): void
    {
        $publication = CompanyPublication::factory()->create();

        $this->attachFeaturedImage($publication, [
            'caption' => ['en' => 'JSON-LD caption'],
            'description' => ['en' => 'JSON-LD description'],
        ]);

        $response = $this->get(route('companies.show', ['slug' => $publication->slug]));

        $response->assertOk();
        // One JSON-LD script block only — the image is a property on the
        // existing default block, not a second jsonLdMulti() group.
        $this->assertSame(1, substr_count($response->getContent(), 'application/ld+json'));
        $response->assertSee('ImageObject', false);
        $response->assertSee('JSON-LD caption', false);
        $response->assertSee('JSON-LD description', false);
    }
}
