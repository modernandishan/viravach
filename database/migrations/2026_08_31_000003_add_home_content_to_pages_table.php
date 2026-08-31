<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Homepage copy lives on the Page row (per-locale), so the hero heading,
     * subheading and reserved intro body are editable in the Filament Page
     * resource like `title` already is.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->json('h1')->nullable();
            $table->json('subheading')->nullable();
            $table->json('intro_body')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['h1', 'subheading', 'intro_body']);
        });
    }
};
