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
        // og_image و twitter_image اکنون از طریق Spatie Media Library مدیریت
        // می‌شوند تا در بخش رسانه‌ی فیلامنت قابل مشاهده و حذف امن از MinIO باشند.
        Schema::table('seo_metas', function (Blueprint $table) {
            $table->dropColumn(['og_image', 'twitter_image']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seo_metas', function (Blueprint $table) {
            $table->string('og_image')->nullable();
            $table->string('twitter_image')->nullable();
        });
    }
};
