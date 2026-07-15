<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Fillable([
    'company_id',
    'country_id',
    'state_id',
    'city_id',
    'type',
    'address_line',
    'postal_code',
    'latitude',
    'longitude',
    'is_primary',
])]
#[Translatable([
    'address_line',
])]
class CompanyAddress extends Model
{
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'country_id' => 'integer',
            'state_id' => 'integer',
            'city_id' => 'integer',
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
            'is_primary' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
