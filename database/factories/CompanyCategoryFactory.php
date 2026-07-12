<?php

namespace Database\Factories;

use App\Models\CompanyCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyCategory>
 */
class CompanyCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'slug' => fake()->unique()->slug(),
            'title' => ['en' => fake()->words(2, true), 'fa' => fake()->words(2, true)],
            'description' => null,
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
