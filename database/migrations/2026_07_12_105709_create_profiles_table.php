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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('username')->nullable()->unique();
            $table->string('national_code', 10)->nullable()->unique();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->json('biography')->nullable();
            $table->json('skills')->nullable();
            $table->string('gender')->nullable();
            $table->date('birth_date')->nullable();
            $table->json('job_title')->nullable();
            $table->json('social_links')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
