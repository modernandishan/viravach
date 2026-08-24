<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Admin-editable overrides for config/viravach_chat.php and the chat
 * components' hardcoded rate limits. Every property is nullable/empty by
 * default (see the matching settings migration) so an unset value means
 * "fall back to the config default" rather than an admin-chosen value —
 * consuming code must apply that fallback itself, this class only stores
 * whatever the admin has explicitly overridden.
 */
class ChatSettings extends Settings
{
    public bool $ai_enabled;

    public string $ai_provider;

    public string $ai_model;

    public ?int $ai_history_limit;

    public ?int $rate_limit_messages_per_minute;

    public ?int $rate_limit_translations_per_minute;

    /**
     * Per-locale system prompt overrides, keyed the same way as
     * config('viravach_chat.system_prompts'). A missing key or blank string
     * falls back to the config array's prompt for that locale — values are
     * plain (non-nullable) strings because laravel-settings can't build an
     * ArraySettingsCast for a nullable-string value type.
     *
     * @var array<string, string>
     */
    public array $system_prompts;

    public static function group(): string
    {
        return 'chat';
    }
}
