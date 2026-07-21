<?php

namespace App\Services\Chat;

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
     * @throws SupportTransferException when the participant hasn't chatted
     *                                  with the AI assistant yet, or no support agent is available
     */
    public function transfer(Model $participant): Conversation
    {
        if (! $this->isEligible($participant)) {
            throw SupportTransferException::notEligible();
        }

        $existing = $this->existingConversation($participant);

        if ($existing) {
            return $existing;
        }

        $agent = User::role('support')->inRandomOrder()->first();

        if (! $agent) {
            throw SupportTransferException::noAgentAvailable();
        }

        // Guard against the same stale-relation pitfall as
        // AiChatService::startOrGetConversation() — see its comment.
        $participant->unsetRelation('participation');
        $agent->unsetRelation('participation');

        $conversation = Chat::makeDirect()->createConversation([$participant, $agent]);
        $conversation->update(['data' => ['type' => 'support']]);

        Chat::message(__('chat.transferred'))->from($agent)->to($conversation)->type('system')->send();

        return $conversation;
    }

    /**
     * A participant becomes eligible for a human transfer only after they've
     * sent at least one message to the AI assistant.
     */
    public function isEligible(Model $participant): bool
    {
        $aiConversation = $this->findConversationByType($participant, 'ai');

        return $aiConversation !== null && $aiConversation->messages()->exists();
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
