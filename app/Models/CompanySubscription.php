<?php

namespace App\Models;

use Laravelcm\Subscriptions\Models\Subscription as BaseSubscription;

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
}
