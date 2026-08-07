<?php

namespace App\Livewire\Concerns;

use App\Services\Chat\ChatParticipantResolver;
use App\Services\Chat\Exceptions\TranslationException;
use App\Services\Chat\TranslationService;
use App\Settings\ChatSettings;
use App\Support\LocalizedDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\Message as ChatMessage;

/**
 * Shared participant resolution, message presentation, history fetching, and
 * inline-translation logic used by the header chat drawer and the full-page
 * chat client (resources/views/pages/dashboard/⚡chat.blade.php).
 *
 * Consuming components must implement locateMessageBody(int $messageId): ?string,
 * returning the body of a previously presented message from wherever they
 * keep their message arrays.
 */
trait InteractsWithChatMessages
{
    /**
     * Number of most recent messages loaded per conversation when opened.
     */
    protected const HISTORY_PAGE_SIZE = 30;

    /**
     * Per-render translation state keyed by message id.
     *
     * @var array<int, array{visible: bool, text: ?string, error: ?string}>
     */
    public array $translations = [];

    /**
     * Memoized for the lifetime of this request only — never hydrated
     * across requests, so it can't leak one participant's identity into
     * another request's resolution.
     */
    protected ?Model $resolvedParticipant = null;

    /**
     * Toggle the inline translation of a single message, fetching it via
     * TranslationService the first time. Never persisted — purely render state.
     */
    public function toggleTranslation(int $messageId): void
    {
        $existing = $this->translations[$messageId] ?? null;

        if ($existing && ($existing['visible'] ?? false)) {
            $this->translations[$messageId]['visible'] = false;

            return;
        }

        if ($existing && $existing['text'] !== null) {
            $this->translations[$messageId]['visible'] = true;

            return;
        }

        $participant = $this->participant();

        if (RateLimiter::tooManyAttempts($this->translateRateLimitKey($participant), $this->translateRateLimitMax())) {
            $this->translations[$messageId] = [
                'visible' => true,
                'text' => null,
                'error' => __('chat.rate_limited_translate'),
            ];

            return;
        }

        RateLimiter::hit($this->translateRateLimitKey($participant), 60);

        $body = $this->locateMessageBody($messageId);

        if ($body === null) {
            return;
        }

        try {
            $translated = app(TranslationService::class)->translate($body, app()->getLocale());

            $this->translations[$messageId] = ['visible' => true, 'text' => $translated, 'error' => null];
        } catch (TranslationException) {
            $this->translations[$messageId] = ['visible' => true, 'text' => null, 'error' => __('chat.translation_failed')];
        }
    }

    protected function participant(): Model
    {
        return $this->resolvedParticipant ??= app(ChatParticipantResolver::class)->resolve();
    }

    /**
     * @return array<int, array{id: int, body: string, senderName: string, senderType: ?string, senderAvatar: ?string, isOwn: bool, time: ?string, type: string}>
     */
    protected function fetchMessages(Conversation $conversation, Model $participant): array
    {
        $paginator = Chat::conversation($conversation)
            ->setParticipant($participant)
            ->setCursorPaginationParams(['sorting' => 'desc'])
            ->perPage(self::HISTORY_PAGE_SIZE)
            ->getMessagesWithCursor();

        return collect($paginator->items())
            ->reverse()
            ->values()
            ->map(fn (ChatMessage $message) => $this->presentMessage($message, $participant))
            ->all();
    }

    /**
     * @return array{id: int, body: string, senderName: string, senderType: ?string, senderAvatar: ?string, isOwn: bool, time: ?string, type: string}
     */
    protected function presentMessage(ChatMessage $message, Model $participant): array
    {
        $isOwn = $message->participation->messageable_type === $participant->getMorphClass()
            && (int) $message->participation->messageable_id === $participant->getKey();

        return [
            'id' => $message->id,
            'body' => $message->body,
            'senderName' => (string) ($message->sender['name'] ?? ''),
            'senderType' => $message->sender['type'] ?? null,
            'senderAvatar' => $message->sender['avatar_url'] ?? null,
            'isOwn' => $isOwn,
            'time' => LocalizedDate::format($message->created_at, LocalizedDate::FORMAT_DATETIME),
            'type' => $message->type,
        ];
    }

    protected function sendRateLimitKey(Model $participant): string
    {
        return 'chat-send:'.$participant->getMorphClass().':'.$participant->getKey();
    }

    protected function translateRateLimitKey(Model $participant): string
    {
        return 'chat-translate:'.$participant->getMorphClass().':'.$participant->getKey();
    }

    /**
     * Falls back to the historical hardcoded limit (10/min) when the admin
     * hasn't set an override in ChatSettings.
     */
    protected function sendRateLimitMax(): int
    {
        return app(ChatSettings::class)->rate_limit_messages_per_minute ?? 10;
    }

    /**
     * Falls back to the historical hardcoded limit (20/min) when the admin
     * hasn't set an override in ChatSettings.
     */
    protected function translateRateLimitMax(): int
    {
        return app(ChatSettings::class)->rate_limit_translations_per_minute ?? 20;
    }
}
