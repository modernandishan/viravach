<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * publish() no longer copies description (the draft column was
     * repurposed into the single-language brief). The snapshot's column is
     * kept — nullable now — only so already-published pages keep rendering
     * their last approved HTML until the AI content renderer ships.
     */
    public function up(): void
    {
        Schema::table('company_publications', function (Blueprint $table) {
            $table->json('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('company_publications', function (Blueprint $table) {
            $table->json('description')->nullable(false)->change();
        });
    }
};
