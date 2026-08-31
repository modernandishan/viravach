<?php

namespace Tests\Feature\Ai;

use App\Enums\CompanyContentStatus;
use App\Events\Ai\ContentGenerationProgressed;
use App\Jobs\Ai\FinalizeContent;
use App\Jobs\Ai\GenerateSeoBlock;
use App\Jobs\Ai\GenerateSourceContent;
use App\Models\Company;
use App\Models\CompanyContent;
use App\Models\User;
use App\Services\Ai\ContentGenerationService;
use App\Settings\ContentSettings;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ContentGenerationUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);
        app(ContentSettings::class)->fill(['enabled' => true])->save();
        RateLimiter::clear('ai-content:999999');
    }

    private function enableSettings(): void
    {
        app(ContentSettings::class)->fill(['enabled' => true])->save();
    }

    private function pageFor(Company $company): Testable
    {
        return Livewire::actingAs($company->user)
            ->test('pages::dashboard.edit-company', ['company' => $company->id]);
    }

    public function test_the_button_queues_a_job_and_records_feature_usage(): void
    {
        $this->enableSettings();
        Queue::fake();

        $company = Company::factory()->create();

        $this->pageFor($company)->call('requestContentGeneration');

        Queue::assertPushed(GenerateSourceContent::class);

        $featureSlug = $company->activeSubscription()->plan->slug.'-ai-content-generations';
        $this->assertSame(1, $company->activeSubscription()->getFeatureUsage($featureSlug));
    }

    public function test_a_rejected_request_does_not_record_usage(): void
    {
        // Disabled feature: request() rejects without queuing.
        app(ContentSettings::class)->fill(['enabled' => false])->save();
        Queue::fake();

        $company = Company::factory()->create();

        $this->pageFor($company)->call('requestContentGeneration');

        Queue::assertNothingPushed();

        $featureSlug = $company->activeSubscription()->plan->slug.'-ai-content-generations';
        $this->assertSame(0, $company->activeSubscription()->getFeatureUsage($featureSlug));
    }

    public function test_a_user_out_of_quota_sees_no_button_and_no_request_is_made(): void
    {
        $this->enableSettings();
        Queue::fake();

        $company = Company::factory()->create();

        // Burn the Free plan's single monthly generation.
        $company->activeSubscription()->recordFeatureUsage($company->activeSubscription()->plan->slug.'-ai-content-generations');

        $this->pageFor($company)
            ->assertSee(__('companies.content_quota_exhausted'))
            ->assertDontSee(__('companies.content_generate'))
            ->call('requestContentGeneration');

        Queue::assertNothingPushed();
    }

    public function test_the_rate_limiter_blocks_a_second_request_within_the_window(): void
    {
        $this->enableSettings();
        Queue::fake();

        $company = Company::factory()->create();

        // Pro plan gives 3 generations: quota is NOT the blocker here.
        $company->activeSubscription()->plan->features()->updateOrCreate(
            ['slug' => $company->activeSubscription()->plan->slug.'-ai-content-generations'],
            ['name' => ['en' => 'AI generations'], 'value' => '3', 'resettable_period' => 1, 'resettable_interval' => 'month'],
        );

        $page = $this->pageFor($company);

        $page->call('requestContentGeneration');
        $page->call('requestContentGeneration');

        Queue::assertPushed(GenerateSourceContent::class, 1);
    }

    public function test_the_content_card_shows_progress_while_generating(): void
    {
        $company = Company::factory()->create();

        CompanyContent::firstOrCreate(['company_id' => $company->id])->forceFill([
            'status' => CompanyContentStatus::Generating,
            'step' => 2,
        ])->save();

        $this->pageFor($company)
            ->assertSee(__('companies.content_step_of', ['step' => 2, 'label' => __('companies.ai_step_2')]))
            ->assertDontSee(__('companies.content_generate'));
    }

    public function test_the_content_card_shows_step_four_while_the_locale_batch_runs(): void
    {
        $company = Company::factory()->create();

        CompanyContent::firstOrCreate(['company_id' => $company->id])->forceFill([
            'status' => CompanyContentStatus::Generating,
            'step' => 4,
        ])->save();

        $this->pageFor($company)
            ->assertSee(__('companies.content_step_of', ['step' => 4, 'label' => __('companies.ai_step_4')]))
            ->assertDontSee(__('companies.content_generate'));
    }

    public function test_the_event_is_dispatched_on_claim_with_queued_and_step_zero(): void
    {
        $this->enableSettings();
        Event::fake([ContentGenerationProgressed::class]);
        Queue::fake();

        $company = Company::factory()->create();

        app(ContentGenerationService::class)->request($company);

        Event::assertDispatched(
            ContentGenerationProgressed::class,
            fn (ContentGenerationProgressed $event): bool => $event->companyId === $company->id
                && $event->status === 'queued'
                && $event->step === 0
        );
    }

    public function test_the_event_is_dispatched_on_each_step_and_on_ready(): void
    {
        $company = Company::factory()->create();

        CompanyContent::firstOrCreate(['company_id' => $company->id]);
        $content = $company->contentRecord;
        $content->forceFill(['ai_payload' => ['en' => ['hero' => ['headline' => 'H', 'subheadline' => 'S']]]])->save();

        // Each call into ContentGenerator must get a schema-valid response,
        // otherwise the step throws before advanceStep finishes and the
        // later steps never run. Call order: content, then SEO.
        app(ContentSettings::class)->fill(['base_url' => 'https://ai.example/v1', 'api_key' => 'k'])->save();

        Http::fake(['https://ai.example/*' => Http::sequence([
            $this->chatResponse($this->validContentPayload()),
            $this->chatResponse($this->validSeoPayload()),
        ])]);

        Event::fake([ContentGenerationProgressed::class]);

        (new GenerateSourceContent($company->id))->handle();
        (new GenerateSeoBlock($company->id))->handle();
        (new FinalizeContent($company->id))->handle();

        Event::assertDispatched(ContentGenerationProgressed::class, fn (ContentGenerationProgressed $event): bool => $event->step === 1 && $event->status === 'generating');
        Event::assertDispatched(ContentGenerationProgressed::class, fn (ContentGenerationProgressed $event): bool => $event->step === 2 && $event->status === 'generating');
        Event::assertDispatched(ContentGenerationProgressed::class, fn (ContentGenerationProgressed $event): bool => $event->step === 5 && $event->status === 'ready');
    }

    /**
     * A payload that passes CompanySeoSchema::validate().
     *
     * @return array<string, mixed>
     */
    private function validSeoPayload(): array
    {
        return [
            'meta_title' => str_repeat('t', 35),
            'meta_description' => str_repeat('d', 130),
            'focus_keyword' => 'industrial insulation',
            'keyword_candidates' => ['industrial insulation', 'insulation panels', 'thermal insulation'],
            'meta_keywords' => ['insulation supplier', 'export panels', 'thermal boards'],
        ];
    }

    /**
     * Wrap a payload as a chat-completions response body.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function chatResponse(array $payload): array
    {
        return [
            'choices' => [['message' => ['content' => json_encode($payload, JSON_UNESCAPED_UNICODE)]]],
            'usage' => ['total_tokens' => 10],
        ];
    }

    /**
     * A payload that passes CompanyContentSchema::validate() (same shape
     * the pipeline tests use).
     *
     * @return array<string, mixed>
     */
    private function validContentPayload(): array
    {
        $body = str_repeat('We manufacture industrial insulation panels for export markets. ', 14);

        return [
            'v' => 1,
            'hero' => [
                'headline' => 'Industrial Insulation Panels Supplier',
                'subheadline' => str_repeat('Reliable export quality. ', 3),
                'image_alt' => str_repeat('Factory view ', 3),
            ],
            'about' => [
                'heading' => 'About This Industrial Company',
                'body' => $body,
            ],
            'offerings' => [
                ['title' => 'Insulation Panels', 'body' => str_repeat('Export grade panels. ', 12)],
                ['title' => 'Thermal Boards', 'body' => str_repeat('Boards in many thicknesses. ', 12)],
                ['title' => 'Custom Fabrication', 'body' => str_repeat('Custom orders shipped worldwide. ', 12)],
            ],
            'strengths' => [
                ['title' => 'Export Experience', 'body' => str_repeat('Decades of export operations. ', 4)],
                ['title' => 'Quality Control', 'body' => str_repeat('Every batch is pressure tested. ', 4)],
                ['title' => 'Fast Logistics', 'body' => str_repeat('Containers leave weekly. ', 4)],
            ],
            'markets' => [
                'heading' => 'Export Markets',
                'body' => str_repeat('Active buyers across several regions. ', 6),
                'countries' => [],
            ],
            'specs' => [
                ['label' => 'Thickness', 'value' => '50 mm'],
            ],
            'faq' => [
                ['q' => 'What is the minimum order?', 'a' => str_repeat('One full container per order. ', 4)],
                ['q' => 'Do you ship worldwide?', 'a' => str_repeat('We ship to most major ports. ', 4)],
                ['q' => 'What is the lead time?', 'a' => str_repeat('Usually four to six weeks. ', 4)],
                ['q' => 'Are samples available?', 'a' => str_repeat('Samples ship within one week. ', 4)],
            ],
            'cta' => [
                'heading' => 'Request a Quote Today',
                'body' => str_repeat('Contact our export desk. ', 2),
            ],
        ];
    }

    public function test_the_event_is_dispatched_on_failure_with_the_reason(): void
    {
        $company = Company::factory()->create();

        CompanyContent::firstOrCreate(['company_id' => $company->id]);
        $content = $company->contentRecord;

        Event::fake([ContentGenerationProgressed::class]);

        app(ContentGenerationService::class)->markFailed($content, 'gateway exploded');

        Event::assertDispatched(
            ContentGenerationProgressed::class,
            fn (ContentGenerationProgressed $event): bool => $event->status === 'failed'
                && $event->failureReason === 'gateway exploded'
        );
    }

    public function test_the_event_broadcasts_on_the_company_channel_with_the_expected_payload(): void
    {
        $company = Company::factory()->create();

        CompanyContent::firstOrCreate(['company_id' => $company->id])->forceFill([
            'status' => CompanyContentStatus::Generating,
            'step' => 2,
        ])->save();

        Event::fake([ContentGenerationProgressed::class]);

        ContentGenerationProgressed::dispatch($company->contentRecord);

        Event::assertDispatched(
            ContentGenerationProgressed::class,
            fn (ContentGenerationProgressed $event): bool => $event->broadcastOn()->name === 'private-'.ContentGenerationProgressed::channelNameFor($company->id)
                && $event->broadcastWith() === [
                    'company_id' => $company->id,
                    'status' => 'generating',
                    'step' => 2,
                    'failure_reason' => null,
                ]
        );
    }

    public function test_a_non_owner_cannot_authorize_the_channel_but_the_owner_can(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => '12345',
            'broadcasting.connections.reverb.options' => [
                'host' => 'localhost',
                'port' => 8080,
                'scheme' => 'http',
                'useTLS' => false,
            ],
        ]);

        require base_path('routes/channels.php');

        $company = Company::factory()->create();
        $channel = 'private-'.ContentGenerationProgressed::channelNameFor($company->id);

        $intruder = User::factory()->create();
        $this->actingAs($intruder)
            ->postJson('/broadcasting/auth', ['channel_name' => $channel, 'socket_id' => '123.456'])
            ->assertForbidden();

        $this->actingAs($company->user)
            ->postJson('/broadcasting/auth', ['channel_name' => $channel, 'socket_id' => '123.456'])
            ->assertOk();
    }

    public function test_the_poll_attribute_is_present_while_generating_and_absent_when_ready(): void
    {
        $company = Company::factory()->create();

        CompanyContent::firstOrCreate(['company_id' => $company->id])->forceFill([
            'status' => CompanyContentStatus::Generating,
            'step' => 1,
        ])->save();

        $this->pageFor($company)->assertSee('wire:poll.10s');

        $company->contentRecord->forceFill(['status' => CompanyContentStatus::Ready, 'step' => 5])->save();

        $this->pageFor($company)->assertDontSee('wire:poll.10s');
    }
}
