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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained(config('laravel-subscriptions.tables.plans'))->cascadeOnDelete();

            $table->unsignedBigInteger('amount');
            $table->string('status')->default('pending');
            $table->string('gateway')->default('zarinpal');
            $table->string('transaction_id')->nullable();
            $table->string('gateway_ref')->nullable();
            $table->string('failure_reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
