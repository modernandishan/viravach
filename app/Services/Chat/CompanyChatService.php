<?php

namespace App\Services\Chat;

use App\Events\ChatConversationStarted;
use App\Models\Company;
use App\Services\Chat\Exceptions\CompanyChatException;
use Illuminate\Database\Eloquent\Model;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Musonza\Chat\Models\Conversation;

class CompanyChatService
{
    /**
     * Find (or start) the participant's direct conversation with the company
     * itself — the Company model is the counterpart participant, so its owner
     * replies AS the company from the dashboard. Mirrors
     * SupportTransferService::transfer().
     *
     * @throws CompanyChatException when the company has no owner to answer,
     *                              or the participant hasn't chatted with the
     *                              AI assistant about this company yet
     */
    public function transfer(Model $participant, Company $company): Conversation
    {
        if (! $this->hasOwner($company)) {
            throw CompanyChatException::noOwner();
        }

        if (! $this->isEligible($participant, $company)) {
            throw CompanyChatException::notEligible();
        }

        $conversation = $this->existingConversation($participant, $company);

        if (! $conversation) {
            // Guard against the same stale-relation pitfall as
            // AiChatService::startOrGetConversation() — see its comment.
            $participant->unsetRelation('participation');
            $company->unsetRelation('participation');

            $conversation = Chat::makeDirect()->createConversation(
                [$participant, $company],
                ['type' => 'company', 'company_id' => $company->id],
            );

            // Only on creation (a reused conversation is already in the
            // owner's company section): push the new row to whoever is
            // watching the company's dashboard list.
            ChatConversationStarted::dispatch($conversation, $company, $participant);
        }

        $companyName = (string) ($company->publication?->name ?? $company->name);

        Chat::message(__('chat.connected_to_company', ['company' => $companyName]))
            ->from($company)
            ->to($conversation)
            ->type('system')
            ->send();

        return $conversation;
    }

    /**
     * A participant may contact a company directly only after they've sent at
     * least one message in THIS company's AI-scoped conversation (same "chat
     * with the AI first" rule as the support transfer).
     */
    public function isEligible(Model $participant, Company $company): bool
    {
        $aiConversation = $this->findScopedConversation($participant, 'ai', $company->id);

        return $aiConversation !== null && $aiConversation->messages()->exists();
    }

    public function hasOwner(Company $company): bool
    {
        return $company->user()->exists();
    }

    /**
     * The participant's existing direct conversation with this company, if any.
     */
    public function existingConversation(Model $participant, Company $company): ?Conversation
    {
        return $this->findScopedConversation($participant, 'company', $company->id);
    }

    /**
     * musonza's "data" column is plain text (not a native json/jsonb column),
     * so Postgres's ->> path operator isn't available at the SQL level here.
     * A participant only ever has a handful of direct conversations, so it's
     * cheap to fetch them and filter by the cast `data` array in PHP instead
     * (same pattern as SupportTransferService). No direct_message constraint:
     * company-scoped AI conversations are intentionally non-direct (see
     * AiChatService::startOrGetConversation()).
     */
    protected function findScopedConversation(Model $participant, string $type, int $companyId): ?Conversation
    {
        return Conversation::query()
            ->whereHas('participants', function ($query) use ($participant) {
                $query->where('messageable_type', $participant->getMorphClass())
                    ->where('messageable_id', $participant->getKey());
            })
            ->get()
            ->first(fn (Conversation $conversation) => ($conversation->data['type'] ?? null) === $type
                && ($conversation->data['company_id'] ?? null) === $companyId);
    }
}
