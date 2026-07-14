<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's default roles.
     */
    public function run(): void
    {
        $roles = [
            'super_admin',
            'shareholder',

            'admin',
            'support',

            'export_expert',
            'marketer',

            'user',
            'discounter',
        ];

        foreach ($roles as $role) {
            Role::findOrCreate($role);
        }
    }
}
