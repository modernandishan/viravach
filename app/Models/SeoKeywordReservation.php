<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Claim on an SEO keyword per locale. The (locale, keyword) pair is unique
 * across the table so two companies cannot target the same keyword in the
 * same language; the morph points at the owner (company, company
 * publication, ...).
 */
#[Fillable([
    'locale',
    'keyword',
    'seoable_type',
    'seoable_id',
])]
class SeoKeywordReservation extends Model
{
    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
