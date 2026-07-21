<?php

namespace App\Jobs;

use App\Ai\Agents\ViraBotAgent;
use App\Models\AiAssistant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\Message as ChatMessage;
use Throwable;

class GenerateAiChatReply implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $conversationId,
        public Model $participant,
        public ?string $context = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $conversation = Conversation::findOrFail($this->conversationId);
        $assistant = AiAssistant::query()->firstOrFail();

        $recentMessages = $conversation->messages()
            ->with('participation')
            ->latest('id')
            ->limit((int) config('viravach_chat.ai_history_limit'))
            ->get()
            ->reverse()
            ->values();

        $latestMessage = $recentMessages->pop();

        $history = $recentMessages
            ->map(fn (ChatMessage $message) => $this->isFromAssistant($message, $assistant)
                ? new AssistantMessage($message->body)
                : new UserMessage($message->body))
            ->all();

        $agent = new ViraBotAgent($history, $this->context);

        $response = $agent->prompt($latestMessage?->body ?? '');

        Chat::message($response->text)->from($assistant)->to($conversation)->send();
    }

    /**
     * Handle a permanently failed job after retries are exhausted.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Failed to generate AI chat reply.', [
            'conversation_id' => $this->conversationId,
            'exception' => $exception,
        ]);

        $conversation = Conversation::find($this->conversationId);
        $assistant = AiAssistant::query()->first();

        if (! $conversation || ! $assistant) {
            return;
        }

        Chat::message(__('chat.ai_error'))->from($assistant)->to($conversation)->send();
    }

    /**
     * Determine whether a chat message was sent by the AiAssistant participant.
     */
    protected function isFromAssistant(ChatMessage $message, AiAssistant $assistant): bool
    {
        return $message->participation?->messageable_type === $assistant->getMorphClass()
            && (int) $message->participation?->messageable_id === $assistant->getKey();
    }
}
