<?php

namespace App\Support;

use App\Enums\WordPressPostStatus;
use App\Models\Company;
use App\Models\WordPressContentPost;
use Illuminate\Support\Carbon;

/**
 * The monthly allowance for AI-generated WordPress articles.
 *
 * The limit is never stored against a company: it is read live from the
 * plan feature at the moment of each check (see PlanFeature), and usage is a
 * plain count of this month's rows. So raising a plan's number in the admin
 * takes effect immediately, for every company on that plan, including ones
 * that have already generated this month.
 *
 * The package's own recordFeatureUsage() counter is deliberately not used
 * here: its reset window is anchored to the subscription's creation date,
 * which is a rolling period rather than the calendar month this feature is
 * specified in.
 */
class WordPressContentQuota
{
    public const FEATURE = 'virawp-monthly-contents';

    public function __construct(private readonly Company $company) {}

    public static function for(Company $company): self
    {
        return new self($company);
    }

    /** Articles allowed this month; 0 when the plan grants none. */
    public function limit(): int
    {
        return PlanFeature::intValue($this->company, self::FEATURE) ?? 0;
    }

    /**
     * Articles already spent this calendar month. Failed generations are
     * excluded: a run that broke on a technical fault must not cost the
     * owner an article, matching how the company-profile generator refuses
     * to burn quota on a rejection.
     */
    public function used(): int
    {
        return WordPressContentPost::query()
            ->where('company_id', $this->company->id)
            ->where('status', '!=', WordPressPostStatus::Failed)
            ->whereBetween('created_at', [$this->periodStart(), $this->periodEnd()])
            ->count();
    }

    public function remaining(): int
    {
        return max(0, $this->limit() - $this->used());
    }

    public function canGenerate(): bool
    {
        return $this->remaining() > 0;
    }

    /** When the allowance next resets, for the "come back on" message. */
    public function resetsAt(): Carbon
    {
        return $this->periodEnd()->addSecond();
    }

    private function periodStart(): Carbon
    {
        return now()->startOfMonth();
    }

    private function periodEnd(): Carbon
    {
        return now()->endOfMonth();
    }
}
