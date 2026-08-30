<?php

namespace App\Models;

use App\Enums\CompanyContentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-company AI content pipeline state (one row per company): generation
 * progress, the pristine model payloads and failure details. The rendered
 * payload itself lives on companies.content.
 */
#[Fillable([
    'company_id',
    'status',
    'input_hash',
    'ai_payload',
    'step',
    'failure_reason',
    'generations_count',
    'locked_at',
])]
class CompanyContent extends Model
{
    protected function casts(): array
    {
        return [
            'status' => CompanyContentStatus::class,
            'ai_payload' => 'array',
            'locked_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
