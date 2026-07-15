<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyAddress>
 */
class CompanyAddressFactory extends Factory
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
            'country_id' => fn () => Country::query()->inRandomOrder()->value('id'),
            'state_id' => null,
            'city_id' => null,
            'type' => 'office',
            'address_line' => ['en' => fake()->address(), 'fa' => fake()->address()],
            'postal_code' => fake()->postcode(),
            'latitude' => null,
            'longitude' => null,
            'is_primary' => false,
        ];
    }
}
