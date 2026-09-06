<?php

use App\Enums\WordPressPostStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per generated WordPress article. Unlike company_contents —
     * which is deliberately one row per company, forever — this table grows,
     * because the feature is a recurring monthly generation.
     *
     * The table carries two jobs at once and needs no companion ledger:
     * it is the owner's publish history, AND it is the source the trend
     * picker checks to avoid handing the same company a topic twice
     * (hence the normalized topic column and its index).
     *
     * There is no stored quota column on purpose: the monthly limit is read
     * live from the plan feature at each attempt and compared against a
     * count of these rows, so an admin raising a plan's number takes effect
     * immediately with nothing to recompute.
     */
    public function up(): void
    {
        Schema::create('wordpress_content_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // One generation is one language, chosen by the owner from the
            // site's own active locales.
            $table->string('locale', 5);
            $table->string('mode', 32);
            $table->string('status', 32)->default(WordPressPostStatus::Queued->value);

            // Pipeline resume pointer, same convention as company_contents.
            $table->unsignedTinyInteger('step')->default(0);

            $table->string('topic');
            $table->string('topic_normalized');

            $table->string('title')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('focus_keyword')->nullable();
            $table->string('image_alt')->nullable();

            $table->unsignedBigInteger('wp_post_id')->nullable();
            $table->unsignedBigInteger('wp_media_id')->nullable();
            $table->string('wp_post_url')->nullable();

            $table->text('failure_reason')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // The monthly quota count.
            $table->index(['company_id', 'created_at']);
            // The trend-repeat check.
            $table->index(['company_id', 'topic_normalized']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wordpress_content_posts');
    }
};
