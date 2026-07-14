<?php

namespace App\Observers;

use App\Models\Profile;
use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $user->assignRole('user');

        Profile::query()->create([
            'user_id' => $user->id,
        ]);
    }
}
