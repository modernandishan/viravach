<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyPublication;
use App\Services\CompanyPublicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_the_published_snapshot(): void
    {
        $publication = CompanyPublication::factory()->create([
            'name' => ['en' => 'Acme Trading Co', 'fa' => 'شرکت آکمی'],
            'description' => ['en' => '<p>About Acme</p>', 'fa' => '<p>درباره آکمی</p>'],
            'website' => 'https://acme.test',
        ]);

        $response = $this->get(route('companies.show', ['slug' => $publication->slug]));

        $response->assertOk();
        $response->assertSee('Acme Trading Co');
        $response->assertSee('About Acme', false);
        $response->assertSee('https://acme.test');
    }

    public function test_a_company_without_a_publication_is_not_publicly_visible(): void
    {
        $company = Company::factory()->create();

        $this->get(route('companies.show', ['slug' => $company->slug]))->assertNotFound();
    }

    public function test_a_scheduled_publication_is_not_visible_yet(): void
    {
        $publication = CompanyPublication::factory()->create([
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('companies.show', ['slug' => $publication->slug]))->assertNotFound();
    }

    public function test_it_records_a_page_view_against_the_publication(): void
    {
        $publication = CompanyPublication::factory()->create();

        $this->get(route('companies.show', ['slug' => $publication->slug]));

        $this->assertDatabaseHas('views', [
            'viewable_type' => CompanyPublication::class,
            'viewable_id' => $publication->id,
        ]);
    }

    public function test_the_public_page_keeps_serving_the_snapshot_after_owner_edits(): void
    {
        $company = Company::factory()->create(['name' => ['en' => 'Approved Name']]);

        $publication = app(CompanyPublicationService::class)->publish($company);

        $company->update(['name' => ['en' => 'Edited Draft Name']]);

        $response = $this->get(route('companies.show', ['slug' => $publication->slug]));

        $response->assertOk();
        $response->assertSee('Approved Name');
        $response->assertDontSee('Edited Draft Name');
    }

    public function test_the_public_page_survives_deleting_the_company(): void
    {
        $company = Company::factory()->create(['name' => ['en' => 'Everlasting Co']]);

        $publication = app(CompanyPublicationService::class)->publish($company);

        $company->forceDelete();

        $response = $this->get(route('companies.show', ['slug' => $publication->slug]));

        $response->assertOk();
        $response->assertSee('Everlasting Co');
    }
}
