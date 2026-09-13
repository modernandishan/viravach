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

    /**
     * Optional per-locale extra guidance ({locale} => text) appended to the
     * fixed translation prompt by CompanyContentLocalizationPrompt. Blank or
     * missing locale entries mean no extra guidance — the default.
     *
     * @var array<string, string>
     */
    public array $localization_prompt;

    public int $timeout;

    public int $max_retries;

    /** Low values suit factual copy that must not invent anything. */
    public float $temperature;

    /**
     * Independent of `enabled` — text content generation can run while
     * image generation stays off.
     */
    public bool $image_enabled;

    public string $image_model;

    public string $image_size;

    public static function group(): string
    {
        return 'content';
    }

    /**
     * api_key is encrypted at rest by the package itself (SettingsConfig
     * merges this list with any #[ShouldBeEncrypted] attributes) — encrypt
     * on save, decrypt on load, transparent to every reader. The existing
     * plain value is converted in place by the matching settings migration
     * in database/settings/; adding a name here WITHOUT that migration
     * would leave the stored plaintext to be handed to decrypt() on the
     * next load, which throws.
     *
     * @return array<int, string>
     */
    public static function encrypted(): array
    {
        return ['api_key'];
    }
}
