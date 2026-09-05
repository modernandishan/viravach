<?php

namespace App\Services\Chat;

use App\Enums\TicketStatus;
use App\Events\ChatConversationStarted;
use App\Events\TicketCreated;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\Message as ChatMessage;

/**
 * Ticket support on top of musonza/chat: each ticket is a PRIVATE (group)
 * conversation tagged data.type = 'ticket' — deliberately NOT makeDirect(),
 * because musonza allows only one direct conversation per participant pair
 * (the constraint SupportTransferService works around with its single-agent
 * assignment). A private conversation has no such limit, and the Ticket row
 * itself — not participation — is what makes it visible to every staff user.
 *
 * The customer is always a participant from creation; staff members join as
 * explicit participants when they first reply (ChatParticipantResolver's
 * explicit-participation model), which gives them musonza read/unread
 * bookkeeping for the thread.
 */
class TicketService
{
    /**
     * Open a new ticket for the user: private conversation tagged
     * type=ticket, first message from the user, Ticket row with a
     * human-readable reference number, broadcast to staff channels.
     */
    public function create(User $user, string $subject, string $body): Ticket
    {
        return DB::transaction(function () use ($user, $subject, $body): Ticket {
            $conversation = Chat::createConversation([$user], ['type' => 'ticket']);

            Chat::message($body)->from($user)->to($conversation)->send();

            $ticket = Ticket::create([
                'reference_number' => $this->nextReferenceNumber(),
                'subject' => $subject,
                'status' => TicketStatus::Open,
                'user_id' => $user->getKey(),
                'conversation_id' => $conversation->getKey(),
            ]);

            TicketCreated::dispatch($ticket, $this->staffChannelNames());

            return $ticket;
        });
    }

    /**
     * A staff member answers a ticket: they join the conversation as an
     * explicit participant (claiming the thread for musonza's unread
     * bookkeeping), the message is sent, and the ticket moves to answered.
     */
    public function addStaffReply(Ticket $ticket, User $staff, string $body): ChatMessage
    {
        $conversation = $ticket->conversation;

        // Guard against the stale-relation pitfall documented in
        // SupportTransferService::transfer().
        $staff->unsetRelation('participation');

        $conversation->addParticipants([$staff]);

        Chat::message($body)->from($staff)->to($conversation)->send();

        $ticket->markAnswered();

        return Chat::conversation($conversation)
            ->setParticipant($staff)
            ->getMessages()
            ->last();
    }

    /**
     * The customer posts a follow-up. A closed ticket reopens automatically
     * (confirmed product decision); an answered one goes back to open.
     */
    public function addUserReply(Ticket $ticket, User $user, string $body): void
    {
        Chat::message($body)->from($user)->to($ticket->conversation)->send();

        $ticket->markUserReplied();
    }

    /**
     * TICKET-YYYYMMDD-NNNN: per-day sequence derived from the count of
     * existing references with the same date prefix, created inside the
     * caller's transaction; the unique index on reference_number is the
     * race-condition safety net.
     */
    protected function nextReferenceNumber(): string
    {
        $prefix = 'TICKET-'.now()->format('Ymd').'-';

        $sequence = Ticket::query()
            ->where('reference_number', 'like', $prefix.'%')
            ->count() + 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Per-user private channel names for every staff user, resolved up-front
     * so the queued broadcast job needs no database access (same pattern as
     * ChatConversationStarted's pre-resolved recipient channel).
     *
     * @return list<string>
     */
    protected function staffChannelNames(): array
    {
        $names = [];

        foreach (Ticket::STAFF_ROLES as $role) {
            User::role($role)
                ->get()
                ->each(function (User $staff) use (&$names) {
                    $channel = ChatConversationStarted::channelNameFor($staff);

                    if (! in_array($channel, $names, true)) {
                        $names[] = $channel;
                    }
                });
        }

        return $names;
    }
}
