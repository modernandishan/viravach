<?php

namespace Database\Seeders;

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
    }
}
