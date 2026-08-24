<?php

namespace App\Services\Chat;

use App\Events\ChatConversationStarted;
use App\Models\Company;
use App\Models\User;
use App\Services\Chat\Exceptions\CompanyChatException;
use Illuminate\Database\Eloquent\Model;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\Message;

/**
 * Direct visitor-to-company conversations: the Company model is the
 * counterpart participant, so its owner replies AS the company from the
 * dashboard. A participant may hold several of these (one per company they've
 * contacted), so company_id is required to tell them apart.
 */
class CompanyChatService
{
    /**
     * Find the participant's existing direct conversation with this company,
     * creating one if none exists yet. Idempotent — reopening never creates a
     * duplicate.
     *
     * @throws CompanyChatException when the company has no owner to answer, or
     *                              the participant IS the company's own owner
     *                              (a company must never be able to message
     *                              itself — enforced here so a crafted
     *                              Livewire call cannot bypass it)
     */
    public function startOrGetConversation(Model $participant, Company $company): Conversation
    {
        if (! $this->hasOwner($company)) {
            throw CompanyChatException::noOwner();
        }

        if ($this->isOwner($participant, $company)) {
            throw CompanyChatException::isOwner();
        }

        $existing = $this->existingConversation($participant, $company);

        if ($existing) {
            return $existing;
        }

        // createConversation() touches the participants' cached
        // "participation" relation; unsetting it guards against a stale,
        // empty relation on a reused $participant instance or the memoized
        // $assistant within one process.
        $participant->unsetRelation('participation');
        $company->unsetRelation('participation');

        // musonza enforces pair-level uniqueness for direct conversations
        // (Conversation::makeDirect() throws DirectMessagingExistsException
        // if this exact pair already has ANY direct conversation, regardless
        // of its data.type tag), so the type-scoped lookup above isn't
        // enough on its own — adopt whatever direct conversation the pair
        // already has instead of blindly trying to create a second one.
        $conversation = Chat::conversations()->between($participant, $company);

        if ($conversation) {
            $conversation->update(['data' => ['type' => 'company', 'company_id' => $company->id]]);
        } else {
            $conversation = Chat::makeDirect()->createConversation(
                [$participant, $company],
                ['type' => 'company', 'company_id' => $company->id],
            );
        }

        // Only on first becoming a company conversation for this pair: push
        // the new row to whoever is watching the company's dashboard list.
        ChatConversationStarted::dispatch($conversation, $company, $participant);

        return $conversation;
    }

    /**
     * Store the participant's message in their direct conversation with the
     * company. Mirrors AiChatService::sendUserMessage(), minus the AI job
     * dispatch — a human owner replies from the dashboard instead.
     */
    public function sendMessage(Model $participant, Company $company, string $body): Message
    {
        $conversation = $this->startOrGetConversation($participant, $company);

        return Chat::message($body)->from($participant)->to($conversation)->send();
    }

    public function hasOwner(Company $company): bool
    {
        return $company->user_id !== null;
    }

    /**
     * The participant's existing direct conversation with this company, if any.
     */
    public function existingConversation(Model $participant, Company $company): ?Conversation
    {
        return $this->findScopedConversation($participant, $company->id);
    }

    /**
     * Public so callers (e.g. the chat modal) can decide whether to show the
     * "contact the company" tab at all without triggering the exception just
     * to find out — a company must never be able to message itself.
     */
    public function isOwner(Model $participant, Company $company): bool
    {
        return $participant instanceof User && (int) $participant->getKey() === (int) $company->user_id;
    }

    /**
     * musonza's "data" column is plain text (not a native json/jsonb column),
     * so Postgres's ->> path operator isn't available at the SQL level here.
     * A participant only ever has a handful of direct conversations, so it's
     * cheap to fetch them and filter by the cast `data` array in PHP instead
     * (same pattern as SupportTransferService and AiChatService).
     */
    protected function findScopedConversation(Model $participant, int $companyId): ?Conversation
    {
        return Conversation::query()
            ->whereHas('participants', function ($query) use ($participant) {
                $query->where('messageable_type', $participant->getMorphClass())
                    ->where('messageable_id', $participant->getKey());
            })
            ->get()
            ->first(fn (Conversation $conversation) => ($conversation->data['type'] ?? null) === 'company'
                && ($conversation->data['company_id'] ?? null) === $companyId);
    }
}
