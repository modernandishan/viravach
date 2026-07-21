<?php

use App\Models\User;
use App\Services\Chat\Exceptions\TranslationException;
use App\Services\Chat\TranslationService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\Message as ChatMessage;
use Musonza\Chat\Models\Participation;

new
#[Layout('layouts::landing')]
class extends Component
{
    protected const HISTORY_PAGE_SIZE = 30;

    /**
     * @var array<int, array{id: int, participantName: string, lastMessage: ?string, unreadCount: int, updatedAt: ?string}>
     */
    public array $conversations = [];

    public ?int $selectedConversationId = null;

    /**
     * @var array<int, array{id: int, body: string, senderName: string, isOwn: bool, time: ?string, type: string}>
     */
    public array $messages = [];

    public string $body = '';

    public ?string $sendError = null;

    /**
     * @var array<int, array{visible: bool, text: ?string, error: ?string}>
     */
    public array $translations = [];

    public function mount(): void
    {
        $this->loadConversations();
    }

    public function selectConversation(int $conversationId): void
    {
        $agent = auth()->user();
        $conversation = Conversation::findOrFail($conversationId);

        abort_unless($this->belongsToAgent($conversation, $agent), 403);

        $this->selectedConversationId = $conversationId;
        $this->messages = $this->fetchMessages($conversation, $agent);
        $this->sendError = null;

        Chat::conversation($conversation)->setParticipant($agent)->readAll();

        $this->loadConversations();
    }

    public function sendMessage(): void
    {
        $this->sendError = null;

        $this->validate(
            ['body' => ['required', 'string', 'max:2000']],
            [
                'body.required' => __('chat.message_required'),
                'body.max' => __('chat.message_too_long'),
            ],
        );

        $agent = auth()->user();
        $conversation = $this->resolveSelectedConversation($agent);

        if (! $conversation) {
            return;
        }

        if (RateLimiter::tooManyAttempts($this->sendRateLimitKey($agent), 10)) {
            $this->sendError = __('chat.rate_limited_send');

            return;
        }

        RateLimiter::hit($this->sendRateLimitKey($agent), 60);

        $body = strip_tags(trim($this->body));

        if ($body === '') {
            return;
        }

        $message = Chat::message($body)->from($agent)->to($conversation)->send();

        $this->messages[] = $this->presentMessage($message->load('participation.messageable'), $agent);
        $this->body = '';

        $this->loadConversations();
    }

    /**
     * Polled every 5s while a conversation is open, since agent replies (and
     * new customer messages) can arrive anytime — no push channel until
     * Reverb lands.
     */
    public function pollForReply(): void
    {
        $agent = auth()->user();
        $conversation = $this->resolveSelectedConversation($agent);

        if (! $conversation) {
            return;
        }

        $lastKnownId = (int) (collect($this->messages)->max('id') ?? 0);

        $newMessages = $conversation->messages()
            ->with('participation.messageable')
            ->where('id', '>', $lastKnownId)
            ->orderBy('id')
            ->get();

        if ($newMessages->isEmpty()) {
            return;
        }

        foreach ($newMessages as $newMessage) {
            $this->messages[] = $this->presentMessage($newMessage, $agent);
        }

        Chat::conversation($conversation)->setParticipant($agent)->readAll();
        $this->loadConversations();
    }

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

        $agent = auth()->user();

        if (RateLimiter::tooManyAttempts($this->translateRateLimitKey($agent), 20)) {
            $this->translations[$messageId] = [
                'visible' => true,
                'text' => null,
                'error' => __('chat.rate_limited_translate'),
            ];

            return;
        }

        RateLimiter::hit($this->translateRateLimitKey($agent), 60);

        $message = collect($this->messages)->firstWhere('id', $messageId);

        if (! $message) {
            return;
        }

        try {
            $translated = app(TranslationService::class)->translate($message['body'], app()->getLocale());

            $this->translations[$messageId] = ['visible' => true, 'text' => $translated, 'error' => null];
        } catch (TranslationException) {
            $this->translations[$messageId] = ['visible' => true, 'text' => null, 'error' => __('chat.translation_failed')];
        }
    }

    protected function loadConversations(): void
    {
        $agent = auth()->user();

        $paginator = Chat::conversations()->setParticipant($agent)->isDirect()->perPage(100)->get();

        $this->conversations = collect($paginator->items())
            ->filter(fn (Participation $participation) => ($participation->conversation->data['type'] ?? null) === 'support')
            ->map(fn (Participation $participation) => $this->presentConversation($participation, $agent))
            ->values()
            ->all();
    }

    /**
     * @return array{id: int, participantName: string, lastMessage: ?string, unreadCount: int, updatedAt: ?string}
     */
    protected function presentConversation(Participation $participation, User $agent): array
    {
        $conversation = $participation->conversation;

        $otherParticipant = $conversation->participants->first(
            fn (Participation $p) => ! ($p->messageable_type === $agent->getMorphClass() && (int) $p->messageable_id === $agent->getKey())
        );

        return [
            'id' => $conversation->id,
            'participantName' => (string) ($otherParticipant?->messageable?->getParticipantDetails()['name'] ?? ''),
            'lastMessage' => $conversation->last_message?->body,
            'unreadCount' => Chat::conversation($conversation)->setParticipant($agent)->unreadCount(),
            'updatedAt' => $conversation->updated_at?->format('Y/m/d H:i'),
        ];
    }

    /**
     * Only returns the selected conversation if it's actually a support
     * conversation this agent participates in — guards against a tampered
     * selectedConversationId bypassing selectConversation()'s own check.
     */
    protected function resolveSelectedConversation(User $agent): ?Conversation
    {
        if ($this->selectedConversationId === null) {
            return null;
        }

        $conversation = Conversation::find($this->selectedConversationId);

        if (! $conversation || ! $this->belongsToAgent($conversation, $agent)) {
            return null;
        }

        return $conversation;
    }

    protected function belongsToAgent(Conversation $conversation, User $agent): bool
    {
        if (($conversation->data['type'] ?? null) !== 'support') {
            return false;
        }

        return $conversation->participants()
            ->where('messageable_type', $agent->getMorphClass())
            ->where('messageable_id', $agent->getKey())
            ->exists();
    }

    /**
     * @return array<int, array{id: int, body: string, senderName: string, isOwn: bool, time: ?string, type: string}>
     */
    protected function fetchMessages(Conversation $conversation, User $agent): array
    {
        $paginator = Chat::conversation($conversation)
            ->setParticipant($agent)
            ->setCursorPaginationParams(['sorting' => 'desc'])
            ->perPage(self::HISTORY_PAGE_SIZE)
            ->getMessagesWithCursor();

        return collect($paginator->items())
            ->reverse()
            ->values()
            ->map(fn (ChatMessage $message) => $this->presentMessage($message, $agent))
            ->all();
    }

    /**
     * @return array{id: int, body: string, senderName: string, isOwn: bool, time: ?string, type: string}
     */
    protected function presentMessage(ChatMessage $message, User $agent): array
    {
        $isOwn = $message->participation->messageable_type === $agent->getMorphClass()
            && (int) $message->participation->messageable_id === $agent->getKey();

        return [
            'id' => $message->id,
            'body' => $message->body,
            'senderName' => (string) ($message->sender['name'] ?? ''),
            'isOwn' => $isOwn,
            'time' => $message->created_at?->format('H:i'),
            'type' => $message->type,
        ];
    }

    protected function sendRateLimitKey(User $agent): string
    {
        return 'chat-send:'.$agent->getMorphClass().':'.$agent->getKey();
    }

    protected function translateRateLimitKey(User $agent): string
    {
        return 'chat-translate:'.$agent->getMorphClass().':'.$agent->getKey();
    }

    public function render()
    {
        return $this->view()->title(__('chat.inbox_title').' | '.__('globals.viravach'));
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">
        <div class="card card-flush">
            <div class="card-header align-items-center py-5">
                <div class="card-title">
                    <h2>{{ __('chat.inbox_title') }}</h2>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="row g-0" style="min-height: 500px;">
                    <!--begin::Conversation list-->
                    <div class="col-12 col-md-4 border-end">
                        <div class="scroll-y mh-500px">
                            @forelse($conversations as $conversation)
                                <!--begin::Conversation item-->
                                <div
                                    class="d-flex align-items-center py-3 px-3 border-bottom cursor-pointer {{ $selectedConversationId === $conversation['id'] ? 'bg-light-primary' : '' }}"
                                    wire:click="selectConversation({{ $conversation['id'] }})"
                                    wire:key="support-conversation-{{ $conversation['id'] }}"
                                >
                                    <div class="symbol symbol-40px me-3">
                                        <span class="symbol-label bg-light-info text-info fw-bold">
                                            {{ \Illuminate\Support\Str::substr($conversation['participantName'], 0, 1) }}
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-gray-900 text-truncate">{{ $conversation['participantName'] }}</span>
                                            @if($conversation['unreadCount'] > 0)
                                                <span class="badge badge-circle badge-primary">{{ $conversation['unreadCount'] }}</span>
                                            @endif
                                        </div>
                                        <div class="text-muted fs-8 text-truncate">
                                            {{ $conversation['lastMessage'] ?? __('chat.no_message_preview') }}
                                        </div>
                                        <div class="text-muted fs-9">{{ $conversation['updatedAt'] }}</div>
                                    </div>
                                </div>
                                <!--end::Conversation item-->
                            @empty
                                <div class="text-center text-muted fs-6 py-10 px-5">
                                    {{ __('chat.inbox_empty') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <!--end::Conversation list-->
                    <!--begin::Thread-->
                    <div class="col-12 col-md-8 ps-md-5 d-flex flex-column">
                        @if($selectedConversationId === null)
                            <div class="d-flex align-items-center justify-content-center flex-grow-1 text-muted fs-6">
                                {{ __('chat.select_conversation') }}
                            </div>
                        @else
                            <div class="scroll-y mh-400px flex-grow-1 mb-3" wire:poll.5s="pollForReply">
                                @foreach($messages as $msg)
                                    @include('components.chat-elements.message-item', ['msg' => $msg, 'translations' => $translations])
                                @endforeach
                            </div>

                            @error('body')
                                <div class="text-danger fs-8 mb-2">{{ $message }}</div>
                            @enderror
                            @if($sendError)
                                <div class="text-danger fs-8 mb-2">{{ $sendError }}</div>
                            @endif

                            <textarea class="form-control mb-3" rows="2" wire:model="body" wire:keydown.enter.prevent="sendMessage" placeholder="{{ __('chat.placeholder') }}"></textarea>
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-primary" type="button" wire:click="sendMessage" wire:target="sendMessage" wire:loading.attr="disabled">
                                    {{ __('chat.send') }}
                                </button>
                            </div>
                        @endif
                    </div>
                    <!--end::Thread-->
                </div>
            </div>
        </div>
    </div>
</div>
