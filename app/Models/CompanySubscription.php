<?php

namespace App\Models;

use Laravelcm\Subscriptions\Models\Subscription as BaseSubscription;
use Laravelcm\Subscriptions\Models\SubscriptionUsage;

/**
 * Overrides the package's Subscription model to add `is_trial` — a marker
 * for the daily revert-expired-trials command, kept off the package's own
 * model since it's specific to this app's trial policy.
 */
class CompanySubscription extends BaseSubscription
{
    /**
     * Merged with the package's own $casts property (Laravel merges a
     * casts() method's return value with the parent's $casts property
     * automatically) — no need to repeat the inherited casts here.
     */
    protected function casts(): array
    {
        return [
            'is_trial' => 'boolean',
        ];
    }

    /**
     * The package's recordFeatureUsage() hardcodes a `subscription_id`
     * column, but this app's usage table names the FK after the extended
     * model (company_subscription_id, via foreignIdFor in the migration) —
     * so the insert is repeated here with the correct column while keeping
     * the package's resettable-period logic identical.
     */
    public function recordFeatureUsage(string $featureSlug, int $uses = 1, bool $incremental = true): SubscriptionUsage
    {
        $feature = $this->plan->features()->where('slug', $featureSlug)->first();

        $usage = $this->usage()->firstOrNew([
            'company_subscription_id' => $this->getKey(),
            'feature_id' => $feature->getKey(),
        ]);

        if ($feature->resettable_period) {
            if ($usage->valid_until === null) {
                $usage->fill(['valid_until' => $feature->getResetDate($this->created_at)]);
            } elseif ($usage->expired()) {
                $usage->fill([
                    'valid_until' => $feature->getResetDate($usage->valid_until),
                    'used' => 0,
                ]);
            }
        }

        $usage->fill(['used' => $incremental ? $usage->used + $uses : $uses]);

        $usage->save();

        return $usage;
    }

    /**
     * Give back a feature's usage for this billing period — e.g. an admin
     * compensating a company whose generation was queued (burning the
     * quota) but then failed for a technical reason. Deletes the usage row
     * outright rather than zeroing `used` in place, so a getFeatureUsage()/
     * canUseFeature() check right after sees a fresh feature exactly as if
     * it had never been used this period. `usage()` is already scoped to
     * this subscription via the FK, same as recordFeatureUsage() above.
     */
    public function resetFeatureUsage(string $featureSlug): void
    {
        $feature = $this->plan->features()->where('slug', $featureSlug)->first();

        if ($feature === null) {
            return;
        }

        $this->usage()->where('feature_id', $feature->getKey())->delete();
    }
}
