<?php

namespace Tests\Feature\Dashboard;

use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use App\Services\CompanySubscriptionService;
use App\Support\VideoMetadata;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The intro video is a plan-gated company asset: eligible plans (Pro, Pro
 * Plus — Free has no `intro-video` feature) may store exactly one video
 * (max 256MB, max 10 minutes) on the s3 disk. No public rendering exists;
 * this suite covers eligibility, the validation chain and storage only.
 */
class SettingsIntroVideoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);
        Storage::fake('s3');
    }

    protected function onPlan(string $slug): Company
    {
        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();

        app(CompanySubscriptionService::class)->switchToPlan(
            $company,
            Plan::where('slug', $slug)->firstOrFail(),
        );

        return $company;
    }

    protected function fakeVideo(int $kilobytes = 100): UploadedFile
    {
        return UploadedFile::fake()->create('intro.mp4', $kilobytes, 'video/mp4');
    }

    public function test_a_free_plan_company_sees_the_upsell_and_cannot_upload(): void
    {
        $company = $this->onPlan('free');

        Livewire::actingAs($company->user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->assertSee(__('settings.intro_video_upsell'))
            ->assertSee(__('settings.intro_video_upsell_cta'))
            ->set('introVideo', $this->fakeVideo())
            ->call('saveIntroVideo')
            ->assertHasErrors(['introVideo']);

        $this->assertCount(0, $company->getMedia('intro_video'));
    }

    public function test_an_eligible_company_uploads_a_video_to_the_intro_video_collection_on_s3(): void
    {
        Storage::fake('s3');

        $company = $this->onPlan('pro-3-months');
        $this->mockDuration(300.0);

        $video = $this->fakeVideo();

        Livewire::actingAs($company->user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->assertSee(__('settings.intro_video_label'))
            ->set('introVideo', $video)
            ->call('saveIntroVideo')
            ->assertHasNoErrors();

        $media = $company->getFirstMedia('intro_video');

        $this->assertNotNull($media);
        $this->assertSame('s3', $media->disk);
        $this->assertSame('intro.mp4', $media->file_name);
        Storage::disk('s3')->assertExists($media->getPathRelativeToRoot());
    }

    public function test_uploading_again_replaces_the_single_video(): void
    {
        $company = $this->onPlan('pro-plus-3-months');
        $this->mockDuration(120.0);

        $component = Livewire::actingAs($company->user)
            ->test('pages::dashboard.settings', ['company' => $company]);

        $component->set('introVideo', $this->fakeVideo())->call('saveIntroVideo')->assertHasNoErrors();
        $component->set('introVideo', $this->fakeVideo())->call('saveIntroVideo')->assertHasNoErrors();

        $this->assertCount(1, $company->getMedia('intro_video'));
    }

    public function test_a_non_video_file_is_rejected(): void
    {
        $company = $this->onPlan('pro-3-months');

        Livewire::actingAs($company->user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->set('introVideo', UploadedFile::fake()->create('notes.txt', 10, 'text/plain'))
            ->call('saveIntroVideo')
            ->assertHasErrors(['introVideo']);

        $this->assertCount(0, $company->getMedia('intro_video'));
    }

    public function test_a_video_longer_than_ten_minutes_is_rejected(): void
    {
        $company = $this->onPlan('pro-3-months');
        $this->mockDuration(601.0);

        Livewire::actingAs($company->user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->set('introVideo', $this->fakeVideo())
            ->call('saveIntroVideo')
            ->assertHasErrors(['introVideo']);

        $this->assertCount(0, $company->getMedia('intro_video'));
    }

    public function test_unparsable_video_metadata_is_rejected(): void
    {
        $company = $this->onPlan('pro-3-months');
        $this->mockDuration(null);

        Livewire::actingAs($company->user)
            ->test('pages::dashboard.settings', ['company' => $company])
            ->set('introVideo', $this->fakeVideo())
            ->call('saveIntroVideo')
            ->assertHasErrors(['introVideo']);

        $this->assertCount(0, $company->getMedia('intro_video'));
    }

    public function test_the_video_can_be_removed(): void
    {
        $company = $this->onPlan('pro-3-months');
        $this->mockDuration(300.0);

        $component = Livewire::actingAs($company->user)
            ->test('pages::dashboard.settings', ['company' => $company]);

        $component->set('introVideo', $this->fakeVideo())->call('saveIntroVideo')->assertHasNoErrors();

        $media = $company->getFirstMedia('intro_video');

        $component->call('removeIntroVideo')->assertHasNoErrors();

        // The test's $company instance cached its media relation when we
        // read it above; refresh so the deletion is visible.
        $this->assertCount(0, $company->refresh()->getMedia('intro_video'));
        Storage::disk('s3')->assertMissing($media->getPathRelativeToRoot());
    }

    protected function mockDuration(?float $seconds): void
    {
        $metadata = $this->mock(VideoMetadata::class);
        $metadata->shouldReceive('durationSeconds')->andReturn($seconds);
    }
}
