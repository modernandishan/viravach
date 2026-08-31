<?php

namespace Tests\Feature\Ai;

use App\Enums\CompanyContentStatus;
use App\Jobs\Ai\GenerateFeaturedImage;
use App\Models\Company;
use App\Models\CompanyContent;
use App\Services\CompanyPublicationService;
use App\Settings\ContentSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FeaturedImageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A minimal but genuinely decodable 1x1 PNG, so MediaLibrary's own
     * image handling (and the queued webp conversion, which runs inline
     * under the `sync` queue connection used in tests) has real bytes to
     * work with.
     */
    private const VALID_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');

        app(ContentSettings::class)->fill([
            'enabled' => true,
            // Deliberately WITHOUT /v1 — mirrors the real Open WebUI
            // deployment ('http://open-webui:8080/api'), where chat
            // completions resolve to {base_url}/chat/completions but the
            // images endpoint lives under {base_url}/v1/images/generations.
            'base_url' => 'https://ai.example/api',
            'api_key' => 'secret-key',
            'image_enabled' => true,
            'image_model' => 'image-model',
            'image_size' => '1024x1024',
        ])->save();
    }

    private function fakeImageEndpoint(): void
    {
        Http::fake([
            'https://ai.example/api/v1/images/generations*' => Http::response([
                'data' => [['b64_json' => self::VALID_PNG_BASE64]],
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function localePayload(string $locale): array
    {
        return [
            'v' => 1,
            'hero' => [
                'headline' => "Headline {$locale}",
                'subheadline' => "Subheadline {$locale}",
                'image_alt' => "Alt text {$locale}",
            ],
            'about' => [
                'heading' => "About heading {$locale}",
                'body' => "First paragraph {$locale}.\n\nSecond paragraph {$locale}.",
            ],
            'offerings' => [
                ['title' => "Offering One {$locale}", 'body' => 'x'],
                ['title' => "Offering Two {$locale}", 'body' => 'y'],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function fullContent(): array
    {
        $locales = array_keys((array) config('laravellocalization.supportedLocales'));

        return collect($locales)->mapWithKeys(fn (string $locale): array => [$locale => $this->localePayload($locale)])->all();
    }

    private function makeCompanyWithContent(): Company
    {
        return Company::factory()->create(['content' => $this->fullContent()]);
    }

    public function test_a_successful_run_attaches_one_media_item_to_featured_image(): void
    {
        $this->fakeImageEndpoint();

        $company = $this->makeCompanyWithContent();

        (new GenerateFeaturedImage($company->id))->handle();

        $company->refresh();

        $this->assertCount(1, $company->getMedia('featured_image'));
        $this->assertSame('featured_image', $company->getFirstMedia('featured_image')->collection_name);
        $this->assertSame('s3', $company->getFirstMedia('featured_image')->disk);
    }

    public function test_metadata_is_filled_for_all_five_locales_from_the_existing_payload(): void
    {
        $this->fakeImageEndpoint();

        $company = $this->makeCompanyWithContent();

        (new GenerateFeaturedImage($company->id))->handle();

        $media = $company->fresh()->getFirstMedia('featured_image');
        $locales = array_keys((array) config('laravellocalization.supportedLocales'));

        $this->assertCount(5, $locales);

        foreach ($locales as $locale) {
            $this->assertSame("Headline {$locale}", $media->getCustomProperty('title')[$locale]);
            $this->assertSame("Alt text {$locale}", $media->getCustomProperty('alt')[$locale]);
            $this->assertSame("Subheadline {$locale}", $media->getCustomProperty('caption')[$locale]);
            $this->assertSame("First paragraph {$locale}.", $media->getCustomProperty('description')[$locale]);
        }
    }

    public function test_the_job_is_skipped_when_image_generation_is_disabled(): void
    {
        app(ContentSettings::class)->fill(['image_enabled' => false])->save();
        Http::fake();

        $company = $this->makeCompanyWithContent();

        (new GenerateFeaturedImage($company->id))->handle();

        $this->assertCount(0, $company->fresh()->getMedia('featured_image'));
        Http::assertNothingSent();
    }

    public function test_the_job_is_skipped_when_a_featured_image_already_exists(): void
    {
        Http::fake();

        $company = $this->makeCompanyWithContent();
        $company->addMedia(UploadedFile::fake()->image('existing.png', 10, 10))
            ->toMediaCollection('featured_image', 's3');

        (new GenerateFeaturedImage($company->id))->handle();

        $this->assertCount(1, $company->fresh()->getMedia('featured_image'));
        Http::assertNothingSent();
    }

    public function test_the_job_is_skipped_when_there_is_no_english_payload(): void
    {
        Http::fake();

        $company = Company::factory()->create(['content' => null]);

        (new GenerateFeaturedImage($company->id))->handle();

        $this->assertCount(0, $company->fresh()->getMedia('featured_image'));
        Http::assertNothingSent();
    }

    public function test_a_failing_image_call_does_not_change_the_content_status_away_from_ready(): void
    {
        Http::fake([
            'https://ai.example/api/v1/images/generations*' => Http::response(['error' => 'boom'], 500),
        ]);

        $company = $this->makeCompanyWithContent();
        $content = CompanyContent::forceCreate([
            'company_id' => $company->id,
            'status' => CompanyContentStatus::Ready,
            'step' => 5,
        ]);

        // Dispatch (not a direct ->handle() call) so the queue's own
        // failure machinery invokes failed(). Under the `sync` connection
        // used in tests, SyncQueue::handleException() calls failed() and
        // then rethrows — unlike a real async worker, which never
        // propagates the exception back to whatever dispatched the job.
        // That rethrow is exactly why FinalizeContent dispatches this job
        // strictly AFTER markReady(): the ready status is already
        // persisted before this line ever runs.
        try {
            GenerateFeaturedImage::dispatch($company->id);
        } catch (\Throwable) {
            // Expected under `sync` — see comment above.
        }

        $content->refresh();

        $this->assertSame(CompanyContentStatus::Ready, $content->status);
        $this->assertStringContainsString('Featured image generation failed', (string) $content->failure_reason);
        $this->assertCount(0, $company->fresh()->getMedia('featured_image'));
    }

    public function test_the_publication_carries_the_image_after_publishing(): void
    {
        $this->fakeImageEndpoint();

        $company = $this->makeCompanyWithContent();
        (new GenerateFeaturedImage($company->id))->handle();

        $publication = app(CompanyPublicationService::class)->publish($company->fresh());

        $this->assertCount(1, $publication->getMedia('featured_image'));
        $this->assertSame(
            'Alt text en',
            $publication->getFirstMedia('featured_image')->getCustomProperty('alt')['en'],
        );
    }
}
