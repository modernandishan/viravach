<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Models\Country;
use App\Models\Profile;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's super admin user.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => [
                'fa' => 'محمد جواد',
                'en' => 'Mohammad Javad',
            ],
            'family' => [
                'fa' => 'قانع دستجردی',
                'en' => 'Ghane Dastgerdy',
            ],
            'phone' => '09332253169',
            'email' => 'info@viravach.com',
            'password' => '1qazxsw2',
        ]);

        $user->assignRole('super_admin');

        // WithoutModelEvents (used by this seeder) suppresses UserObserver,
        // so the profile row must be created explicitly here rather than
        // relying on the observer's Profile::create() on the "created" event.
        $profile = Profile::firstOrCreate(['user_id' => $user->id]);

        $country = Country::first();
        $state = State::first();

        $profile->update([
            'country_id' => $country->id,
            'state_id' => $state->id,

            'username' => 'mjavad',
            'national_code' => '1234567890',
            'gender' => Gender::Male,
            'birth_date' => '1999-01-01',

            'job_title' => [
                'fa' => 'توسعه‌دهنده ارشد لاراول',
                'en' => 'Senior Laravel Developer',
            ],
            'skills' => ['Laravel', 'Filament', 'Vue.js', 'TailwindCSS'],
            'social_links' => [
                'github' => 'https://github.com/mjavad',
                'linkedin' => 'https://linkedin.com/in/mjavad',
            ],
        ]);
    }
}
