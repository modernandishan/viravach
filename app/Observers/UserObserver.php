<?php

namespace App\Observers;

use App\Models\Profile;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $role = Role::firstOrCreate(['name' => 'user']);

        if (! $user->hasRole('user')) {
            $user->assignRole($role);
        }

        Profile::create([
            'user_id' => $user->id,
        ]);
    }
}
