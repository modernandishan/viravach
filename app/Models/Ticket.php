<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Musonza\Chat\Models\Conversation;

#[Fillable([
    'reference_number',
    'subject',
    'status',
    'user_id',
    'conversation_id',
])]
class Ticket extends Model
{
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /** Staff roles that can see and answer every ticket. */
    public const STAFF_ROLES = ['support', 'admin', 'super_admin'];

    public static function isStaff(User $user): bool
    {
        foreach (self::STAFF_ROLES as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Staff reply accepted: the ticket moves to answered. No-op transitions
     * are harmless; only meaningful changes are persisted.
     */
    public function markAnswered(): void
    {
        if ($this->status !== TicketStatus::Answered) {
            $this->update(['status' => TicketStatus::Answered]);
        }
    }

    /**
     * Customer reply on a closed ticket reopens it (standard ticket-system
     * behaviour — confirmed product decision); on an answered ticket it goes
     * back to open, waiting for staff again. A reply on an already-open
     * ticket changes nothing.
     */
    public function markUserReplied(): void
    {
        if ($this->status === TicketStatus::Open) {
            return;
        }

        $this->update(['status' => TicketStatus::Open]);
    }
}
