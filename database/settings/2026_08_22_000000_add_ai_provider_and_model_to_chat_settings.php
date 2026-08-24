<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('chat.ai_provider', 'openwebui');
        $this->migrator->add('chat.ai_model', 'virabot');
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('chat.ai_provider');
        $this->migrator->deleteIfExists('chat.ai_model');
    }
};
