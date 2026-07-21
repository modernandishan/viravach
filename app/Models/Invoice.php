<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'company_id',
    'plan_id',
    'amount',
    'status',
    'gateway',
    'transaction_id',
    'gateway_ref',
    'failure_reason',
])]
class Invoice extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'status' => InvoiceStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
