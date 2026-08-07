<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Musonza\Chat\Models\Participation;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| Chat channels
|--------------------------------------------------------------------------
|
| Guest participants (App\Models\Guest, resolved from the guest_token
| cookie) are deliberately NOT authorized here: Laravel's /broadcasting/auth
| endpoint requires an authenticated user, and a guest has none, so their
| subscription attempts are rejected before any callback runs. Building a
| signed guest broadcasting-auth flow was considered and postponed — guests
| keep the pre-Reverb polling fallback in the chat drawers instead (see the
| @guest branches in the drawer components). The tradeoff: guests see
| replies with a few seconds of poll latency; in exchange there is no
| custom auth endpoint whose signed tokens would effectively become
| long-lived credentials tied to a spoofable cookie.
|
*/

/*
 * musonza broadcasts MessageWasSent on this per-conversation channel.
 * Authorized when the authenticated user has a chat_participation row for
 * the conversation — either personally, or through a Company they own
 * (owners read/answer company threads AS the company on /dashboard/chat).
 */
Broadcast::channel('mc-chat-conversation.{conversationId}', function (User $user, int $conversationId) {
    $isDirectParticipant = Participation::query()
        ->where('conversation_id', $conversationId)
        ->where('messageable_type', $user->getMorphClass())
        ->where('messageable_id', $user->getKey())
        ->exists();

    if ($isDirectParticipant) {
        return true;
    }

    return Participation::query()
        ->where('conversation_id', $conversationId)
        ->where('messageable_type', (new Company)->getMorphClass())
        ->whereIn('messageable_id', $user->companies()->select('id'))
        ->exists();
});

/*
 * App\Events\ChatConversationStarted announces brand-new conversations on a
 * channel keyed to the recipient participant (naming defined by
 * ChatConversationStarted::channelNameFor()). Only the "user" and "company"
 * participant types get an authorization callback — "guest" and
 * "ai-assistant" channels have no legitimate subscriber, so they stay
 * undefined and every subscription attempt is rejected.
 */
Broadcast::channel('chat-participant.user.{id}', function (User $user, int $id) {
    return $user->getKey() === $id;
});

Broadcast::channel('chat-participant.company.{id}', function (User $user, int $id) {
    return $user->companies()->whereKey($id)->exists();
});
