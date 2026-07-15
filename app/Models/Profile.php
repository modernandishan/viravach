<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Fillable([
    'user_id',
    'username',
    'national_code',
    'country_id',
    'state_id',
    'city',
    'address',
    'postal_code',
    'biography',
    'skills',
    'gender',
    'birth_date',
    'job_title',
    'social_links',
])]
#[Translatable([
    'address',
    'biography',
    'job_title',
])]
class Profile extends Model
{
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'country_id' => 'integer',
            'state_id' => 'integer',
            // A flat list of tags, not per-locale — must not also be Translatable
            // (HasTranslations would otherwise intercept it and return a string).
            'skills' => 'array',
            'gender' => Gender::class,
            'birth_date' => 'date',
            'social_links' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
