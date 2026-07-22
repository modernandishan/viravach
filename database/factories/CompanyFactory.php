<?php

namespace Database\Factories;

use App\Enums\CompanyReviewStatus;
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
            'website' => null,
            'email' => fake()->companyEmail(),
            'phones' => null,
            'social_links' => null,
            'review_status' => CompanyReviewStatus::PendingReview,
            'reviewed_at' => null,
            'is_verified' => false,
            'is_featured' => false,
            'employee_range' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'review_status' => CompanyReviewStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }
}
