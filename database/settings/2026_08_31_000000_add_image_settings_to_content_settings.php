<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Featured-image generation is its own opt-in switch, independent of
 * `content.enabled` — a company's text content can be AI-generated while
 * image generation stays off, e.g. while the image gateway/model is still
 * being evaluated.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('content.image_enabled', false);
        $this->migrator->add('content.image_model', '');
        $this->migrator->add('content.image_size', '1024x1024');
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('content.image_enabled');
        $this->migrator->deleteIfExists('content.image_model');
        $this->migrator->deleteIfExists('content.image_size');
    }
};
