<?php

use App\Events\ChatConversationStarted;
use App\Models\Company;
use App\Models\User;
use App\Services\Chat\Exceptions\TranslationException;
use App\Services\Chat\TranslationService;
use App\Settings\ChatSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\Message as ChatMessage;
use Musonza\Chat\Models\Participation;

/**
 * Shared inbox for two audiences, filtered by conversation type:
 * - Support agents (role support/admin/super_admin — anyone SupportTransferService
 *   could have assigned) see their 'support' conversations, exactly as before.
 * - Company owners see 'company' conversations for every company they own —
 *   they reply AS the Company model (see CompanyChatService), not as
 *   themselves, since that's the actual musonza participant on those threads.
 *
 * A user who is both sees both lists merged. Access is authorized in mount()
 * rather than route middleware, since it now depends on owning a company too.
 */
new
#[Layout('layouts::landing')]
class extends Component
{
    protected const HISTORY_PAGE_SIZE = 30;

    /**
     * @var array<int, array{id: int, type: string, companyId: ?int, companyName: ?string, participantName: string, lastMessage: ?string, unreadCount: int, updatedAt: ?string}>
     */
    public array $conversations = [];

    public ?int $selectedConversationId = null;

    /**
     * Which kind of conversation is selected: 'support' or 'company'. Needed
     * because replying to a 'company' conversation must act AS that Company,
     * not as the authenticated user themselves.
     */
    public ?string $selectedType = null;

    public ?int $selectedCompanyId = null;

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
        $user = auth()->user();

        abort_unless($user->hasRole('support') || $user->companies()->exists(), 403);

        $this->loadConversations();
    }

    /**
     * Echo listeners — the PRIMARY real-time mechanism on this page.
     * Registered here instead of #[On] attributes because the channel names
     * embed ids only known at runtime. Listens on the user's own
     * ChatConversationStarted channel (new support assignments) AND on every
     * owned company's channel (new direct conversations from visitors — see
     * CompanyChatService, which broadcasts to the Company as recipient), plus
     * the musonza channel of every conversation already in the inbox at
     * mount. Conversations assigned/started AFTER mount get their row via
     * ChatConversationStarted, and their channel is subscribed by the
     * template's per-selection subscription once opened; until then their
     * messages only update the list via the 60s fallback poll.
     */
    public function getListeners(): array
    {
        $user = auth()->user();

        $listeners = [
            'echo-private:'.ChatConversationStarted::channelNameFor($user).',ChatConversationStarted' => 'refreshConversations',
        ];

        foreach ($user->companies as $company) {
            $listeners['echo-private:'.ChatConversationStarted::channelNameFor($company).',ChatConversationStarted'] = 'refreshConversations';
        }

        foreach (collect($this->conversations)->pluck('id') as $conversationId) {
            $listeners["echo-private:mc-chat-conversation.{$conversationId},.Musonza\\Chat\\Eventing\\MessageWasSent"] = 'onConversationMessage';
        }

        return $listeners;
    }

    /**
     * Public wrapper so Echo listeners (and only they) can trigger the
     * protected list rebuild.
     */
    public function refreshConversations(): void
    {
        $this->loadConversations();
    }

    /**
     * Echo handler for musonza's MessageWasSent: reuses the exact refresh
     * paths the old poll called — pollForReply() when the push is for the
     * open conversation, refreshConversations() (unread badges / last
     * message) when it is for any other one in the inbox.
     *
     * @param  array{message?: array{conversation_id?: int}}  $event
     */
    public function onConversationMessage(array $event = []): void
    {
        $conversationId = (int) data_get($event, 'message.conversation_id', 0);

        if ($conversationId !== 0 && $conversationId === $this->selectedConversationId) {
            $this->pollForReply();

            return;
        }

        $this->refreshConversations();
    }

    /**
     * FALLBACK ONLY, not the primary mechanism (that's the Echo listeners
     * above): runs on a slow 60s wire:poll to recover anything a missed or
     * dropped WebSocket event left behind — both the open conversation and
     * the inbox list.
     */
    public function fallbackSync(): void
    {
        $this->pollForReply();
        $this->loadConversations();
    }

    public function selectConversation(int $conversationId, string $type, ?int $companyId = null): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        if ($type === 'company') {
            abort_unless(
                $companyId !== null
                    && auth()->user()->companies()->whereKey($companyId)->exists()
                    && $this->belongsToCompany($conversation, $companyId),
                403,
            );
        } else {
            abort_unless($this->belongsToAgent($conversation, auth()->user()), 403);
        }

        $this->selectedConversationId = $conversationId;
        $this->selectedType = $type;
        $this->selectedCompanyId = $companyId;

        $actingAs = $this->actingAs();

        $this->messages = $this->fetchMessages($conversation, $actingAs);
        $this->sendError = null;

        Chat::conversation($conversation)->setParticipant($actingAs)->readAll();

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

        $conversation = $this->resolveSelectedConversation();

        if (! $conversation) {
            return;
        }

        $user = auth()->user();

        if (RateLimiter::tooManyAttempts($this->sendRateLimitKey($user), $this->sendRateLimitMax())) {
            $this->sendError = __('chat.rate_limited_send');

            return;
        }

        RateLimiter::hit($this->sendRateLimitKey($user), 60);

        $body = strip_tags(trim($this->body));

        if ($body === '') {
            return;
        }

        $actingAs = $this->actingAs();

        $message = Chat::message($body)->from($actingAs)->to($conversation)->send();

        $this->messages[] = $this->presentMessage($message->load('participation.messageable'), $actingAs);
        $this->body = '';

        $this->loadConversations();
    }

    /**
     * Fetches messages newer than the last one shown in the open
     * conversation. Triggered primarily by Echo MessageWasSent pushes (via
     * onConversationMessage()); the 60s fallbackSync() poll is the only
     * remaining timer that reaches it.
     */
    public function pollForReply(): void
    {
        $conversation = $this->resolveSelectedConversation();

        if (! $conversation) {
            return;
        }

        $actingAs = $this->actingAs();

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
            $this->messages[] = $this->presentMessage($newMessage, $actingAs);
        }

        Chat::conversation($conversation)->setParticipant($actingAs)->readAll();
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

        $user = auth()->user();

        if (RateLimiter::tooManyAttempts($this->translateRateLimitKey($user), $this->translateRateLimitMax())) {
            $this->translations[$messageId] = [
                'visible' => true,
                'text' => null,
                'error' => __('chat.rate_limited_translate'),
            ];

            return;
        }

        RateLimiter::hit($this->translateRateLimitKey($user), 60);

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
        $user = auth()->user();

        $rows = collect();

        if ($user->hasRole('support')) {
            $paginator = Chat::conversations()->setParticipant($user)->isDirect()->perPage(100)->get();

            $rows = $rows->merge(
                collect($paginator->items())
                    ->filter(fn (Participation $participation) => ($participation->conversation->data['type'] ?? null) === 'support')
                    ->map(fn (Participation $participation) => $this->presentSupportConversation($participation, $user))
            );
        }

        $companyIds = $user->companies()->pluck('id');

        if ($companyIds->isNotEmpty()) {
            $companyMorphClass = (new Company)->getMorphClass();

            $companyConversations = Conversation::query()
                ->whereHas('participants', function ($query) use ($companyMorphClass, $companyIds) {
                    $query->where('messageable_type', $companyMorphClass)
                        ->whereIn('messageable_id', $companyIds);
                })
                ->get()
                ->filter(fn (Conversation $conversation) => ($conversation->data['type'] ?? null) === 'company');

            $rows = $rows->merge(
                $companyConversations->map(fn (Conversation $conversation) => $this->presentCompanyConversation($conversation))
            );
        }

        $this->conversations = $rows->sortByDesc('updatedAtSort')->values()
            ->map(fn (array $row) => collect($row)->except('updatedAtSort')->all())
            ->all();
    }

    /**
     * @return array{id: int, type: string, companyId: ?int, companyName: ?string, participantName: string, lastMessage: ?string, unreadCount: int, updatedAt: ?string, updatedAtSort: ?string}
     */
    protected function presentSupportConversation(Participation $participation, User $agent): array
    {
        $conversation = $participation->conversation;

        $otherParticipant = $conversation->participants->first(
            fn (Participation $p) => ! ($p->messageable_type === $agent->getMorphClass() && (int) $p->messageable_id === $agent->getKey())
        );

        return [
            'id' => $conversation->id,
            'type' => 'support',
            'companyId' => null,
            'companyName' => null,
            'participantName' => (string) ($otherParticipant?->messageable?->getParticipantDetails()['name'] ?? ''),
            'lastMessage' => $conversation->last_message?->body,
            'unreadCount' => Chat::conversation($conversation)->setParticipant($agent)->unreadCount(),
            'updatedAt' => $conversation->updated_at?->format('Y/m/d H:i'),
            'updatedAtSort' => $conversation->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{id: int, type: string, companyId: ?int, companyName: ?string, participantName: string, lastMessage: ?string, unreadCount: int, updatedAt: ?string, updatedAtSort: ?string}
     */
    protected function presentCompanyConversation(Conversation $conversation): array
    {
        $companyMorphClass = (new Company)->getMorphClass();

        $companyParticipant = $conversation->participants->first(
            fn (Participation $p) => $p->messageable_type === $companyMorphClass
        );

        $company = $companyParticipant?->messageable;

        $otherParticipant = $conversation->participants->first(
            fn (Participation $p) => $p->messageable_type !== $companyMorphClass
        );

        return [
            'id' => $conversation->id,
            'type' => 'company',
            'companyId' => $company?->id,
            'companyName' => (string) ($company?->publication?->name ?? $company?->name ?? ''),
            'participantName' => (string) ($otherParticipant?->messageable?->getParticipantDetails()['name'] ?? ''),
            'lastMessage' => $conversation->last_message?->body,
            'unreadCount' => $company ? Chat::conversation($conversation)->setParticipant($company)->unreadCount() : 0,
            'updatedAt' => $conversation->updated_at?->format('Y/m/d H:i'),
            'updatedAtSort' => $conversation->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Resolves who the authenticated user is acting AS for the currently
     * selected conversation: themselves for a 'support' row, or the specific
     * owned Company for a 'company' row (owners reply AS the company).
     */
    protected function actingAs(): Model
    {
        if ($this->selectedType === 'company' && $this->selectedCompanyId !== null) {
            return Company::findOrFail($this->selectedCompanyId);
        }

        return auth()->user();
    }

    /**
     * Only returns the selected conversation if it still actually matches
     * the selected type/company — guards against a tampered selection
     * bypassing selectConversation()'s own authorization.
     */
    protected function resolveSelectedConversation(): ?Conversation
    {
        if ($this->selectedConversationId === null) {
            return null;
        }

        $conversation = Conversation::find($this->selectedConversationId);

        if (! $conversation) {
            return null;
        }

        if ($this->selectedType === 'company') {
            return $this->selectedCompanyId !== null && $this->belongsToCompany($conversation, $this->selectedCompanyId)
                ? $conversation
                : null;
        }

        return $this->belongsToAgent($conversation, auth()->user()) ? $conversation : null;
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

    protected function belongsToCompany(Conversation $conversation, int $companyId): bool
    {
        if (($conversation->data['type'] ?? null) !== 'company' || ($conversation->data['company_id'] ?? null) !== $companyId) {
            return false;
        }

        return $conversation->participants()
            ->where('messageable_type', (new Company)->getMorphClass())
            ->where('messageable_id', $companyId)
            ->exists();
    }

    /**
     * @return array<int, array{id: int, body: string, senderName: string, isOwn: bool, time: ?string, type: string}>
     */
    protected function fetchMessages(Conversation $conversation, Model $actingAs): array
    {
        $paginator = Chat::conversation($conversation)
            ->setParticipant($actingAs)
            ->setCursorPaginationParams(['sorting' => 'desc'])
            ->perPage(self::HISTORY_PAGE_SIZE)
            ->getMessagesWithCursor();

        return collect($paginator->items())
            ->reverse()
            ->values()
            ->map(fn (ChatMessage $message) => $this->presentMessage($message, $actingAs))
            ->all();
    }

    /**
     * @return array{id: int, body: string, senderName: string, isOwn: bool, time: ?string, type: string}
     */
    protected function presentMessage(ChatMessage $message, Model $actingAs): array
    {
        $isOwn = $message->participation->messageable_type === $actingAs->getMorphClass()
            && (int) $message->participation->messageable_id === $actingAs->getKey();

        return [
            'id' => $message->id,
            'body' => $message->body,
            'senderName' => (string) ($message->sender['name'] ?? ''),
            'isOwn' => $isOwn,
            'time' => $message->created_at?->format('H:i'),
            'type' => $message->type,
        ];
    }

    protected function sendRateLimitKey(User $user): string
    {
        return 'chat-send:'.$user->getMorphClass().':'.$user->getKey();
    }

    protected function translateRateLimitKey(User $user): string
    {
        return 'chat-translate:'.$user->getMorphClass().':'.$user->getKey();
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
                                    wire:click="selectConversation({{ $conversation['id'] }}, '{{ $conversation['type'] }}'{{ $conversation['companyId'] !== null ? ', '.$conversation['companyId'] : '' }})"
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
                                        @if($conversation['companyName'])
                                            <div class="text-muted fs-9 text-truncate">{{ $conversation['companyName'] }}</div>
                                        @endif
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
                            {{--
                                FALLBACK ONLY, not the primary mechanism: Echo listeners
                                (getListeners()) drive updates; this slow poll recovers
                                missed/dropped WebSocket events. The x-init subscription is
                                keyed per selection so conversations assigned after mount
                                are covered too (dedup lives in listenToChatConversation —
                                resources/js/echo.js).
                            --}}
                            <div
                                class="scroll-y mh-400px flex-grow-1 mb-3"
                                wire:poll.60s="fallbackSync"
                                wire:key="support-chat-messages-{{ $selectedConversationId }}"
                                x-data
                                {{-- typeof guard: if the bundle failed to load, degrade to the fallback poll instead of throwing mid-update. --}}
                                x-init="typeof window.listenToChatConversation === 'function'
                                    && window.listenToChatConversation({{ (int) $selectedConversationId }}, (e) => $wire.onConversationMessage(e))"
                            >
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
