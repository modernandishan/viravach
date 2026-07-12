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
    'country_id',
    'name',
    'slug',
    'code',
    'type',
    'latitude',
    'longitude',
    'is_active',
])]
#[Translatable([
    'name',
    'type',
])]
class State extends Model
{
    use HasFactory,
        HasTranslations,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'country_id' => 'integer',
            'is_active'  => 'boolean',
            'latitude'   => 'decimal:6',
            'longitude'  => 'decimal:6',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
