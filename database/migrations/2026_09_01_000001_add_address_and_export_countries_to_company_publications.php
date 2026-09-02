<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot the company's primary address and its export countries onto
     * the publication.
     *
     * Both were previously read through to the live draft
     * ($publication->company->exportCountries), which leaked unapproved edits
     * onto the public page and into ViraBot's context. The address was not
     * snapshotted at all — only primaryAddress->state_id was — so the public
     * profile and the bot had no address to show.
     *
     * FKs point at the shared reference tables (countries/states/cities) with
     * nullOnDelete, matching how state_id was already handled: those rows are
     * shared reference data, not company-owned, so the snapshot references
     * rather than copies them.
     */
    public function up(): void
    {
        Schema::table('company_publications', function (Blueprint $table) {
            $table->foreignId('country_id')
                ->nullable()
                ->after('state_id')
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('city_id')
                ->nullable()
                ->after('country_id')
                ->constrained()
                ->nullOnDelete();

            // Translatable, mirroring company_addresses.address_line.
            $table->json('address_line')->nullable()->after('city_id');
            $table->string('postal_code')->nullable()->after('address_line');

            $table->decimal('latitude', 10, 6)->nullable()->after('postal_code');
            $table->decimal('longitude', 10, 6)->nullable()->after('latitude');
        });

        // Mirrors company_export_countries, keyed to the publication instead
        // of the draft. Named explicitly rather than by Laravel's convention
        // (company_publication_country) because the publication now also has
        // a country_id of its own for the address — the two must not read as
        // the same thing.
        Schema::create('company_publication_export_countries', function (Blueprint $table) {
            $table->foreignId('company_publication_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();

            $table->unique(
                ['company_publication_id', 'country_id'],
                'company_publication_export_country_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_publication_export_countries');

        Schema::table('company_publications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_id');
            $table->dropConstrainedForeignId('city_id');
            $table->dropColumn(['address_line', 'postal_code', 'latitude', 'longitude']);
        });
    }
};
