<?php

namespace Tests\Feature\Filament;

use App\Enums\CompanyContentStatus;
use App\Enums\CompanyReviewStatus;
use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Jobs\Ai\GenerateSourceContent;
use App\Models\Company;
use App\Models\CompanyContent;
use App\Models\Plan;
use App\Models\User;
use App\Services\CompanySubscriptionService;
use App\Settings\ContentSettings;
use Database\Seeders\PlanSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CompanyContentPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        $user->givePermissionTo(
            collect(['ViewAny', 'View', 'Update', 'Approve'])
                ->map(fn (string $ability) => Permission::firstOrCreate([
                    'name' => "{$ability}:Company",
                    'guard_name' => 'web',
                ]))
        );

        $this->actingAs($user);
    }

    /**
     * A schema-valid English payload (same shape the pipeline produces).
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function contentPayload(array $overrides = []): array
    {
        // Locale-keyed, exactly like the pipeline stores it.
        return ['en' => array_merge([
            'v' => 1,
            'hero' => [
                'headline' => 'Industrial Insulation Panels Supplier',
                'subheadline' => str_repeat('Reliable export quality. ', 2),
                'image_alt' => str_repeat('Factory view ', 2),
            ],
            'about' => [
                'heading' => 'About This Industrial Company',
                'body' => str_repeat('We manufacture industrial insulation panels for export. ', 15),
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
        ], $overrides)];
    }

    private function makeCompanyWithContent(CompanyContentStatus $status, string $name): Company
    {
        $company = Company::factory()->create(['name' => ['en' => $name, 'fa' => 'شرکت وضعیت']]);

        CompanyContent::firstOrCreate(['company_id' => $company->id])->forceFill([
            'status' => $status,
            'step' => $status === CompanyContentStatus::Ready ? 5 : 1,
        ])->save();

        return $company;
    }

    public function test_the_status_column_shows_the_content_state(): void
    {
        $this->makeCompanyWithContent(CompanyContentStatus::Ready, 'Ready Co');
        Company::factory()->create(['name' => ['en' => 'Empty Co', 'fa' => 'شرکت خالی']]);

        Livewire::test(ListCompanies::class)
            ->assertSee('Ready Co')
            ->assertSee('آماده')
            ->assertSee('Empty Co')
            ->assertSee('بدون محتوا');
    }

    public function test_the_content_status_filter_filters_by_status(): void
    {
        $this->makeCompanyWithContent(CompanyContentStatus::Ready, 'Ready Co');
        Company::factory()->create(['name' => ['en' => 'Empty Co', 'fa' => 'شرکت خالی']]);

        Livewire::test(ListCompanies::class)
            ->set('tableFilters.content_status.value', 'ready')
            ->assertSee('Ready Co')
            ->assertDontSee('Empty Co');

        Livewire::test(ListCompanies::class)
            ->set('tableFilters.content_status.value', 'none')
            ->assertSee('Empty Co')
            ->assertDontSee('Ready Co');
    }

    public function test_editing_a_locales_content_persists_it_and_resets_review_status(): void
    {
        $this->seed(PlanSeeder::class);

        $company = Company::factory()->create([
            'review_status' => CompanyReviewStatus::Approved,
            'reviewed_at' => now(),
        ]);
        $company->forceFill(['content' => $this->contentPayload()])->save();

        Livewire::test(EditCompany::class, ['record' => $company->getRouteKey()])
            ->fillForm(['content' => ['en' => ['hero' => ['headline' => 'Edited Headline For Company']]]])
            ->tap(fn ($component) => fwrite(STDERR, 'DBG-AFTER-FILL: '.json_encode(data_get($component->instance()->data, 'content.en.hero.headline', 'MISSING'))."\n"))
            ->call('save')
            ->tap(fn ($component) => fwrite(STDERR, 'DBG-BEFORE-ASSERT: '.json_encode(data_get($component->instance()->data, 'content.en.hero.headline', 'MISSING'))."\n"))
            ->assertHasNoErrors();

        $company->refresh();

        $this->assertSame('Edited Headline For Company', $company->content['en']['hero']['headline']);
        // The untouched schema version key survives the edit.
        $this->assertSame(1, $company->content['en']['v']);
        // Edited content goes back to pending review.
        $this->assertSame(CompanyReviewStatus::PendingReview, $company->review_status);
        $this->assertNull($company->reviewed_at);
    }

    public function test_saving_content_that_violates_the_schema_is_rejected(): void
    {
        $this->seed(PlanSeeder::class);

        $company = Company::factory()->create([
            'review_status' => CompanyReviewStatus::Approved,
        ]);
        $company->forceFill(['content' => $this->contentPayload()])->save();

        Livewire::test(EditCompany::class, ['record' => $company->getRouteKey()])
            ->fillForm(['content' => ['en' => ['about' => ['body' => 'Too short.']]]])
            ->call('save')
            ->assertHasFormErrors(['content.en.about.body']);

        $company->refresh();

        // Rejected save: content untouched, review status unchanged.
        $this->assertSame(
            'Industrial Insulation Panels Supplier',
            $company->content['en']['hero']['headline'],
        );
        $this->assertSame(CompanyReviewStatus::Approved, $company->review_status);
    }

    public function test_the_pipeline_section_shows_the_quota_usage_for_the_active_plan(): void
    {
        $this->seed(PlanSeeder::class);

        $company = Company::factory()->create();
        $subscription = $company->activeSubscription();
        $subscription->recordFeatureUsage($subscription->plan->slug.'-ai-content-generations');

        // Free plan: 1 monthly generation allowed, 1 used.
        Livewire::test(EditCompany::class, ['record' => $company->getRouteKey()])
            ->assertSee('1 از 1 بار در این ماه');

        $proPlan = Plan::where('slug', 'pro-3-months')->firstOrFail();
        app(CompanySubscriptionService::class)->switchToPlan($company, $proPlan);

        // Switching plans changes the invoice period, which clears usage —
        // the new plan's own limit (3) must show with zero used.
        Livewire::test(EditCompany::class, ['record' => $company->getRouteKey()])
            ->assertSee('0 از 3 بار در این ماه');
    }

    public function test_the_generate_action_queues_the_chain(): void
    {
        app(ContentSettings::class)->fill(['enabled' => true])->save();
        Queue::fake();

        $company = Company::factory()->create();

        Livewire::test(ListCompanies::class)
            ->callAction(TestAction::make('generate_content')->table($company));

        Queue::assertPushed(GenerateSourceContent::class);
    }

    public function test_the_generate_action_is_hidden_without_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'ViewAny:Company', 'guard_name' => 'web']));
        $this->actingAs($user);

        $company = Company::factory()->create();

        Livewire::test(ListCompanies::class)
            ->assertActionHidden(TestAction::make('generate_content')->table($company));
    }
}
