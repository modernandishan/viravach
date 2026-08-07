<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('chat.ai_enabled', true);
        $this->migrator->add('chat.ai_history_limit', null);
        $this->migrator->add('chat.rate_limit_messages_per_minute', null);
        $this->migrator->add('chat.rate_limit_translations_per_minute', null);
        $this->migrator->add('chat.system_prompts', []);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('chat.ai_enabled');
        $this->migrator->deleteIfExists('chat.ai_history_limit');
        $this->migrator->deleteIfExists('chat.rate_limit_messages_per_minute');
        $this->migrator->deleteIfExists('chat.rate_limit_translations_per_minute');
        $this->migrator->deleteIfExists('chat.system_prompts');
    }
};
