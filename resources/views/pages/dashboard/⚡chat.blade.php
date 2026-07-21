<?php

use App\Livewire\Concerns\InteractsWithChatMessages;
use App\Services\Chat\AiChatService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\Participation;

new
#[Layout('layouts::landing')]
class extends Component
{
    use InteractsWithChatMessages;

    /**
     * Stop polling for an AI reply after this many seconds even if none
     * arrives, so a stuck queue worker doesn't leave the typing indicator
     * forever.
     */
    protected const POLL_TIMEOUT_SECONDS = 60;

    public string $search = '';

    /**
     * @var array<int, array{conversationId: int, name: string, type: ?string, lastMessage: ?string, relativeTime: ?string, sortTime: int, unreadCount: int}>
     */
    public array $contacts = [];

    public ?int $aiConversationId = null;

    public ?int $selectedConversationId = null;

    public bool $isAiSelected = false;

    /**
     * @var array<int, array{id: int, body: string, senderName: string, senderType: ?string, isOwn: bool, time: ?string, type: string}>
     */
    public array $messages = [];

    public bool $awaitingReply = false;

    public ?int $pollDeadline = null;

    public string $body = '';

    public ?string $sendError = null;

    /**
     * Guarantees the AiAssistant conversation exists (so it can always be
     * pinned first in the contacts list), builds the contacts list, then
     * defaults to opening ViraBot's conversation.
     */
    public function mount(): void
    {
        $participant = $this->participant();

        $this->loadAiConversation($participant);
        $this->loadContacts($participant);
        $this->selectConversation((int) $this->aiConversationId);
    }

    public function updatedSearch(): void
    {
        $this->loadContacts($this->participant());
    }

    public function selectConversation(int $conversationId): void
    {
        $participant = $this->participant();
        $conversation = Conversation::findOrFail($conversationId);

        abort_unless($this->participantBelongsTo($conversation, $participant), 403);

        $this->selectedConversationId = $conversationId;
        $this->isAiSelected = $conversationId === $this->aiConversationId;
        $this->messages = $this->fetchMessages($conversation, $participant);
        $this->sendError = null;
        $this->awaitingReply = false;

        Chat::conversation($conversation)->setParticipant($participant)->readAll();

        $this->loadContacts($participant);
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

        if ($this->selectedConversationId === null) {
            return;
        }

        $participant = $this->participant();

        if (RateLimiter::tooManyAttempts($this->sendRateLimitKey($participant), 10)) {
            $this->sendError = __('chat.rate_limited_send');

            return;
        }

        RateLimiter::hit($this->sendRateLimitKey($participant), 60);

        $body = strip_tags(trim($this->body));

        if ($body === '') {
            return;
        }

        if ($this->isAiSelected) {
            $message = app(AiChatService::class)->sendUserMessage($participant, $body);

            $this->awaitingReply = true;
            $this->pollDeadline = now()->addSeconds(self::POLL_TIMEOUT_SECONDS)->timestamp;
        } else {
            $conversation = Conversation::findOrFail($this->selectedConversationId);
            $message = Chat::message($body)->from($participant)->to($conversation)->send();
        }

        $this->messages[] = $this->presentMessage($message->load('participation.messageable'), $participant);
        $this->body = '';

        $this->loadContacts($participant);
    }

    /**
     * Polled while awaiting an AI reply (3s, stops itself once it lands or
     * times out — see the template), and every 5s while any conversation is
     * open, since replies from a human counterpart can arrive anytime with
     * no push channel until Reverb lands.
     */
    public function pollForReply(): void
    {
        if ($this->selectedConversationId === null) {
            return;
        }

        if ($this->isAiSelected && $this->awaitingReply && $this->pollDeadline !== null && now()->timestamp >= $this->pollDeadline) {
            $this->awaitingReply = false;

            return;
        }

        $participant = $this->participant();
        $conversation = Conversation::findOrFail($this->selectedConversationId);

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
            $this->messages[] = $this->presentMessage($newMessage, $participant);
        }

        if ($this->isAiSelected) {
            $this->awaitingReply = false;
        }

        Chat::conversation($conversation)->setParticipant($participant)->readAll();
        $this->loadContacts($participant);
    }

    /**
     * Locates the body of a previously presented message for
     * InteractsWithChatMessages::toggleTranslation().
     */
    protected function locateMessageBody(int $messageId): ?string
    {
        $message = collect($this->messages)->firstWhere('id', $messageId);

        return $message['body'] ?? null;
    }

    protected function loadAiConversation(Model $participant): void
    {
        $conversation = app(AiChatService::class)->startOrGetConversation($participant);

        $this->aiConversationId = $conversation->id;
    }

    /**
     * Builds the right-column contacts list: the AiAssistant pinned first
     * (always, regardless of the search box), then every other participant
     * this participant has ever conversed with, most recent message first
     * and filtered by the search box.
     */
    protected function loadContacts(Model $participant): void
    {
        $paginator = Chat::conversations()->setParticipant($participant)->isDirect()->perPage(100)->get();

        $all = collect($paginator->items())
            ->map(fn (Participation $participation) => $this->presentContact($participation, $participant))
            ->filter()
            ->values();

        $ai = $all->firstWhere('conversationId', $this->aiConversationId);
        $others = $all->reject(fn (array $contact) => $contact['conversationId'] === $this->aiConversationId)
            ->sortByDesc('sortTime')
            ->values();

        $search = trim($this->search);

        if ($search !== '') {
            $others = $others->filter(
                fn (array $contact) => mb_stripos($contact['name'], $search) !== false
            )->values();
        }

        $this->contacts = $ai ? [$ai, ...$others->all()] : $others->all();
    }

    /**
     * @return ?array{conversationId: int, name: string, type: ?string, lastMessage: ?string, relativeTime: ?string, sortTime: int, unreadCount: int}
     */
    protected function presentContact(Participation $participation, Model $participant): ?array
    {
        $conversation = $participation->conversation;

        $other = $conversation->participants->first(
            fn (Participation $p) => ! ($p->messageable_type === $participant->getMorphClass() && (int) $p->messageable_id === $participant->getKey())
        );

        if (! $other || ! $other->messageable) {
            return null;
        }

        $details = $other->messageable->getParticipantDetails();

        return [
            'conversationId' => $conversation->id,
            'name' => (string) ($details['name'] ?? ''),
            'type' => $details['type'] ?? null,
            'lastMessage' => $conversation->last_message?->body,
            'relativeTime' => $conversation->updated_at?->locale(app()->getLocale())->diffForHumans(),
            'sortTime' => $conversation->updated_at?->timestamp ?? 0,
            'unreadCount' => Chat::conversation($conversation)->setParticipant($participant)->unreadCount(),
        ];
    }

    protected function participantBelongsTo(Conversation $conversation, Model $participant): bool
    {
        return $conversation->participants()
            ->where('messageable_type', $participant->getMorphClass())
            ->where('messageable_id', $participant->getKey())
            ->exists();
    }

    public function render()
    {
        return $this->view()->title(__('chat.page_title').' | '.__('auth.user-dashboard').' | '.__('globals.viravach'));
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">
        <div class="d-flex flex-column flex-lg-row">
            <!--begin::Sidebar-->
            <div class="flex-column flex-lg-row-auto w-100 w-lg-300px w-xl-400px mb-10 mb-lg-0">
                <!--begin::مخاطبین-->
                <div class="card card-flush">
                    <!--begin::کارت header-->
                    <div class="card-header pt-7">
                        <div class="card-title">
                            <h2 class="fs-4">{{ __('chat.contacts_title') }}</h2>
                        </div>
                    </div>
                    <div class="card-header pt-0 border-0">
                        <!--begin::form-->
                        <form class="w-100 position-relative" autocomplete="off" onsubmit="return false;">
                            <i class="ki-duotone ki-magnifier fs-3 text-gray-500 position-absolute top-50 ms-5 translate-middle-y">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <input type="text" class="form-control form-control-solid px-13" wire:model.live.debounce.300ms="search" placeholder="{{ __('chat.search_placeholder') }}">
                        </form>
                        <!--end::form-->
                    </div>
                    <!--end::کارت header-->
                    <!--begin::کارت body-->
                    <div class="card-body pt-5">
                        <div class="scroll-y me-n5 pe-5 h-300px h-lg-auto" style="max-height: 549px;">
                            @foreach($contacts as $contact)
                                <!--begin::contact-->
                                <div
                                    class="d-flex flex-stack py-4 px-3 rounded cursor-pointer {{ $selectedConversationId === $contact['conversationId'] ? 'bg-light-primary' : '' }} {{ $contact['type'] === 'ai' ? 'bg-light-primary bg-opacity-25' : '' }}"
                                    wire:click="selectConversation({{ $contact['conversationId'] }})"
                                    wire:key="chat-contact-{{ $contact['conversationId'] }}"
                                >
                                    <!--begin::Details-->
                                    <div class="d-flex align-items-center overflow-hidden">
                                        <!--begin::Avatar-->
                                        <div class="symbol symbol-45px symbol-circle position-relative">
                                            <span class="symbol-label {{ $contact['type'] === 'ai' ? 'bg-light-primary text-primary' : 'bg-light-info text-info' }} fs-6 fw-bolder">
                                                {{ \Illuminate\Support\Str::substr($contact['name'], 0, 1) }}
                                            </span>
                                            @if($contact['type'] === 'user')
                                                {{-- Static placeholder: no live presence tracking is wired up yet, this only distinguishes a real person from ViraBot. --}}
                                                <div class="symbol-badge bg-secondary start-100 top-100 border-4 h-8px w-8px ms-n2 mt-n2"></div>
                                            @endif
                                        </div>
                                        <!--end::Avatar-->
                                        <!--begin::Details-->
                                        <div class="ms-5 overflow-hidden">
                                            <div class="d-flex align-items-center">
                                                <span class="fs-5 fw-bold text-gray-900 text-truncate">{{ $contact['name'] }}</span>
                                                @if($contact['type'] === 'ai')
                                                    <span class="badge badge-light-primary fs-9 ms-2">{{ __('chat.pinned_label') }}</span>
                                                @endif
                                            </div>
                                            <div class="fw-semibold text-muted text-truncate">
                                                {{ $contact['lastMessage'] ?? __('chat.no_message_preview') }}
                                            </div>
                                        </div>
                                        <!--end::Details-->
                                    </div>
                                    <!--end::Details-->
                                    <!--begin::Meta-->
                                    <div class="d-flex flex-column align-items-end ms-2 flex-shrink-0">
                                        <span class="text-muted fs-7 mb-1">{{ $contact['relativeTime'] }}</span>
                                        @if($contact['unreadCount'] > 0)
                                            <span class="badge badge-sm badge-circle badge-light-warning">{{ $contact['unreadCount'] }}</span>
                                        @endif
                                    </div>
                                    <!--end::Meta-->
                                </div>
                                <!--end::contact-->
                            @endforeach
                        </div>
                    </div>
                    <!--end::کارت body-->
                </div>
                <!--end::مخاطبین-->
            </div>
            <!--end::Sidebar-->
            <!--begin::Content-->
            <div class="flex-lg-row-fluid ms-lg-7 ms-xl-10">
                <div class="card" id="kt_chat_messenger">
                    @if($selectedConversationId === null)
                        <div class="card-body d-flex align-items-center justify-content-center text-muted fs-6" style="min-height: 500px;">
                            {{ __('chat.select_contact_prompt') }}
                        </div>
                    @else
                        @php($currentContact = collect($contacts)->firstWhere('conversationId', $selectedConversationId))
                        <!--begin::کارت header-->
                        <div class="card-header" id="kt_chat_messenger_header">
                            <div class="card-title">
                                <div class="d-flex justify-content-center flex-column me-3">
                                    <span class="fs-4 fw-bold text-gray-900 me-1 mb-2 lh-1">{{ $currentContact['name'] ?? '' }}</span>
                                </div>
                            </div>
                        </div>
                        <!--end::کارت header-->
                        <!--begin::کارت body-->
                        <div class="card-body" id="kt_chat_messenger_body">
                            <div
                                class="scroll-y me-n5 pe-5 h-400px h-lg-auto"
                                style="max-height: 480px;"
                                id="dashboard-chat-messages"
                                wire:key="dashboard-chat-messages-{{ $selectedConversationId }}"
                                wire:poll.5s="pollForReply"
                                x-data
                                x-init="
                                    const scrollToBottom = () => { $el.scrollTop = $el.scrollHeight; };
                                    scrollToBottom();
                                    new MutationObserver(scrollToBottom).observe($el, { childList: true, subtree: true });
                                "
                            >
                                @if($isAiSelected && empty($messages) && ! $awaitingReply)
                                    <div class="text-center text-muted fs-6 py-10 px-5">
                                        {{ __('chat.empty_state') }}
                                    </div>
                                @endif

                                @foreach($messages as $msg)
                                    @include('components.chat-elements.message-item', ['msg' => $msg, 'translations' => $translations])
                                @endforeach

                                @if($isAiSelected && $awaitingReply)
                                    <div class="d-flex justify-content-start mb-10" wire:poll.3s="pollForReply">
                                        <div class="p-3 rounded bg-light-info text-muted fs-7 fst-italic">
                                            {{ __('chat.typing') }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <!--end::کارت body-->
                        <!--begin::کارت footer-->
                        <div class="card-footer pt-4" id="kt_chat_messenger_footer">
                            @error('body')
                                <div class="text-danger fs-8 mb-2">{{ $message }}</div>
                            @enderror
                            @if($sendError)
                                <div class="text-danger fs-8 mb-2">{{ $sendError }}</div>
                            @endif
                            <textarea class="form-control form-control-flush mb-3" rows="1" wire:model="body" wire:keydown.enter.prevent="sendMessage" placeholder="{{ __('chat.placeholder') }}"></textarea>
                            <div class="d-flex flex-stack justify-content-end">
                                <button class="btn btn-primary" type="button" wire:click="sendMessage" wire:target="sendMessage" wire:loading.attr="disabled">
                                    {{ __('chat.send') }}
                                </button>
                            </div>
                        </div>
                        <!--end::کارت footer-->
                    @endif
                </div>
            </div>
            <!--end::Content-->
        </div>
    </div>
</div>
