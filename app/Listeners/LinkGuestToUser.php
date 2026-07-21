<?php

namespace App\Listeners;

use App\Models\Guest;
use App\Services\Chat\ChatParticipantResolver;
use Illuminate\Auth\Events\Login;

class LinkGuestToUser
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $token = request()->cookie(ChatParticipantResolver::GUEST_TOKEN_COOKIE);

        if (! $token) {
            return;
        }

        Guest::where('token', $token)
            ->whereNull('user_id')
            ->update(['user_id' => $event->user->getAuthIdentifier()]);
    }
}
