<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the AI-generated structured content payload: a plain locale-keyed
     * map ({fa: {...}, en: {...}}), NOT spatie-translatable (nested arrays
     * are unreliable there). company_publications keeps its description
     * column untouched so already-published pages keep rendering until the
     * renderer ships.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->jsonb('content')->nullable();
        });

        Schema::table('company_publications', function (Blueprint $table) {
            $table->jsonb('content')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('company_publications', function (Blueprint $table) {
            $table->dropColumn('content');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('content');
        });
    }
};
