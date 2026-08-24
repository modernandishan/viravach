<?php

namespace App\Services\Chat;

use App\Events\ChatConversationStarted;
use App\Models\User;
use App\Services\Chat\Exceptions\SupportTransferException;
use Illuminate\Database\Eloquent\Model;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Musonza\Chat\Models\Conversation;

class SupportTransferService
{
    /**
     * Find (or start) the participant's direct conversation with a human
     * support agent, tagging it so it's distinguishable from AI conversations.
     *
     * @throws SupportTransferException when no support agent is available
     */
    public function transfer(Model $participant): Conversation
    {
        $existing = $this->existingConversation($participant);

        if ($existing) {
            return $existing;
        }

        $agent = $this->pickAgent($participant);

        if (! $agent) {
            throw SupportTransferException::noAgentAvailable();
        }

        // Guard against the same stale-relation pitfall as
        // AiChatService::startOrGetConversation() — see its comment.
        $participant->unsetRelation('participation');
        $agent->unsetRelation('participation');

        // musonza enforces pair-level uniqueness for direct conversations
        // (Conversation::makeDirect() throws DirectMessagingExistsException
        // if this exact pair already has ANY direct conversation, regardless
        // of its data.type tag), so the type-scoped lookup above isn't
        // enough on its own — adopt whatever direct conversation the pair
        // already has instead of blindly trying to create a second one.
        $conversation = Chat::conversations()->between($participant, $agent);

        if ($conversation) {
            $conversation->update(['data' => ['type' => 'support']]);
        } else {
            $conversation = Chat::makeDirect()->createConversation([$participant, $agent]);
            $conversation->update(['data' => ['type' => 'support']]);
        }

        // Only on first becoming a support conversation for this pair: push
        // the row to the assigned agent's inbox list and announce the
        // hand-off in the thread itself.
        ChatConversationStarted::dispatch($conversation, $agent, $participant);

        Chat::message(__('chat.transferred'))->from($agent)->to($conversation)->type('system')->send();

        return $conversation;
    }

    /**
     * Picks a random agent from the first of these roles that has any
     * candidate OTHER than the requesting participant: support, then admin,
     * then super_admin. Excluding the participant matters because musonza
     * allows only one direct conversation per participant pair — pairing the
     * requester with themselves would degenerately "match" any of their own
     * existing direct conversations in the between() lookup above and trip
     * DirectMessagingExistsException instead of a clean noAgentAvailable().
     * A crafted installation could theoretically exhaust all three roles
     * this way, but super_admin always exists in practice, so
     * SupportTransferException::noAgentAvailable() is kept as a safety net
     * rather than an expected outcome.
     */
    protected function pickAgent(Model $participant): ?User
    {
        foreach (['support', 'admin', 'super_admin'] as $role) {
            $query = User::role($role);

            if ($participant instanceof User) {
                $query->whereKeyNot($participant->getKey());
            }

            $agent = $query->inRandomOrder()->first();

            if ($agent) {
                return $agent;
            }
        }

        return null;
    }

    /**
     * The participant's existing direct support conversation, if any —
     * regardless of which agent it's assigned to.
     */
    public function existingConversation(Model $participant): ?Conversation
    {
        return $this->findConversationByType($participant, 'support');
    }

    /**
     * musonza's "data" column is plain text (not a native json/jsonb column),
     * so Postgres's ->> path operator isn't available at the SQL level here.
     * A participant only ever has a couple of direct conversations, so it's
     * cheap to fetch them and filter by the cast `data` array in PHP instead.
     */
    protected function findConversationByType(Model $participant, string $type): ?Conversation
    {
        return Conversation::query()
            ->where('direct_message', true)
            ->whereHas('participants', function ($query) use ($participant) {
                $query->where('messageable_type', $participant->getMorphClass())
                    ->where('messageable_id', $participant->getKey());
            })
            ->get()
            ->first(fn (Conversation $conversation) => ($conversation->data['type'] ?? null) === $type);
    }
}
