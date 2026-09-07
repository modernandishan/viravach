<?php

namespace Tests\Feature;

use App\Enums\CompanyReviewStatus;
use App\Models\Company;
use App\Models\CompanyPublication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Company::scopePendingRepublish() backs both the admin list's
 * "در انتظار انتشار مجدد" tab and the sidebar navigation badge, so the four
 * combinations of review status and publication presence are pinned here.
 */
class PendingRepublishTest extends TestCase
{
    use RefreshDatabase;

    private function publish(Company $company): CompanyPublication
    {
        return CompanyPublication::create([
            'company_id' => $company->id,
            'slug' => $company->slug,
            'name' => $company->getTranslations('name'),
            'published_at' => now(),
        ]);
    }

    public function test_a_published_company_with_draft_edits_is_pending_republish(): void
    {
        $company = Company::factory()->create(['review_status' => CompanyReviewStatus::PendingReview]);
        $this->publish($company);

        $this->assertTrue(
            Company::query()->pendingRepublish()->whereKey($company->id)->exists(),
        );
    }

    public function test_a_company_that_was_never_approved_is_not_pending_republish(): void
    {
        // Same PendingReview status, but nothing is live yet — this is the
        // first-approval queue, not the republish queue.
        $company = Company::factory()->create(['review_status' => CompanyReviewStatus::PendingReview]);

        $this->assertFalse(
            Company::query()->pendingRepublish()->whereKey($company->id)->exists(),
        );
    }

    public function test_a_published_company_in_sync_is_not_pending_republish(): void
    {
        $company = Company::factory()->approved()->create();
        $this->publish($company);

        $this->assertFalse(
            Company::query()->pendingRepublish()->whereKey($company->id)->exists(),
        );
    }

    public function test_an_approved_company_without_a_publication_is_not_pending_republish(): void
    {
        $company = Company::factory()->approved()->create();

        $this->assertFalse(
            Company::query()->pendingRepublish()->whereKey($company->id)->exists(),
        );
    }

    public function test_the_scope_counts_only_the_matching_companies(): void
    {
        $pending = Company::factory()->create(['review_status' => CompanyReviewStatus::PendingReview]);
        $this->publish($pending);

        $inSync = Company::factory()->approved()->create();
        $this->publish($inSync);

        Company::factory()->create(['review_status' => CompanyReviewStatus::PendingReview]);

        $this->assertSame(1, Company::query()->pendingRepublish()->count());
    }

    public function test_soft_deleted_companies_are_excluded(): void
    {
        $company = Company::factory()->create(['review_status' => CompanyReviewStatus::PendingReview]);
        $this->publish($company);
        $company->delete();

        $this->assertSame(0, Company::query()->pendingRepublish()->count());
    }
}
