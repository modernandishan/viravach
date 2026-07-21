<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('laravel-subscriptions.tables.subscriptions'), function (Blueprint $table): void {
            $table->id();

            $table->morphs('subscriber');
            $table->foreignIdFor(config('laravel-subscriptions.models.plan'));
            $table->json('name');
            // Slugs are only unique per-subscriber (see HasSlug's
            // extraScope()), not globally, so no unique index here.
            $table->string('slug');
            $table->json('description')->nullable();
            $table->string('timezone')->nullable();

            $table->dateTime('trial_ends_at')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('canceled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Marks subscriptions created via the one-time 14-day Pro Plus
            // trial grant — see
            // App\Services\CompanySubscriptionService::revertExpiredTrials().
            $table->boolean('is_trial')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('laravel-subscriptions.tables.subscriptions'));
    }
};
