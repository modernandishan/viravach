<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            // Real columns, not JSON inside the conversation's data field:
            // tickets are listed, filtered and looked up by these directly.
            $table->string('reference_number')->unique();
            $table->string('subject');
            $table->string('status')->index()->default('open');

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // The underlying musonza/chat conversation (data.type = 'ticket',
            // private — NOT direct, so every staff member can join without
            // the one-conversation-per-pair constraint).
            $table->unsignedBigInteger('conversation_id');
            $table->foreign('conversation_id')->references('id')->on('chat_conversations')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
