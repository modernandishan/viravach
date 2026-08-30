<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // enabled=false and empty base_url/model: nothing may generate until
        // an admin explicitly switches the pipeline on and points it at a
        // gateway. The translation model exists for later locale variants.
        $this->migrator->add('content.enabled', false);
        $this->migrator->add('content.provider', 'openwebui');
        $this->migrator->add('content.base_url', '');
        $this->migrator->add('content.api_key', '');
        $this->migrator->add('content.model', '');
        $this->migrator->add('content.translation_model', '');
        $this->migrator->add('content.timeout', 120);
        $this->migrator->add('content.max_retries', 2);
        $this->migrator->add('content.temperature', 0.5);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('content.enabled');
        $this->migrator->deleteIfExists('content.provider');
        $this->migrator->deleteIfExists('content.base_url');
        $this->migrator->deleteIfExists('content.api_key');
        $this->migrator->deleteIfExists('content.model');
        $this->migrator->deleteIfExists('content.translation_model');
        $this->migrator->deleteIfExists('content.timeout');
        $this->migrator->deleteIfExists('content.max_retries');
        $this->migrator->deleteIfExists('content.temperature');
    }
};
