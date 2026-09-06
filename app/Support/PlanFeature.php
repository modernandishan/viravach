<?php

namespace App\Support;

use App\Models\Company;

/**
 * Reads a plan feature's CURRENT value for a company.
 *
 * Feature slugs are stored plan-prefixed — PlanSeeder::seedFeatures() writes
 * `{plan-slug}-{key}` because the package's features table has a globally
 * unique slug index — so every caller has to rebuild the slug before it can
 * look anything up. That rule was being copy-pasted at each call site; this
 * is the one place that knows it.
 *
 * Nothing is cached: the value is read from the plan's feature row on every
 * call, so an admin changing a plan's number takes effect on the next check
 * with nothing to invalidate or recompute.
 */
class PlanFeature
{
    public static function value(Company $company, string $key): ?string
    {
        $subscription = $company->activeSubscription();
        $plan = $subscription?->plan;

        if ($plan === null) {
            return null;
        }

        $value = $subscription->getFeatureValue($plan->slug.'-'.$key);

        return $value !== null ? (string) $value : null;
    }

    /**
     * The same value as an integer allowance. Null means the plan does not
     * grant the feature at all, which callers treat as zero rather than as
     * unlimited — every quota feature in this app is a number.
     */
    public static function intValue(Company $company, string $key): ?int
    {
        $value = self::value($company, $key);

        return is_numeric($value) ? (int) $value : null;
    }
}
