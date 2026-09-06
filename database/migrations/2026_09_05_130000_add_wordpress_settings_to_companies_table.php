<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-company WordPress publishing settings, entered on the dashboard
     * settings page. Only the draft Company carries them: they are operator
     * credentials/preferences, not public content, so they are deliberately
     * NOT copied onto the company_publications snapshot.
     *
     * wp_application_password is a `text` column rather than string(255)
     * because the model casts it to `encrypted` — the ciphertext is far
     * longer than the ~24 character password WordPress hands out.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('seo_plugin', 32)->nullable();
            $table->text('wp_application_password')->nullable();
            $table->string('content_generation_mode', 32)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'seo_plugin',
                'wp_application_password',
                'content_generation_mode',
            ]);
        });
    }
};
