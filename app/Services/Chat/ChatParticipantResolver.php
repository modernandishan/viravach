<?php

namespace App\Services\Chat;

use App\Models\Guest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class ChatParticipantResolver
{
    public const GUEST_TOKEN_COOKIE = 'guest_token';

    /**
     * Resolve the current chat participant: the authenticated user, or the
     * guest identified by the guest_token cookie (created on first use).
     */
    public function resolve(): Model
    {
        if ($user = Auth::user()) {
            return $user;
        }

        $token = request()->cookie(self::GUEST_TOKEN_COOKIE);

        $guest = $token ? Guest::where('token', $token)->first() : null;

        if (! $guest) {
            $guest = Guest::create(['token' => (string) Str::uuid()]);

            Cookie::queue(Cookie::make(self::GUEST_TOKEN_COOKIE, $guest->token, 60 * 24 * 365));
        }

        return $guest;
    }
}
