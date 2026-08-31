<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * description has been dead since CompanyPublicationService stopped
     * copying it (see 2026_08_30_000001_make_company_publications_description_nullable);
     * the AI-generated content payload replaced it everywhere it was read.
     */
    public function up(): void
    {
        Schema::table('company_publications', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Re-adds the column shape only — the original translated content is
     * not recoverable, since the content pipeline replaced it and nothing
     * has written to this column since.
     */
    public function down(): void
    {
        Schema::table('company_publications', function (Blueprint $table) {
            $table->json('description')->nullable();
        });
    }
};
