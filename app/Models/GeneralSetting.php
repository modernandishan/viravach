<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Fillable([
    'site_name',
    'site_tagline',
    'favicon',
    'logo_square_light',
    'logo_square_dark',
    'logo_wide_light',
    'logo_wide_dark',
])]
#[Translatable([
    'site_name',
    'site_tagline',
])]
class GeneralSetting extends Model
{
    use HasTranslations;

    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['site_name' => []]);
    }
}
