<?php

namespace Database\Factories;

use App\Models\Rfq;
use App\Models\RfqResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RfqResponse>
 */
class RfqResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rfq_id' => Rfq::factory(),
            'user_id' => User::factory(),
            'message' => fake()->paragraph(),
        ];
    }
}
