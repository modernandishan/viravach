<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a user opens a new ticket so support staff see it in the
 * shared queue without waiting for the next fallback poll. Unlike
 * ChatConversationStarted there is no single recipient: tickets are visible
 * to every staff user (support/admin/super_admin), so the event is broadcast
 * once per staff member on their existing per-participant private channel —
 * the same channels and authorization the support inbox already uses.
 */
class TicketCreated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public int $ticketId;

    public string $referenceNumber;

    public string $subject;

    public string $creatorName;

    /**
     * @param  list<string>  $staffChannelNames  pre-resolved channel names
     *                                           (one per staff user)
     */
    public function __construct(
        Ticket $ticket,
        public array $staffChannelNames,
    ) {
        $this->ticketId = $ticket->getKey();
        $this->referenceNumber = $ticket->reference_number;
        $this->subject = $ticket->subject;
        $this->creatorName = (string) ($ticket->user->getParticipantDetails()['name'] ?? '');
    }

    public function broadcastOn(): array
    {
        return array_map(
            fn (string $channel) => new PrivateChannel($channel),
            $this->staffChannelNames
        );
    }

    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticketId,
            'reference_number' => $this->referenceNumber,
            'subject' => $this->subject,
            'creator_name' => $this->creatorName,
        ];
    }
}
