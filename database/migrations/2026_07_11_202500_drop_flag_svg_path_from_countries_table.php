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
        // پرچم اکنون از طریق Spatie Media Library (کالکشن «flag») مدیریت می‌شود
        // تا در بخش رسانه‌ی فیلامنت قابل مشاهده و حذف امن از MinIO باشد.
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn('flag_svg_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->string('flag_svg_path')->nullable();
        });
    }
};
