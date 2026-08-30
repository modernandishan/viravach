<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Admin-editable configuration for the AI company-content generation
 * pipeline, mirroring the ChatSettings pattern: rows are created by the
 * matching settings migration in database/settings/ and edited in Filament.
 * `enabled` defaults to false — content generation is a paid, queue-driven
 * feature and must never fire implicitly.
 */
class ContentSettings extends Settings
{
    public bool $enabled;

    public string $provider;

    public string $base_url;

    public string $api_key;

    /** Model used for Persian generation. */
    public string $model;

    public string $translation_model;

    public int $timeout;

    public int $max_retries;

    /** Low values suit factual copy that must not invent anything. */
    public float $temperature;

    public static function group(): string
    {
        return 'content';
    }
}
