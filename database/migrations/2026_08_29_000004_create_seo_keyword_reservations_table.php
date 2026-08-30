<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Claims on SEO keywords per locale. The (locale, keyword) pair is
     * globally unique so two companies cannot target the same keyword in
     * the same language; the morph points at the owner (company, company
     * publication, ...).
     */
    public function up(): void
    {
        Schema::create('seo_keyword_reservations', function (Blueprint $table) {
            $table->id();

            $table->string('locale', 5);
            $table->string('keyword', 191);

            $table->string('seoable_type');
            $table->unsignedBigInteger('seoable_id');

            $table->timestamps();

            $table->unique(['locale', 'keyword']);
            $table->index(['seoable_type', 'seoable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_keyword_reservations');
    }
};
