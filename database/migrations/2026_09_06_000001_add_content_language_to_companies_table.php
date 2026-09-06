<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The language every generated article for this company is written in.
     *
     * The choice moves from the manual generation form into Settings, so the
     * scheduled generation job (which runs with no user present) has a
     * persisted value to read. Defaults to fa, matching the product
     * requirement "one generation, one language" the form used to default to.
     * The allowed values are Viravach's active locales — enforced by form
     * validation (Rule::in over config('laravellocalization.supportedLocales'))
     * rather than a database constraint, so adding a locale needs no migration.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('content_language', 5)->default('fa');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('content_language');
        });
    }
};
