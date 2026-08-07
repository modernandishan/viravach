<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Musonza\Chat\Models\Conversation;

/**
 * Broadcast when a chat service creates a conversation whose counterpart
 * ends up in the recipient's contact/inbox list. musonza's own MessageWasSent
 * only reaches subscribers of an EXISTING conversation channel — a recipient
 * who has never seen the conversation isn't subscribed yet, so without this
 * event a brand-new thread would only appear on their next fallback poll.
 *
 * Broadcast on a private channel keyed to the recipient participant
 * (see channelNameFor()); authorization lives in routes/channels.php.
 */
class ChatConversationStarted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public int $conversationId;

    public ?string $conversationType;

    /**
     * Name of the counterpart as the recipient's list will display it —
     * minimal preview data so the client can render a new row without a
     * full reload.
     */
    public string $counterpartName;

    /**
     * Resolved to a scalar up-front so the queued broadcast job doesn't
     * need the recipient model at all.
     */
    protected string $recipientChannel;

    public function __construct(Conversation $conversation, Model $recipient, Model $counterpart)
    {
        $this->conversationId = (int) $conversation->getKey();
        $this->conversationType = $conversation->data['type'] ?? null;
        $this->counterpartName = (string) ($counterpart->getParticipantDetails()['name'] ?? '');
        $this->recipientChannel = static::channelNameFor($recipient);
    }

    /**
     * Per-participant channel name, e.g. "chat-participant.user.5" or
     * "chat-participant.company.3" — kebab-cased class basename instead of
     * the morph class because backslashes don't belong in channel names.
     * Components building echo listeners must use this same helper.
     */
    public static function channelNameFor(Model $participant): string
    {
        return 'chat-participant.'.Str::kebab(class_basename($participant)).'.'.$participant->getKey();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel($this->recipientChannel);
    }

    /**
     * @return array{conversation_id: int, type: ?string, counterpart_name: string}
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'type' => $this->conversationType,
            'counterpart_name' => $this->counterpartName,
        ];
    }
}
