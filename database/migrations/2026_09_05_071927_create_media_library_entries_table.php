<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The shared owner row for manually-uploaded media library files. Every
     * file an admin uploads from the Filament Media resource attaches to a
     * single MediaLibraryEntry row, giving the Media row its polymorphic
     * model_type/model_id without a real Company/Category owner yet. File
     * metadata (per-locale title/alt/...) lives on the Media row's custom
     * properties, exactly like attached media.
     */
    public function up(): void
    {
        Schema::create('media_library_entries', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_library_entries');
    }
};
