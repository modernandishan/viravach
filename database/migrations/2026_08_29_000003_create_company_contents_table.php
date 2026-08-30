<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-company AI content pipeline state (one row per company): progress
     * tracking, the pristine model payloads and failure details. The
     * rendered payload itself lives on companies.content.
     */
    public function up(): void
    {
        Schema::create('company_contents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('status', 20)->default('draft')->index();
            $table->string('input_hash', 64)->nullable();
            $table->jsonb('ai_payload')->nullable();
            $table->unsignedTinyInteger('step')->default(0);
            $table->text('failure_reason')->nullable();
            $table->unsignedInteger('generations_count')->default(0);
            $table->timestamp('locked_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_contents');
    }
};
