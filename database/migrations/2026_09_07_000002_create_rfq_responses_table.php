<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chat-style replies from the company's side of one RFQ. Kept as its own
     * table rather than a musonza/chat conversation (the ticket system's
     * choice) because the buyer is not a User and never logs in — there is no
     * second participant to model, only an outgoing thread.
     *
     * user_id is nullable so a reply survives the replying staff member's
     * account being deleted, and so system-generated entries can exist
     * without inventing a placeholder user.
     */
    public function up(): void
    {
        Schema::create('rfq_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_responses');
    }
};
