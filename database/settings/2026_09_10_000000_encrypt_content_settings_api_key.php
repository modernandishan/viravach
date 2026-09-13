<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Converts the ALREADY EXISTING plain `content.api_key` value to its
 * encrypted form, to match ContentSettings::encrypted(). The key was
 * previously readable as plain JSON text in the settings table.
 *
 * encrypt()/decrypt() are the conversion operations — they read the current
 * payload and rewrite it in the other form (SettingsMigrator::encrypt() is
 * update() with Crypto::encrypt as the closure). addEncrypted() is NOT the
 * right call here: it creates a NEW property and throws SettingAlreadyExists
 * against a property that is already present.
 *
 * Idempotency: running up() twice would double-encrypt, and encrypt() on a
 * missing property throws SettingDoesNotExist — so both directions bail out
 * early unless the property is actually there.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('content.api_key')) {
            return;
        }

        $this->migrator->encrypt('content.api_key');
    }

    public function down(): void
    {
        if (! $this->migrator->exists('content.api_key')) {
            return;
        }

        $this->migrator->decrypt('content.api_key');
    }
};
