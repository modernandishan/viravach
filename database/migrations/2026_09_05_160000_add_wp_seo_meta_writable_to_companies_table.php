<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Whether the company's WordPress site accepts SEO plugin meta over the
     * REST API.
     *
     * Neither Yoast nor Rank Math registers its meta keys as REST-writable:
     * Yoast's REST API is documented read-only, and Rank Math's keys are
     * unregistered, so a post request carrying them succeeds while WordPress
     * silently drops the keys. The publisher therefore sends them, reads the
     * post back, and records here whether they actually stuck.
     *
     * Nullable with no default because the honest third state is "we have
     * not published anything yet, so we do not know".
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('wp_seo_meta_writable')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('wp_seo_meta_writable');
        });
    }
};
