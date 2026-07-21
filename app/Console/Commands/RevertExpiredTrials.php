<?php

namespace App\Console\Commands;

use App\Services\CompanySubscriptionService;
use Illuminate\Console\Command;

class RevertExpiredTrials extends Command
{
    protected $signature = 'app:revert-expired-trials';

    protected $description = 'Revert companies whose 14-day Pro Plus trial has ended back to the Free plan';

    public function handle(CompanySubscriptionService $subscriptions): int
    {
        $reverted = $subscriptions->revertExpiredTrials();

        $this->info("Reverted {$reverted} expired trial subscription(s) to the Free plan.");

        return self::SUCCESS;
    }
}
