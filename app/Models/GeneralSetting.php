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
    'footer_about',
    'social_facebook',
    'social_instagram',
    'social_twitter',
    'social_linkedin',
    'social_telegram',
    'social_whatsapp',
    'contact_address',
    'contact_phone',
    'contact_email',
    'enamad_html',
    'trustpilot_enabled',
    'trustpilot_business_unit_id',
    'trustpilot_template_id',
    'trustpilot_locale',
])]
#[Translatable([
    'site_name',
    'site_tagline',
    'footer_about',
])]
class GeneralSetting extends Model
{
    use HasTranslations;

    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['site_name' => []]);
    }
}
