<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
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

        // Custom Shield permissions for the company review workflow (also
        // declared in config/filament-shield.php custom_permissions so they
        // show up when editing roles). super_admin holds permissions
        // explicitly because shield's define_via_gate is disabled.
        $reviewPermissions = collect(['Approve:Company', 'Reject:Company'])
            ->map(fn (string $permission) => Permission::findOrCreate($permission));

        Role::findOrCreate('super_admin')->givePermissionTo($reviewPermissions);
    }
}
