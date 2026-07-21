<?php

namespace App\Services\Chat;

use App\Jobs\GenerateAiChatReply;
use App\Models\AiAssistant;
use Illuminate\Database\Eloquent\Model;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\Message;

class AiChatService
{
    protected static ?AiAssistant $assistant = null;

    /**
     * Find the existing direct conversation between the participant and the
     * AiAssistant, creating a new direct/private one if none exists yet.
     */
    public function startOrGetConversation(Model $participant): Conversation
    {
        $assistant = $this->assistant();

        // Chat::conversations()->between() reads the participants' cached
        // "participation" relation. Without unsetting it here, a repeated
        // call on the same $participant instance (or the memoized $assistant)
        // within one process would see a stale, empty relation and create a
        // duplicate direct conversation instead of finding the existing one.
        $participant->unsetRelation('participation');
        $assistant->unsetRelation('participation');

        $conversation = Chat::conversations()->between($participant, $assistant);

        if ($conversation) {
            return $conversation;
        }

        $conversation = Chat::makeDirect()->createConversation([$participant, $assistant]);
        $conversation->update(['data' => ['type' => 'ai']]);

        return $conversation;
    }

    /**
     * Store the user's message in their conversation with the assistant and
     * queue the AI reply generation.
     */
    public function sendUserMessage(Model $participant, string $body, ?string $context = null): Message
    {
        $conversation = $this->startOrGetConversation($participant);

        $message = Chat::message($body)->from($participant)->to($conversation)->send();

        GenerateAiChatReply::dispatch($conversation->id, $participant, $context);

        return $message;
    }

    protected function assistant(): AiAssistant
    {
        return static::$assistant ??= AiAssistant::query()->firstOrFail();
    }
}
