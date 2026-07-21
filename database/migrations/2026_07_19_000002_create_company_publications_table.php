<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Immutable public snapshot of an approved company. The row must survive
     * hard deletion of the source company, hence the nullable nullOnDelete FK.
     */
    public function up(): void
    {
        Schema::create('company_publications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->nullable()
                ->unique()
                ->constrained()
                ->nullOnDelete();

            $table->string('slug')->unique();

            $table->json('name');
            $table->json('legal_name')->nullable();
            $table->string('legal_type')->nullable();

            $table->string('registration_number')->nullable();
            $table->string('national_id')->nullable();
            $table->date('established_at')->nullable();

            $table->json('description');
            $table->json('summary')->nullable();

            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->json('phones')->nullable();
            $table->json('social_links')->nullable();

            $table->boolean('is_verified')->default(false);
            $table->boolean('is_featured')->default(false)->index();

            $table->string('employee_range')->nullable();

            // Snapshot of the primary address state, used by the public
            // state-listing page. States are shared reference data.
            $table->foreignId('state_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->timestamp('published_at')->index();

            $table->timestamps();
        });

        Schema::create('company_category_company_publication', function (Blueprint $table) {
            $table->foreignId('company_publication_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_category_id')->constrained()->cascadeOnDelete();

            $table->primary(['company_publication_id', 'company_category_id'], 'company_category_publication_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_category_company_publication');
        Schema::dropIfExists('company_publications');
    }
};
