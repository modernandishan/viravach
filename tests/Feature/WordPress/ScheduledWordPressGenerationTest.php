<?php

namespace Tests\Feature\WordPress;

use App\Enums\ContentGenerationMode;
use App\Enums\SeoPlugin;
use App\Enums\WordPressConnectionStatus;
use App\Jobs\WordPress\GenerateWordPressPostContent;
use App\Jobs\WordPress\GenerateWordPressPostImage;
use App\Jobs\WordPress\PublishWordPressPost;
use App\Models\Company;
use App\Models\User;
use App\Models\WordPressContentPost;
use App\Services\WordPress\WordPressContentGenerationFailureReason;
use App\Services\WordPress\WordPressContentGenerationResult;
use App\Services\WordPress\WordPressContentGenerationService;
use App\Settings\ContentSettings;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * The scheduler is the only regular trigger for WordPress articles: for
 * each connected company with complete settings, one attempt every 3 days
 * until the monthly quota is spent. Every gate is proven here.
 */
class ScheduledWordPressGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);
        app(ContentSettings::class)->fill(['enabled' => true])->save();
    }

    /**
     * A company the scheduler should pick: connected, complete settings,
     * on the Free plan (one article a month), no prior attempts.
     */
    protected function automaticCompany(?User $user = null, array $overrides = []): Company
    {
        return Company::factory()->for($user ?? User::factory()->create())->create([
            'wp_connection_status' => WordPressConnectionStatus::Connected,
            'seo_plugin' => SeoPlugin::Yoast,
            'content_generation_mode' => ContentGenerationMode::Industry,
            'content_language' => 'en',
            ...$overrides,
        ]);
    }

    protected function runScheduler(): string
    {
        Artisan::call('app:generate-scheduled-wordpress-content');

        return Artisan::output();
    }

    public function test_an_eligible_company_gets_one_article_queued_with_its_persisted_settings(): void
    {
        Bus::fake();

        $company = $this->automaticCompany();

        $this->runScheduler();

        $post = $company->wordPressContentPosts()->sole();

        $this->assertSame('en', $post->locale);
        $this->assertSame(ContentGenerationMode::Industry, $post->mode);
        Bus::assertChained([
            GenerateWordPressPostContent::class,
            GenerateWordPressPostImage::class,
            PublishWordPressPost::class,
        ]);
    }

    public function test_an_unconnected_company_is_skipped_silently(): void
    {
        Bus::fake();

        $this->automaticCompany(overrides: [
            'wp_connection_status' => WordPressConnectionStatus::NotTested,
        ]);

        $output = $this->runScheduler();

        $this->assertSame(0, WordPressContentPost::count());
        $this->assertStringContainsString('0 queued', $output);
    }

    public function test_a_company_with_incomplete_settings_is_skipped(): void
    {
        Bus::fake();

        $this->automaticCompany(overrides: ['seo_plugin' => null]);

        $this->runScheduler();

        $this->assertSame(0, WordPressContentPost::count());
    }

    public function test_a_company_with_a_run_in_flight_is_skipped(): void
    {
        Bus::fake();

        $company = $this->automaticCompany();
        WordPressContentPost::factory()->queued()->create([
            'company_id' => $company->id,
            'created_at' => now()->subDays(5),
        ]);

        $output = $this->runScheduler();

        $this->assertSame(1, $company->wordPressContentPosts()->count());
        $this->assertStringContainsString('0 queued', $output);
    }

    public function test_a_company_that_attempted_less_than_three_days_ago_is_skipped(): void
    {
        Bus::fake();

        $company = $this->automaticCompany();
        WordPressContentPost::factory()->create([
            'company_id' => $company->id,
            'created_at' => now()->subDays(2),
        ]);

        $this->runScheduler();

        $this->assertSame(1, $company->wordPressContentPosts()->count());
        Bus::assertNothingDispatched();
    }

    public function test_a_failed_attempt_still_respects_the_three_day_cadence(): void
    {
        Bus::fake();

        $company = $this->automaticCompany();
        WordPressContentPost::factory()->failed()->create([
            'company_id' => $company->id,
            'created_at' => now()->subDay(),
        ]);

        $this->runScheduler();

        // The failed row stays the only one: no retry inside the window.
        $this->assertSame(1, $company->wordPressContentPosts()->count());
        Bus::assertNothingDispatched();
    }

    /**
     * Regression test mirroring the real production report: a connected
     * company with every setting filled in and a single FAILED attempt
     * older than the 3-day cadence must be selected and queued — not
     * silently dropped by the eligibility query.
     */
    public function test_a_connected_company_with_a_failed_attempt_older_than_the_cadence_is_queued(): void
    {
        Bus::fake();

        $company = $this->automaticCompany();
        WordPressContentPost::factory()->failed()->create([
            'company_id' => $company->id,
            'created_at' => now()->subDays(4),
        ]);

        $output = $this->runScheduler();

        $post = $company->wordPressContentPosts()->where('status', '!=', 'failed')->sole();

        $this->assertSame('en', $post->locale);
        $this->assertSame(ContentGenerationMode::Industry, $post->mode);
        $this->assertStringContainsString('1 queued', $output);
        Bus::assertChained([
            GenerateWordPressPostContent::class,
            GenerateWordPressPostImage::class,
            PublishWordPressPost::class,
        ]);
    }

    public function test_an_exhausted_quota_is_skipped(): void
    {
        Bus::fake();

        $company = $this->automaticCompany();
        WordPressContentPost::factory()->create([
            'company_id' => $company->id,
            'created_at' => now()->subDays(5),
        ]);

        $this->runScheduler();

        $this->assertSame(1, $company->wordPressContentPosts()->count());
        Bus::assertNothingDispatched();
    }

    public function test_a_new_month_re_enables_an_exhausted_company(): void
    {
        Bus::fake();

        $company = $this->automaticCompany();
        WordPressContentPost::factory()->create([
            'company_id' => $company->id,
            'created_at' => now()->subMonthNoOverflow()->subDays(4),
        ]);

        $this->runScheduler();

        // Last month's row is outside the quota window AND outside the
        // 3-day cadence, so this month's first article goes out.
        $this->assertSame(2, $company->wordPressContentPosts()->count());
        Bus::assertChained([
            GenerateWordPressPostContent::class,
            GenerateWordPressPostImage::class,
            PublishWordPressPost::class,
        ]);
    }

    public function test_one_companys_failure_does_not_block_the_rest(): void
    {
        Bus::fake();

        $companyA = $this->automaticCompany();
        $companyB = $this->automaticCompany();

        $service = $this->mock(WordPressContentGenerationService::class);
        $service->shouldReceive('request')
            ->twice()
            ->andReturnUsing(function (Company $company) use ($companyA) {
                if ($company->is($companyA)) {
                    throw new \RuntimeException('trends exploded');
                }

                return WordPressContentGenerationResult::failed(
                    WordPressContentGenerationFailureReason::NoTopicAvailable,
                );
            });

        $output = $this->runScheduler();

        // Company B was still processed after A threw — the loop isolated it
        // (B was reached and answered, rather than never being called).
        $this->assertStringContainsString('1 errored', $output);
        $this->assertStringContainsString('1 skipped', $output);
        $this->assertSame(0, WordPressContentPost::count());
        Bus::assertNothingDispatched();
    }

    public function test_the_global_kill_switch_skips_everything(): void
    {
        Bus::fake();
        app(ContentSettings::class)->fill(['enabled' => false])->save();

        $this->automaticCompany();

        $output = $this->runScheduler();

        $this->assertSame(0, WordPressContentPost::count());
        $this->assertStringContainsString('disabled', $output);
        Bus::assertNothingDispatched();
    }
}
