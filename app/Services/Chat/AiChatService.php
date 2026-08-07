<?php

namespace App\Services\Chat;

use App\Events\ChatConversationStarted;
use App\Jobs\GenerateAiChatReply;
use App\Models\AiAssistant;
use App\Settings\ChatSettings;
use Illuminate\Database\Eloquent\Model;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\Message;

class AiChatService
{
    protected static ?AiAssistant $assistant = null;

    /**
     * Find the participant's existing AI conversation, creating a new
     * direct/private one with the AiAssistant if none exists yet.
     *
     * With $companyId = null this is the participant's single general AI
     * conversation (data.type=ai, no company_id). With a $companyId it is a
     * SEPARATE conversation scoped to that company (data.company_id set), so
     * company-page chats never leak one company's context into another
     * company's replies or into the general assistant. Because one
     * participant can now hold several direct conversations with the same
     * assistant, Chat::conversations()->between() is ambiguous here and the
     * lookup goes through findAiConversation() instead.
     */
    public function startOrGetConversation(Model $participant, ?int $companyId = null): Conversation
    {
        $existing = $this->findAiConversation($participant, $companyId);

        if ($existing) {
            return $existing;
        }

        $assistant = $this->assistant();

        // createConversation() touches the participants' cached
        // "participation" relation; unsetting it guards against a stale,
        // empty relation on a reused $participant instance or the memoized
        // $assistant within one process.
        $participant->unsetRelation('participation');
        $assistant->unsetRelation('participation');

        if ($companyId === null) {
            $conversation = Chat::makeDirect()->createConversation([$participant, $assistant], ['type' => 'ai']);
        } else {
            // Company-scoped conversations must NOT be direct_message: musonza
            // allows exactly ONE direct conversation per participant pair, and
            // this pair's slot is taken by the general AI conversation (creating
            // a second direct one throws DirectMessagingExistsException). The
            // data tags are what scope these, not the direct flag. A fresh Chat
            // instance is resolved because the facade's request-cached instance
            // keeps directMessage sticky after any makeDirect() call.
            $conversation = app(\Musonza\Chat\Chat::class)->createConversation(
                [$participant, $assistant],
                ['type' => 'ai', 'company_id' => $companyId],
            );
        }

        // Only on creation: a reused conversation is already in the
        // recipient's list, so there is nothing to push.
        ChatConversationStarted::dispatch($conversation, $assistant, $participant);

        return $conversation;
    }

    /**
     * Store the user's message in their conversation with the assistant and
     * queue the AI reply generation — unless the admin kill-switch
     * (ChatSettings::$ai_enabled) is off, in which case the reply job is
     * skipped entirely and the same fallback error message normally sent
     * after a failed job (see GenerateAiChatReply::failed()) is sent right
     * away, so the user never sits waiting on a reply that will never come.
     */
    public function sendUserMessage(Model $participant, string $body, ?string $context = null, ?int $companyId = null): Message
    {
        $conversation = $this->startOrGetConversation($participant, $companyId);

        $message = Chat::message($body)->from($participant)->to($conversation)->send();

        if (app(ChatSettings::class)->ai_enabled) {
            // Locale is captured here, at send time, because the queued job
            // runs outside the request and app()->getLocale() there would
            // fall back to the app default instead of the sender's site locale.
            GenerateAiChatReply::dispatch($conversation->id, $participant, $context, app()->getLocale());
        } else {
            Chat::message(__('chat.ai_error'))->from($this->assistant())->to($conversation)->type('ai_error')->send();
        }

        return $message;
    }

    /**
     * musonza's "data" column is plain text (not a native json/jsonb column),
     * so Postgres's ->> path operator isn't available at the SQL level here.
     * A participant only ever has a handful of direct conversations, so it's
     * cheap to fetch them and filter by the cast `data` array in PHP instead
     * (same pattern as SupportTransferService). No direct_message constraint:
     * company-scoped AI conversations are intentionally non-direct (see
     * startOrGetConversation()).
     */
    protected function findAiConversation(Model $participant, ?int $companyId): ?Conversation
    {
        return Conversation::query()
            ->whereHas('participants', function ($query) use ($participant) {
                $query->where('messageable_type', $participant->getMorphClass())
                    ->where('messageable_id', $participant->getKey());
            })
            ->get()
            ->first(fn (Conversation $conversation) => ($conversation->data['type'] ?? null) === 'ai'
                && ($conversation->data['company_id'] ?? null) === $companyId);
    }

    protected function assistant(): AiAssistant
    {
        return static::$assistant ??= AiAssistant::query()->firstOrFail();
    }
}
