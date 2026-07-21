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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();

            $table->json('name');
            $table->json('official_name');
            $table->json('capital');
            $table->json('currency_name');
            $table->string('slug')->unique();

            $table->char('iso2', 2)->unique()->nullable();
            $table->char('iso3', 3)->unique()->nullable();
            $table->char('numeric_code', 3)->unique()->nullable();
            $table->string('phone_code', 10);

            $table->char('currency', 3);
            $table->string('currency_symbol', 10);

            $table->string('tld', 20)->nullable();
            $table->string('region', 100)->index()->nullable();
            $table->string('subregion', 100)->index()->nullable();

            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('bounding_box')->nullable();
            $table->unsignedBigInteger('area')->nullable();
            $table->unsignedBigInteger('population')->nullable();

            $table->string('flag_emoji', 10)->nullable();
            // $table->string('flag_svg_path')->nullable();

            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
