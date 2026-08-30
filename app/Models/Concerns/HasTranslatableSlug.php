<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Fills the `slug` column from the model's translatable label when it is
 * left empty on create. Slugs are ALWAYS Latin: the label's English (then
 * fallback-locale) translation is run through Str::slug(), which strips
 * non-Latin characters, and a random lowercase latin string is used when
 * nothing usable remains — a Persian-only name must yield a random latin
 * slug, never an empty one and never Persian characters.
 *
 * Existing slugs are never touched: an explicitly supplied slug wins on
 * create, and updates are left entirely alone.
 */
trait HasTranslatableSlug
{
    protected static function bootHasTranslatableSlug(): void
    {
        static::creating(function (Model $model): void {
            if (trim((string) $model->getAttribute('slug')) === '') {
                $model->setAttribute('slug', $model->generateTranslatableSlug());
            }
        });
    }

    /**
     * The translatable attribute the slug is built from; a model whose
     * label is named differently overrides this (CompanyCategory uses
     * `title`).
     */
    protected function slugSourceAttribute(): string
    {
        return 'name';
    }

    /**
     * Whether the slug must ALWAYS carry a random suffix. True for models
     * whose slugs are globally unique and whose labels collide often
     * (Company); false appends a suffix only when the base slug is already
     * taken on the model's own table.
     */
    protected function slugAlwaysSuffix(): bool
    {
        return false;
    }

    protected function generateTranslatableSlug(): string
    {
        /** @var array<string, string|null> $translations */
        $translations = $this->getTranslations($this->slugSourceAttribute());

        $base = Str::slug((string) ($translations['en'] ?? ''));

        if ($base === '') {
            $base = Str::slug((string) ($translations[(string) config('app.fallback_locale')] ?? ''));
        }

        return $this->regenerateSlugFromBase($base);
    }

    /**
     * Public regeneration entry point: turn an already-chosen base string
     * into the model's final slug form — Str::slug() plus the model's own
     * suffix policy. Used by the AI pipeline and the slug backfill command
     * to rebuild slugs from generated content WITHOUT duplicating the
     * suffix logic here.
     */
    public function regenerateSlugFromBase(string $base): string
    {
        $base = Str::slug($base);

        if ($base === '') {
            return Str::lower(Str::random(8));
        }

        if ($this->slugAlwaysSuffix() || static::query()->where('slug', $base)->exists()) {
            return $base.'-'.Str::lower(Str::random(6));
        }

        return $base;
    }
}
