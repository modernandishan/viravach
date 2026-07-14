<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Fillable([
    'state_id',
    'name',
    'slug',
    'latitude',
    'longitude',
    'is_active',
])]
#[Translatable([
    'name',
])]
class City extends Model
{
    use HasFactory,
        HasTranslations,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'state_id' => 'integer',
            'is_active' => 'boolean',
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
        ];
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
