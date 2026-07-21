<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyPublication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyPublication>
 */
class CompanyPublicationFactory extends Factory
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
            'slug' => fake()->unique()->slug(),
            'name' => ['en' => fake()->company(), 'fa' => fake()->company()],
            'legal_name' => null,
            'legal_type' => null,
            'registration_number' => null,
            'national_id' => null,
            'established_at' => null,
            'description' => ['en' => fake()->paragraphs(3, true), 'fa' => fake()->paragraphs(3, true)],
            'summary' => null,
            'website' => null,
            'email' => fake()->companyEmail(),
            'phones' => null,
            'social_links' => null,
            'is_verified' => false,
            'is_featured' => false,
            'employee_range' => null,
            'state_id' => null,
            'published_at' => now()->subDay(),
        ];
    }
}
