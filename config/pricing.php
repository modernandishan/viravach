<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Minimum purchase amount
    |--------------------------------------------------------------------------
    |
    | Plans are priced in Toman (see database/seeders/PlanSeeder.php) and
    | charged as-is via the Zarinpal gateway. Reject paid-plan purchases
    | below this amount before ever reaching the gateway.
    |
    */
    'min_purchase_amount' => env('PAYMENT_MIN_AMOUNT', 10000),
];
