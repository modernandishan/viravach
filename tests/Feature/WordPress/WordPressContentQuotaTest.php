<?php

namespace Tests\Feature\WordPress;

use App\Models\Company;
use App\Models\Plan;
use App\Models\User;
use App\Models\WordPressContentPost;
use App\Services\CompanySubscriptionService;
use App\Support\WordPressContentQuota;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The quota is never stored against the company: it is read live from the
 * plan feature every time. These tests exist to prove exactly that — an
 * admin raising a plan's number must change what a company can already do
 * this month, with nothing to recompute.
 */
class WordPressContentQuotaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);
    }

    protected function onPlan(string $slug): Company
    {
        $company = Company::factory()->create();

        app(CompanySubscriptionService::class)->switchToPlan(
            $company,
            Plan::where('slug', $slug)->firstOrFail(),
        );

        return $company->fresh();
    }

    public function test_free_plan_allows_one_article_per_month(): void
    {
        $company = $this->onPlan('free');

        $this->assertSame(1, WordPressContentQuota::for($company)->limit());
        $this->assertTrue(WordPressContentQuota::for($company)->canGenerate());
    }

    public function test_pro_and_pro_plus_allow_three_and_ten(): void
    {
        $pro = $this->onPlan('pro-3-months');
        $proPlus = $this->onPlan('pro-plus-3-months');

        $this->assertSame(3, WordPressContentQuota::for($pro)->limit());
        $this->assertSame(10, WordPressContentQuota::for($proPlus)->limit());
    }

    public function test_generating_up_to_the_limit_exhausts_it(): void
    {
        $company = $this->onPlan('free');

        $company->wordPressContentPosts()->save(
            WordPressContentPost::factory()->make(['company_id' => $company->id]),
        );

        $this->assertFalse(WordPressContentQuota::for($company)->canGenerate());
        $this->assertSame(0, WordPressContentQuota::for($company)->remaining());
    }

    public function test_raising_the_plans_feature_value_immediately_allows_another_generation(): void
    {
        $company = $this->onPlan('free');

        $company->wordPressContentPosts()->save(
            WordPressContentPost::factory()->make(['company_id' => $company->id]),
        );

        $this->assertFalse(WordPressContentQuota::for($company)->canGenerate());

        // The admin raises the Free plan's monthly allowance — nothing about
        // the company or its existing posts is touched.
        Plan::where('slug', 'free')->firstOrFail()
            ->features()->where('slug', 'free-virawp-monthly-contents')
            ->update(['value' => '2']);

        $this->assertSame(2, WordPressContentQuota::for($company)->limit());
        $this->assertTrue(WordPressContentQuota::for($company)->canGenerate());
    }

    public function test_failed_generations_do_not_consume_the_quota(): void
    {
        $company = $this->onPlan('free');

        $company->wordPressContentPosts()->save(
            WordPressContentPost::factory()->failed()->make(['company_id' => $company->id]),
        );

        $this->assertSame(0, WordPressContentQuota::for($company)->used());
        $this->assertTrue(WordPressContentQuota::for($company)->canGenerate());
    }

    public function test_usage_is_scoped_to_the_current_calendar_month(): void
    {
        $company = $this->onPlan('free');

        $post = WordPressContentPost::factory()->create(['company_id' => $company->id]);
        $post->forceFill(['created_at' => now()->subMonthNoOverflow()])->save();

        $this->assertSame(0, WordPressContentQuota::for($company)->used());
        $this->assertTrue(WordPressContentQuota::for($company)->canGenerate());
    }

    public function test_usage_is_scoped_per_company(): void
    {
        $companyA = $this->onPlan('free');
        $companyB = $this->onPlan('free');

        WordPressContentPost::factory()->create(['company_id' => $companyA->id]);

        $this->assertSame(1, WordPressContentQuota::for($companyA)->used());
        $this->assertSame(0, WordPressContentQuota::for($companyB)->used());
    }

    public function test_a_company_with_no_active_plan_has_no_allowance(): void
    {
        $company = Company::factory()->make(['user_id' => User::factory()->create()->id]);
        $company->saveQuietly();

        $this->assertSame(0, WordPressContentQuota::for($company)->limit());
        $this->assertFalse(WordPressContentQuota::for($company)->canGenerate());
    }
}
