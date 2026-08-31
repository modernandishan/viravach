<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->json('footer_about')->nullable();

            $table->string('social_facebook')->nullable();
            $table->string('social_instagram')->nullable();
            $table->string('social_twitter')->nullable();
            $table->string('social_linkedin')->nullable();
            $table->string('social_telegram')->nullable();
            $table->string('social_whatsapp')->nullable();

            $table->string('contact_address')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();

            $table->text('enamad_html')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn([
                'footer_about',
                'social_facebook',
                'social_instagram',
                'social_twitter',
                'social_linkedin',
                'social_telegram',
                'social_whatsapp',
                'contact_address',
                'contact_phone',
                'contact_email',
                'enamad_html',
            ]);
        });
    }
};
