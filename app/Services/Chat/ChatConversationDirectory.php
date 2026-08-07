<?php

namespace App\Services\Chat;

use Illuminate\Support\Collection;
use Musonza\Chat\Models\Conversation;

/**
 * Read-only classification of musonza conversations by their `data.type`
 * tag, shared by the admin conversation monitoring page and its stats
 * widget. musonza's `data` column is plain text (not jsonb) — same
 * rationale as AiChatService/SupportTransferService — so classification
 * happens in PHP against the cast `data` array rather than as a SQL
 * predicate on that column. Only `id`/`data`/`created_at`/`updated_at` are
 * selected for this scan; callers requery by id for anything heavier
 * (participants, message counts, transcripts).
 */
class ChatConversationDirectory
{
    /**
     * @return Collection<int, Conversation>
     */
    public function aiConversations(): Collection
    {
        return $this->conversationsOfType(['ai']);
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function humanConversations(): Collection
    {
        return $this->conversationsOfType(['support', 'company']);
    }

    /**
     * @param  array<int, string>  $types
     * @return Collection<int, Conversation>
     */
    protected function conversationsOfType(array $types): Collection
    {
        return Conversation::query()
            ->select(['id', 'data', 'created_at', 'updated_at'])
            ->get()
            ->filter(fn (Conversation $conversation) => in_array($conversation->data['type'] ?? null, $types, true))
            ->values();
    }
}
