<?php

namespace Database\Factories;

use App\Enums\RfqStatus;
use App\Models\Company;
use App\Models\Rfq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rfq>
 */
class RfqFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'buyer_name' => fake()->name(),
            'buyer_email' => fake()->safeEmail(),
            'buyer_phone' => fake()->phoneNumber(),
            'buyer_country' => fake()->country(),
            'message' => fake()->paragraph(),
            'locale' => 'en',
            'status' => RfqStatus::Pending,
            'ip_address' => fake()->ipv4(),
        ];
    }

    public function responded(): static
    {
        return $this->state(fn (): array => [
            'status' => RfqStatus::Responded,
        ]);
    }
}
