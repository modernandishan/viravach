<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * temperature arrived after create_content_settings had already run in some
 * environments, so it lives here — fresh environments run both migrations
 * in order and end up with the same final state.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('content.temperature', 0.5);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('content.temperature');
    }
};
