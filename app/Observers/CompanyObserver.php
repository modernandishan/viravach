<?php

namespace App\Observers;

use App\Models\Company;
use App\Services\CompanySubscriptionService;

class CompanyObserver
{
    public function __construct(private readonly CompanySubscriptionService $subscriptions) {}

    /**
     * Safety net for the "every company always has an active subscription"
     * invariant: catches any creation path (tinker, a future API, a bug in
     * a form) that skips explicit plan assignment. Does nothing if a plan
     * was already assigned earlier in the same request (e.g. Filament's
     * plan field, or the dashboard wizard), since assignFreePlanIfMissing()
     * only acts when no subscription exists yet.
     */
    public function created(Company $company): void
    {
        $this->subscriptions->assignFreePlanIfMissing($company);
    }
}
