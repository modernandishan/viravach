<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * Optional per-locale extra guidance appended to the fixed company-content
     * translation prompt (CompanyContentLocalizationPrompt). One array row
     * per field ({locale} => text) — spatie settings keys are strictly
     * group.name, so the per-locale map is the value. Defaults to an empty
     * map: an empty/blank entry means NO extra guidance, so prompt behaviour
     * is unchanged on deploy until an admin actually writes something.
     */
    public function up(): void
    {
        $this->migrator->add('content.localization_prompt', []);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('content.localization_prompt');
    }
};
