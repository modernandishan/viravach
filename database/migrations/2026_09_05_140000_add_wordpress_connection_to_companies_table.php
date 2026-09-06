<?php

use App\Enums\WordPressConnectionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * State for the settings page's "Test connection" check against the
     * company's own WordPress site.
     *
     * wp_username is required because WordPress Application Passwords
     * authenticate over HTTP Basic Auth: the application password alone is
     * only half the credential, the WordPress login it was generated for is
     * the other half.
     *
     * wp_last_posts caches the three posts returned by the last successful
     * test so the confirmation survives a page reload without calling out to
     * the owner's site on every render; re-testing refreshes it.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('wp_username')->nullable();
            $table->string('wp_connection_status', 32)
                ->default(WordPressConnectionStatus::NotTested->value);
            $table->timestamp('wp_last_checked_at')->nullable();
            $table->json('wp_last_posts')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'wp_username',
                'wp_connection_status',
                'wp_last_checked_at',
                'wp_last_posts',
            ]);
        });
    }
};
