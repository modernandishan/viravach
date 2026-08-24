<?php

namespace App\Jobs;

use App\Ai\Agents\ViraBotAgent;
use App\Models\AiAssistant;
use App\Services\Chat\CompanyContextBuilder;
use App\Settings\ChatSettings;
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
     *
     * @param  string|null  $locale  site locale at the time the user sent
     *                               their message — selects the system prompt
     *                               and the fallback error string, since
     *                               app()->getLocale() inside the queue worker
     *                               does not reflect the request's locale
     */
    public function __construct(
        public int $conversationId,
        public Model $participant,
        public ?string $context = null,
        public ?string $locale = null,
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
            ->limit($this->historyLimit())
            ->get()
            ->reverse()
            ->values();

        $latestMessage = $recentMessages->pop();

        $history = $recentMessages
            ->map(fn (ChatMessage $message) => $this->isFromAssistant($message, $assistant)
                ? new AssistantMessage($message->body)
                : new UserMessage($message->body))
            ->all();

        $chatSettings = app(ChatSettings::class);

        $agent = new ViraBotAgent(
            $history,
            $this->resolveContext($conversation),
            $this->locale,
            $this->systemPromptOverride(),
            $chatSettings->ai_provider,
            $chatSettings->ai_model,
        );

        $response = $agent->prompt($latestMessage?->body ?? '');

        Chat::message(trim($response->text))->from($assistant)->to($conversation)->send();
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

        Chat::message(__('chat.ai_error', [], $this->locale))->from($assistant)->to($conversation)->type('ai_error')->send();
    }

    /**
     * Falls back to config('viravach_chat.ai_history_limit') when the admin
     * hasn't set an override in ChatSettings.
     */
    protected function historyLimit(): int
    {
        return app(ChatSettings::class)->ai_history_limit ?? (int) config('viravach_chat.ai_history_limit');
    }

    /**
     * The admin-editable system prompt for this locale, or null (meaning
     * ViraBotAgent falls back to config('viravach_chat.system_prompts')
     * itself) when ChatSettings has no non-blank override for it.
     */
    protected function systemPromptOverride(): ?string
    {
        if ($this->locale === null) {
            return null;
        }

        $prompt = app(ChatSettings::class)->system_prompts[$this->locale] ?? null;

        return blank($prompt) ? null : $prompt;
    }

    /**
     * For company-scoped AI conversations (data.company_id set), the context
     * is rebuilt from the company's published snapshot at reply time — never
     * cached at conversation creation — so edits that go through a new
     * approval/publication are reflected immediately. The builder reads
     * app()->getLocale(), which is switched to the sender's locale for the
     * duration of the build because the queue worker runs on the app default.
     */
    protected function resolveContext(Conversation $conversation): ?string
    {
        $companyId = $conversation->data['company_id'] ?? null;

        if ($companyId === null || ($conversation->data['type'] ?? null) !== 'ai') {
            return $this->context;
        }

        $originalLocale = app()->getLocale();

        if ($this->locale !== null) {
            app()->setLocale($this->locale);
        }

        try {
            $context = app(CompanyContextBuilder::class)->build((int) $companyId);
        } finally {
            app()->setLocale($originalLocale);
        }

        return $context !== '' ? $context : $this->context;
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
