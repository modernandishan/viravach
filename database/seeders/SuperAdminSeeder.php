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
            'name' => 'محمد جواد',
            'family' => 'قانع دستجردی',
            'phone' => '09120000000',
            'email' => 'info@viravach.com',
            'password' => '1qazxsw2',
        ]);

        $user->assignRole('super_admin');
    }
}
