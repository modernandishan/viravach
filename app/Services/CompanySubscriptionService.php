<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use Laravelcm\Subscriptions\Services\Period;

class CompanySubscriptionService
{
    /**
     * Switch (or start) the company's subscription to the given plan,
     * billed from now.
     *
     * The underlying package's Subscription::changePlan() does NOT end the
     * old subscription and create a new row — it mutates the SAME
     * subscription record in place (new plan_id, and a new billing period
     * only if the plan's invoice_interval/invoice_period differ from the
     * previous plan's). A company therefore has at most one ACTIVE
     * subscription row at a time; "switching plans" and "having a
     * subscription" are the same underlying record, as long as the
     * previous one was still active when switched.
     */
    public function switchToPlan(Company $company, Plan $plan): CompanySubscription
    {
        $active = $company->activeSubscription();

        $subscription = $active
            ? $active->changePlan($plan)
            : $company->newPlanSubscription('main', $plan);

        $period = new Period(
            interval: $plan->invoice_interval,
            count: $plan->invoice_period,
            start: now(),
        );

        $subscription->forceFill([
            'is_trial' => false,
            'trial_ends_at' => null,
            'starts_at' => $period->getStartDate(),
            'ends_at' => $plan->isFree() ? null : $period->getEndDate(),
        ])->save();

        // Company::activeSubscription() reads the lazily-loaded and cached
        // `planSubscriptions` relation. If a caller already resolved it
        // (e.g. the `$active` lookup above, when it was still empty)
        // before this method created the first subscription, that stale
        // empty collection would linger on the instance and fool a
        // subsequent activeSubscription() call into creating a second row
        // instead of switching the one just created. Drop the cache so the
        // next read is fresh.
        $company->unsetRelation('planSubscriptions');

        return $subscription;
    }

    /**
     * Safety net for the "every company always has an active subscription"
     * invariant. Does nothing if the company already has one (whichever
     * plan that is) — callers that explicitly chose a plan must not have it
     * silently overridden.
     */
    public function assignFreePlanIfMissing(Company $company): void
    {
        if ($company->activeSubscription() !== null) {
            return;
        }

        $freePlan = Plan::where('slug', 'free')->first();

        // No Free plan configured — nothing to assign. Every real
        // environment seeds it via PlanSeeder (called from
        // DatabaseSeeder), so this only happens on a database that hasn't
        // been seeded yet (e.g. tests unrelated to billing).
        if ($freePlan === null) {
            return;
        }

        $this->switchToPlan($company, $freePlan);
    }

    /**
     * Grant the one-time, 14-day Pro Plus trial to $company.
     *
     * The trial's 14-day window is independent of any Plan's own
     * invoice_period (those are fixed 3/6/12-month billing cycles) — a
     * reference Pro Plus plan is used only for its name/features, and the
     * subscription's actual dates are force-set afterwards.
     */
    public function startProPlusTrial(Company $company): CompanySubscription
    {
        $user = $company->user;

        abort_if($user->hasUsedTrial(), 403, __('subscriptions.trial_already_used'));

        $activePlan = $company->activeSubscription()?->plan;
        abort_if($activePlan !== null && ! $activePlan->isFree(), 403, __('subscriptions.trial_requires_free_plan'));

        $trialPlan = Plan::where('slug', 'pro-plus-3-months')->firstOrFail();

        return DB::transaction(function () use ($company, $user, $trialPlan): CompanySubscription {
            $active = $company->activeSubscription();

            /** @var CompanySubscription $subscription */
            $subscription = $active
                ? $active->changePlan($trialPlan)
                : $company->newPlanSubscription('main', $trialPlan);

            $subscription->forceFill([
                'is_trial' => true,
                'trial_ends_at' => null,
                'starts_at' => now(),
                'ends_at' => now()->addDays(14),
            ])->save();

            $company->unsetRelation('planSubscriptions');

            $user->forceFill(['trial_used_at' => now()])->save();

            return $subscription;
        });
    }

    /**
     * Revert every Pro Plus trial subscription whose 14 days have elapsed
     * back to the Free plan. Matches on the `is_trial` marker rather than
     * inferring from `ends_at` alone, since a normal (paid) Pro Plus
     * purchase could also happen to be near expiry.
     *
     * Clears `is_trial` on the matched row regardless of what happens
     * next, so a historical trial row is never re-matched by tomorrow's
     * run — without this, a company that later purchases a real paid plan
     * would otherwise be yanked back to Free by every subsequent daily
     * run.
     *
     * A subscription's own `ends_at` passing does not by itself make it
     * the company's current state: the owner may have purchased a real
     * plan since (a fresh, still-active subscription row — see
     * switchToPlan()'s "same row" doc comment for why that is a new row,
     * not the expired trial's). Only revert to Free when the company has
     * no active subscription at all, i.e. nothing has superseded the
     * expired trial.
     *
     * @return int number of companies actually reverted to Free
     */
    public function revertExpiredTrials(): int
    {
        $freePlan = Plan::where('slug', 'free')->first();

        if ($freePlan === null) {
            return 0;
        }

        $expiredTrials = CompanySubscription::query()
            ->where('is_trial', true)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->get();

        $reverted = 0;

        foreach ($expiredTrials as $subscription) {
            $subscription->forceFill(['is_trial' => false])->save();

            $company = $subscription->subscriber;

            if (! $company instanceof Company) {
                continue;
            }

            if ($company->activeSubscription() !== null) {
                continue;
            }

            $this->switchToPlan($company, $freePlan);

            $reverted++;
        }

        return $reverted;
    }
}
