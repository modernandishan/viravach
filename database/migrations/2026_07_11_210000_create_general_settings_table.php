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
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();

            $table->json('site_name');
            $table->json('site_tagline')->nullable();

            $table->string('favicon')->nullable();
            $table->string('logo_square_light')->nullable();
            $table->string('logo_square_dark')->nullable();
            $table->string('logo_wide_light')->nullable();
            $table->string('logo_wide_dark')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_settings');
    }
};
