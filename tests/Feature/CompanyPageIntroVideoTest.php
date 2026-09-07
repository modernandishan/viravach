<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyPublication;
use App\Models\Plan;
use App\Services\CompanySubscriptionService;
use App\Support\PlanFeature;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The public page gates the intro video on the company's CURRENT plan rather
 * than on whether the file exists: a lapsed subscription hides both the player
 * and the VideoObject JSON-LD while leaving the media in place, so
 * resubscribing brings the same file back with no admin action and no
 * re-upload. Covers loadIntroVideo() in pages/⚡company.blade.php.
 *
 * Free carries no `intro-video` feature; Pro and Pro Plus set it to 'true'
 * (PlanSeeder::proFeatureValues()).
 */
class CompanyPageIntroVideoTest extends TestCase
{
    use RefreshDatabase;

    /** The player element and the JSON-LD type, both emitted only when the plan allows it. */
    private const PLAYER_MARKUP = '<media-player';

    private const VIDEO_JSON_LD = 'VideoObject';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);
        Storage::fake('s3');
    }

    /**
     * A live snapshot carrying an intro video, owned by a company on $planSlug.
     *
     * The video is attached straight to the publication rather than copied
     * through CompanyPublicationService: what is under test is the rendering
     * gate, and CompanyPublicationServiceTest already covers the copy.
     */
    private function publishedCompanyOnPlan(string $planSlug): CompanyPublication
    {
        $company = Company::factory()->create();

        $this->switchToPlan($company, $planSlug);

        $publication = CompanyPublication::factory()->for($company)->create();

        $publication->addMedia(UploadedFile::fake()->create('intro.mp4', 100, 'video/mp4'))
            ->toMediaCollection('intro_video', 's3');

        return $publication;
    }

    private function switchToPlan(Company $company, string $planSlug): void
    {
        app(CompanySubscriptionService::class)->switchToPlan(
            $company,
            Plan::where('slug', $planSlug)->firstOrFail(),
        );
    }

    private function showPage(CompanyPublication $publication): TestResponse
    {
        return $this->get(route('companies.show', ['slug' => $publication->slug]));
    }

    public function test_the_video_is_hidden_when_the_plan_does_not_grant_the_feature(): void
    {
        $publication = $this->publishedCompanyOnPlan('free');

        $response = $this->showPage($publication);

        $response->assertOk();
        $response->assertDontSee(self::PLAYER_MARKUP, false);
        $response->assertDontSee(self::VIDEO_JSON_LD, false);
    }

    public function test_the_video_is_shown_when_the_plan_grants_the_feature(): void
    {
        $publication = $this->publishedCompanyOnPlan('pro-3-months');

        $response = $this->showPage($publication);

        $response->assertOk();
        $response->assertSee(self::PLAYER_MARKUP, false);
        $response->assertSee(self::VIDEO_JSON_LD, false);
    }

    public function test_the_video_reappears_after_resubscribing_to_an_eligible_plan(): void
    {
        $publication = $this->publishedCompanyOnPlan('free');

        // Two page renders in one test are safe in THIS direction only: the
        // first emits no video, so nothing leaks into the SEOTools json-ld
        // singleton that the second request would inherit. Reverse the order
        // (see, then assertDontSee) and it breaks — see the force-delete test.
        $this->showPage($publication)->assertDontSee(self::PLAYER_MARKUP, false);

        // The only thing that changes is the plan — no re-upload, no admin
        // action, nothing touched on the snapshot.
        $this->switchToPlan($publication->company, 'pro-plus-3-months');

        $response = $this->showPage($publication);

        $response->assertOk();
        $response->assertSee(self::PLAYER_MARKUP, false);
        $response->assertSee(self::VIDEO_JSON_LD, false);
    }

    public function test_the_file_is_kept_on_s3_while_the_plan_hides_the_video(): void
    {
        $publication = $this->publishedCompanyOnPlan('free');

        $this->showPage($publication)->assertDontSee(self::PLAYER_MARKUP, false);

        // Display-only gating: the row and the object both survive being
        // hidden, which is what makes the resubscribe case above possible.
        $media = $publication->getFirstMedia('intro_video');

        $this->assertNotNull($media);
        $this->assertSame('s3', $media->disk);
        Storage::disk('s3')->assertExists($media->getPathRelativeToRoot());
    }

    /**
     * Regression: PlanFeature::value() takes a non-nullable Company, and
     * force-deleting a company nulls the publication's company_id (the
     * snapshot outlives it by design). Reading the plan without a null check
     * would turn every such page into a 500.
     *
     * Deliberately renders the page ONCE, after the delete. SEOTools binds
     * seotools.json-ld-multi as a container singleton, and a feature test
     * reuses one application instance across every $this->get() call, so a
     * VideoObject emitted by an earlier request in the same test survives into
     * the next one and defeats assertDontSee — a purely in-process artifact
     * (production serves each request in a fresh process). The pre-delete
     * entitlement is asserted against PlanFeature directly instead, and
     * test_the_video_is_shown_when_the_plan_grants_the_feature already covers
     * the rendered-while-entitled half.
     */
    public function test_a_publication_whose_company_was_force_deleted_still_renders_without_the_video(): void
    {
        $publication = $this->publishedCompanyOnPlan('pro-3-months');

        // Precondition: this company WAS entitled, so anything hidden below is
        // the missing company talking, not a plan that never granted it.
        $this->assertSame('true', PlanFeature::value($publication->company, 'intro-video'));

        $publication->company->forceDelete();

        $publication->refresh();
        $this->assertNull($publication->company_id);

        $response = $this->showPage($publication);

        $response->assertOk();
        $response->assertDontSee(self::PLAYER_MARKUP, false);
        $response->assertDontSee(self::VIDEO_JSON_LD, false);
    }
}
