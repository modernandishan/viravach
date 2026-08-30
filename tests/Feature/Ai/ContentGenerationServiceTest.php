<?php

namespace Tests\Feature\Ai;

use App\Ai\Input\CompanyInputCollector;
use App\Enums\CompanyContentStatus;
use App\Jobs\Ai\GenerateSourceContent;
use App\Models\Company;
use App\Models\CompanyContent;
use App\Services\Ai\ContentGenerationService;
use App\Settings\ContentSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ContentGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function enableSettings(bool $enabled = true): void
    {
        app(ContentSettings::class)->fill(['enabled' => $enabled])->save();
    }

    protected function hashFor(Company $company): string
    {
        $collector = app(CompanyInputCollector::class);

        return $collector->hash($collector->collect($company->fresh()));
    }

    public function test_a_first_request_claims_the_row_and_dispatches_the_chain(): void
    {
        $this->enableSettings();
        Queue::fake();

        $company = Company::factory()->create();
        $hash = $this->hashFor($company);

        $this->assertTrue(app(ContentGenerationService::class)->request($company));

        $content = CompanyContent::query()->where('company_id', $company->id)->firstOrFail();

        $this->assertSame(CompanyContentStatus::Queued, $content->status);
        $this->assertSame($hash, $content->input_hash);
        $this->assertSame(0, $content->step);
        $this->assertNull($content->failure_reason);
        $this->assertNotNull($content->locked_at);

        Queue::assertPushed(GenerateSourceContent::class, fn ($job, $queue) => $queue === 'ai-content');
    }

    public function test_a_second_request_while_queued_dispatches_nothing(): void
    {
        $this->enableSettings();
        Queue::fake();

        $company = Company::factory()->create();
        $service = app(ContentGenerationService::class);

        $this->assertTrue($service->request($company));
        $this->assertFalse($service->request($company));

        Queue::assertPushed(GenerateSourceContent::class, 1);
    }

    public function test_a_request_with_unchanged_hash_and_status_ready_dispatches_nothing(): void
    {
        $this->enableSettings();
        Queue::fake();

        $company = Company::factory()->create();

        CompanyContent::forceCreate([
            'company_id' => $company->id,
            'status' => CompanyContentStatus::Ready,
            'input_hash' => $this->hashFor($company),
            'step' => 5,
        ]);

        $this->assertFalse(app(ContentGenerationService::class)->request($company));

        Queue::assertNothingPushed();
    }

    public function test_a_request_with_a_changed_hash_and_status_ready_does_dispatch(): void
    {
        $this->enableSettings();
        Queue::fake();

        $company = Company::factory()->create();

        CompanyContent::forceCreate([
            'company_id' => $company->id,
            'status' => CompanyContentStatus::Ready,
            'input_hash' => 'stale-hash-from-an-older-brief',
            'step' => 5,
        ]);

        $this->assertTrue(app(ContentGenerationService::class)->request($company));

        $content = CompanyContent::query()->where('company_id', $company->id)->firstOrFail();

        $this->assertSame(CompanyContentStatus::Queued, $content->status);
        $this->assertSame($this->hashFor($company), $content->input_hash);
        Queue::assertPushed(GenerateSourceContent::class, 1);
    }

    public function test_a_failed_row_can_be_re_requested(): void
    {
        $this->enableSettings();
        Queue::fake();

        $company = Company::factory()->create();

        CompanyContent::forceCreate([
            'company_id' => $company->id,
            'status' => CompanyContentStatus::Failed,
            'failure_reason' => 'gateway exploded',
            'step' => 2,
        ]);

        $this->assertTrue(app(ContentGenerationService::class)->request($company));

        $content = CompanyContent::query()->where('company_id', $company->id)->firstOrFail();

        $this->assertSame(CompanyContentStatus::Queued, $content->status);
        $this->assertNull($content->failure_reason);
        $this->assertSame(0, $content->step);
    }

    public function test_disabled_settings_dispatch_nothing(): void
    {
        // enabled defaults to false from the settings migration.
        app(ContentSettings::class)->fill([
            'base_url' => 'https://ai.example/v1',
        ])->save();

        Queue::fake();

        $company = Company::factory()->create();

        $this->assertFalse(app(ContentGenerationService::class)->request($company));

        Queue::assertNothingPushed();
        $this->assertNull(CompanyContent::query()->where('company_id', $company->id)->first());
    }

    public function test_the_sweeper_marks_a_row_stuck_for_20_minutes_as_failed_and_leaves_a_recent_lock_alone(): void
    {
        $company = Company::factory()->create();
        $another = Company::factory()->create();

        $stuck = CompanyContent::forceCreate([
            'company_id' => $company->id,
            'status' => CompanyContentStatus::Generating,
            'locked_at' => now()->subMinutes(20),
            'step' => 3,
        ]);

        $fresh = CompanyContent::forceCreate([
            'company_id' => $another->id,
            'status' => CompanyContentStatus::Generating,
            'locked_at' => now()->subMinutes(5),
            'step' => 1,
        ]);

        $this->artisan('app:sweep-stuck-content-generations')->assertSuccessful();

        $this->assertSame(CompanyContentStatus::Failed, $stuck->fresh()->status);
        $this->assertStringContainsString('stalled', (string) $stuck->fresh()->failure_reason);
        $this->assertNull($stuck->fresh()->locked_at);

        $this->assertSame(CompanyContentStatus::Generating, $fresh->fresh()->status);
        $this->assertNull($fresh->fresh()->failure_reason);
    }
}
