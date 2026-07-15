<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyBrand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyBrand>
 */
class CompanyBrandFactory extends Factory
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
            'name' => ['en' => fake()->words(2, true), 'fa' => fake()->words(2, true)],
            'slug' => fake()->unique()->slug(),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
