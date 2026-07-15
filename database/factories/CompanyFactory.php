<?php

namespace Database\Factories;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
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
            'slug' => fake()->unique()->slug(),
            'name' => ['en' => fake()->company(), 'fa' => fake()->company()],
            'legal_name' => null,
            'legal_type' => null,
            'registration_number' => null,
            'national_id' => null,
            'established_at' => null,
            'description' => ['en' => fake()->paragraphs(3, true), 'fa' => fake()->paragraphs(3, true)],
            'summary' => null,
            'main_products' => null,
            'website' => null,
            'email' => fake()->companyEmail(),
            'phones' => null,
            'social_links' => null,
            'status' => CompanyStatus::Draft,
            'rejection_reason' => null,
            'is_verified' => false,
            'is_featured' => false,
            'employee_range' => null,
            'published_at' => null,
        ];
    }
}
