<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', [
            'en' => config('app.name', 'Laravel'),
            'fa' => '',
            'ar' => '',
            'ru' => '',
            'tr' => '',
        ]);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('general.site_name');
    }
};
