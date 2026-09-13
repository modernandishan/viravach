<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CURRENTLY UNUSED — nothing in the app reads or writes this table.
 *
 * It backed the in-platform reply thread on the RFQ inbox, which was removed:
 * an RFQ buyer is a guest with no account, so a reply stored here had no
 * inbox to be delivered to and the owner had to contact them out of band
 * anyway. The inbox now shows the buyer's email and phone and the owner
 * answers by whatever channel they choose.
 *
 * Model and `rfq_responses` table are both kept rather than dropped: a future
 * authenticated-buyer version of RFQ would need exactly this shape, and the
 * table holds whatever replies were written before the removal.
 */
#[Fillable([
    'rfq_id',
    'user_id',
    'message',
])]
class RfqResponse extends Model
{
    use HasFactory;

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    /** Null once the staff member who wrote the reply is deleted. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
