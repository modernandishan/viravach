<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call(CountrySeeder::class);
        $this->call(StateSeeder::class);
        $this->call(IsfahanCitiesSeeder::class);

        $this->call(GeneralSettingSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(SuperAdminSeeder::class);

        $this->call(CompanyCategorySeeder::class);
        $this->call(HomePageSeeder::class);
        $this->call(PlanSeeder::class);
        $this->call(AiAssistantSeeder::class);
    }
}
