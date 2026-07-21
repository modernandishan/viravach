<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_id' => Company::factory(),
            'plan_id' => fn () => Plan::query()->inRandomOrder()->value('id'),
            'amount' => fake()->numberBetween(100_000, 5_000_000),
            'status' => InvoiceStatus::Pending,
            'gateway' => 'zarinpal',
            'transaction_id' => null,
            'gateway_ref' => null,
            'failure_reason' => null,
        ];
    }
}
