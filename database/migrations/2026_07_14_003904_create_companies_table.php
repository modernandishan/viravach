<?php

use App\Enums\CompanyReviewStatus;
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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

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

            $table->string('review_status')->default(CompanyReviewStatus::PendingReview->value)->index();
            $table->text('rejection_reason')->nullable();

            $table->boolean('is_verified')->default(false);
            $table->boolean('is_featured')->default(false)->index();

            $table->string('employee_range')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->timestamp('reviewed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
