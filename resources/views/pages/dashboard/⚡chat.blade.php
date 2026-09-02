<?php

use App\Events\ChatConversationStarted;
use App\Livewire\Concerns\InteractsWithChatMessages;
use App\Models\Company;
use App\Models\User;
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

    /**
     * One section per Company the participant owns, listing conversations
     * where the COMPANY is the participant (visitors who contacted it from
     * its public page) — kept apart from the personal contact list above.
     *
     * @var array<int, array{companyId: int, name: string, contacts: array<int, array{conversationId: int, name: string, type: ?string, lastMessage: ?string, relativeTime: ?string, sortTime: int, unreadCount: int}>}>
     */
    public array $companySections = [];

    public ?int $aiConversationId = null;

    public ?int $selectedConversationId = null;

    /**
     * When the open thread comes from a company section, the id of the owned
     * Company it is viewed — and sent — as; null for personal threads.
     */
    public ?int $activeCompanyId = null;

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
        $this->loadCompanySections($participant);
        $this->selectConversation((int) $this->aiConversationId);
    }

    public function updatedSearch(): void
    {
        $this->loadContacts($this->participant());
    }

    /**
     * @param  int|null  $companyId  present when the thread was picked from a
     *                               company section: the conversation is then
     *                               viewed as that owned Company, not as the
     *                               personal participant
     */
    public function selectConversation(int $conversationId, ?int $companyId = null): void
    {
        $participant = $this->participant();
        $viewer = $companyId !== null ? $this->ownedCompany($companyId) : $participant;
        $conversation = Conversation::findOrFail($conversationId);

        abort_unless($this->participantBelongsTo($conversation, $viewer), 403);

        $this->selectedConversationId = $conversationId;
        $this->activeCompanyId = $companyId;
        $this->isAiSelected = $companyId === null && $conversationId === $this->aiConversationId;
        $this->messages = $this->fetchMessages($conversation, $viewer);
        $this->sendError = null;
        $this->awaitingReply = false;

        Chat::conversation($conversation)->setParticipant($viewer)->readAll();

        $this->loadContacts($participant);
        $this->loadCompanySections($participant);
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

        if (RateLimiter::tooManyAttempts($this->sendRateLimitKey($participant), $this->sendRateLimitMax())) {
            $this->sendError = __('chat.rate_limited_send');

            return;
        }

        RateLimiter::hit($this->sendRateLimitKey($participant), 60);

        $body = strip_tags(trim($this->body));

        if ($body === '') {
            return;
        }

        $sender = $this->activeParticipant();

        if ($this->isAiSelected) {
            $message = app(AiChatService::class)->sendUserMessage($participant, $body);

            $this->awaitingReply = true;
            $this->pollDeadline = now()->addSeconds(self::POLL_TIMEOUT_SECONDS)->timestamp;
        } else {
            $conversation = Conversation::findOrFail($this->selectedConversationId);
            $message = Chat::message($body)->from($sender)->to($conversation)->send();
        }

        $this->messages[] = $this->presentMessage($message->load('participation.messageable'), $sender);
        $this->body = '';

        $this->loadContacts($participant);
        $this->loadCompanySections($participant);
    }

    /**
     * Echo listeners — the PRIMARY real-time mechanism on this page.
     * Registered here instead of #[On] attributes because the channel names
     * embed ids only known at runtime. Livewire sends this set to the
     * browser once, at mount, so it covers:
     *  - the per-participant channels (this user + each owned company) that
     *    App\Events\ChatConversationStarted announces new conversations on;
     *  - the per-conversation musonza channels of every conversation already
     *    in the lists at mount.
     * Conversations that appear AFTER mount get their row via
     * ChatConversationStarted, and their channel is subscribed by the
     * template's selectedConversationId watcher once opened; until then
     * their messages only update the list via the 60s fallback poll.
     */
    public function getListeners(): array
    {
        $user = auth()->user();

        $listeners = [
            'echo-private:'.ChatConversationStarted::channelNameFor($user).',ChatConversationStarted' => 'refreshContactLists',
        ];

        foreach ($user->companies as $company) {
            $listeners['echo-private:'.ChatConversationStarted::channelNameFor($company).',ChatConversationStarted'] = 'refreshContactLists';
        }

        foreach ($this->listedConversationIds() as $conversationId) {
            $listeners["echo-private:mc-chat-conversation.{$conversationId},.Musonza\\Chat\\Eventing\\MessageWasSent"] = 'onConversationMessage';
        }

        return $listeners;
    }

    /**
     * Echo handler for musonza's MessageWasSent: reuses the exact refresh
     * paths the old polls called — pollForReply() when the push is for the
     * open thread, refreshContactLists() (unread badges / last message /
     * ordering) when it is for any other listed conversation.
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

        $this->refreshContactLists();
    }

    /**
     * FALLBACK ONLY, not the primary mechanism (that's the Echo listeners
     * above): runs on a slow 60s wire:poll to recover anything a missed or
     * dropped WebSocket event left behind — both the open thread and the
     * contact lists.
     */
    public function fallbackSync(): void
    {
        $this->pollForReply();
        $this->refreshContactLists();
    }

    /**
     * Rebuilds both sidebar lists. Triggered by Echo pushes (new
     * conversations and messages in non-open threads) and the 60s fallback
     * poll.
     */
    public function refreshContactLists(): void
    {
        $participant = $this->participant();

        $this->loadContacts($participant);
        $this->loadCompanySections($participant);
    }

    /**
     * Conversation ids currently present in the personal list and the
     * company sections (plus the open thread), i.e. the channels worth
     * listening on.
     *
     * @return array<int, int>
     */
    protected function listedConversationIds(): array
    {
        $ids = collect($this->contacts)->pluck('conversationId');

        foreach ($this->companySections as $section) {
            $ids = $ids->merge(collect($section['contacts'])->pluck('conversationId'));
        }

        if ($this->selectedConversationId !== null) {
            $ids->push($this->selectedConversationId);
        }

        return $ids->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /**
     * Fetches messages newer than the last one shown in the open thread.
     * Triggered primarily by Echo MessageWasSent pushes (via
     * onConversationMessage()); the 60s fallbackSync() poll is the only
     * remaining timer that reaches it.
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
        $viewer = $this->activeParticipant();
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
            $this->messages[] = $this->presentMessage($newMessage, $viewer);
        }

        if ($this->isAiSelected) {
            $this->awaitingReply = false;
        }

        Chat::conversation($conversation)->setParticipant($viewer)->readAll();
        $this->loadContacts($participant);
        $this->loadCompanySections($participant);
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
            // Company-scoped ViraBot conversations (data.company_id set) live
            // on the public company pages only; listing them here would show
            // several indistinguishable "ViraBot" contacts.
            ->reject(fn (Participation $participation) => ($participation->conversation->data['type'] ?? null) === 'ai'
                && ($participation->conversation->data['company_id'] ?? null) !== null)
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

    /**
     * One section per owned Company with that company's direct conversations
     * (data.type=company), viewed from the COMPANY's perspective so unread
     * counts and message ownership are the company's, not the owner's.
     */
    protected function loadCompanySections(Model $participant): void
    {
        $this->companySections = [];

        if (! $participant instanceof User) {
            return;
        }

        foreach ($participant->companies as $company) {
            $paginator = Chat::conversations()->setParticipant($company)->isDirect()->perPage(100)->get();

            $contacts = collect($paginator->items())
                ->filter(fn (Participation $participation) => ($participation->conversation->data['type'] ?? null) === 'company')
                ->map(fn (Participation $participation) => $this->presentContact($participation, $company))
                ->filter()
                ->sortByDesc('sortTime')
                ->values()
                ->all();

            $this->companySections[] = [
                'companyId' => $company->id,
                'name' => (string) ($company->publication?->name ?? $company->name),
                'contacts' => $contacts,
            ];
        }
    }

    /**
     * The identity the open thread is viewed and sent as: an owned Company
     * for threads picked from a company section — so visitors see the
     * company itself replying — or the resolved personal participant.
     */
    protected function activeParticipant(): Model
    {
        return $this->activeCompanyId !== null
            ? $this->ownedCompany($this->activeCompanyId)
            : $this->participant();
    }

    protected function ownedCompany(int $companyId): Company
    {
        $participant = $this->participant();

        abort_unless($participant instanceof User, 403);

        return $participant->companies()->findOrFail($companyId);
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
        {{-- Same infobar + tab bar every other dashboard page renders, so Chat
             is reachable from — and can navigate back to — the rest of the
             dashboard. The Chat tab activates itself: the infobar marks the
             active tab with request()->routeIs('chat').

             Safe to prepend: this page's scroll containers use fixed pixel
             max-heights (the contact list and the message pane below), not
             viewport units, and the composer is an ordinary card-footer in
             normal flow — not fixed or sticky. So the page simply grows taller
             and the document scrolls, exactly as on ⚡profile and
             ⚡my-companies. Nothing here needed a height adjustment. --}}
        <livewire:dashboard-elements.infobar/>

        <div class="d-flex flex-column flex-lg-row">
            <!--begin::Sidebar-->
            <div class="flex-column flex-lg-row-auto w-100 w-lg-300px w-xl-400px mb-10 mb-lg-0">
                <!--begin::مخاطبین-->
                {{-- FALLBACK ONLY, not the primary mechanism: Echo listeners (getListeners() + the thread subscription below) drive updates; this slow poll recovers missed/dropped WebSocket events. --}}
                <div class="card card-flush" wire:poll.60s="fallbackSync">
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

                            @foreach($companySections as $section)
                                <!--begin::Company section-->
                                <div wire:key="company-section-{{ $section['companyId'] }}">
                                    <div class="separator my-4"></div>
                                    <div class="fs-6 fw-bold text-gray-800 px-3 mb-2">
                                        {{ __('chat.company_inbox_title', ['name' => $section['name']]) }}
                                    </div>

                                    @if(empty($section['contacts']))
                                        <div class="text-muted fs-7 px-3 pb-2">{{ __('chat.company_inbox_empty') }}</div>
                                    @endif

                                    @foreach($section['contacts'] as $contact)
                                        <!--begin::Company contact-->
                                        <div
                                            class="d-flex flex-stack py-4 px-3 rounded cursor-pointer {{ $selectedConversationId === $contact['conversationId'] && $activeCompanyId === $section['companyId'] ? 'bg-light-primary' : '' }}"
                                            wire:click="selectConversation({{ $contact['conversationId'] }}, {{ $section['companyId'] }})"
                                            wire:key="company-{{ $section['companyId'] }}-contact-{{ $contact['conversationId'] }}"
                                        >
                                            <div class="d-flex align-items-center overflow-hidden">
                                                <div class="symbol symbol-45px symbol-circle">
                                                    <span class="symbol-label bg-light-info text-info fs-6 fw-bolder">
                                                        {{ \Illuminate\Support\Str::substr($contact['name'], 0, 1) }}
                                                    </span>
                                                </div>
                                                <div class="ms-5 overflow-hidden">
                                                    <span class="fs-5 fw-bold text-gray-900 text-truncate d-block">{{ $contact['name'] }}</span>
                                                    <div class="fw-semibold text-muted text-truncate">
                                                        {{ $contact['lastMessage'] ?? __('chat.no_message_preview') }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex flex-column align-items-end ms-2 flex-shrink-0">
                                                <span class="text-muted fs-7 mb-1">{{ $contact['relativeTime'] }}</span>
                                                @if($contact['unreadCount'] > 0)
                                                    <span class="badge badge-sm badge-circle badge-light-warning">{{ $contact['unreadCount'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <!--end::Company contact-->
                                    @endforeach
                                </div>
                                <!--end::Company section-->
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
                        @php($currentContact = collect($contacts)->firstWhere('conversationId', $selectedConversationId)
                            ?? collect($companySections)->flatMap(fn ($section) => $section['contacts'])->firstWhere('conversationId', $selectedConversationId))
                        @php($activeSection = $activeCompanyId !== null ? collect($companySections)->firstWhere('companyId', $activeCompanyId) : null)
                        <!--begin::کارت header-->
                        <div class="card-header" id="kt_chat_messenger_header">
                            <div class="card-title">
                                <div class="d-flex justify-content-center flex-column me-3">
                                    <span class="fs-4 fw-bold text-gray-900 me-1 mb-2 lh-1">{{ $currentContact['name'] ?? '' }}</span>
                                    @if($activeSection)
                                        {{-- Replies in this thread are sent AS the company, not as the personal account. --}}
                                        <span class="fs-7 fw-semibold text-muted lh-1">{{ $activeSection['name'] }}</span>
                                    @endif
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
                                data-chat-scroll
                                wire:key="dashboard-chat-messages-{{ $selectedConversationId }}"
                                x-data
                                x-init="
                                    $el.scrollTop = $el.scrollHeight;
                                    {{--
                                        Primary real-time mechanism for the open thread. The wire:key
                                        above recreates this element on every thread switch, so this
                                        runs once per selection and covers conversations created after
                                        mount that getListeners() could not know about (dedup lives in
                                        listenToChatConversation — resources/js/echo.js). Scroll-to-bottom
                                        on every subsequent update is handled by the global 'morphed' hook
                                        in resources/js/echo.js via the data-chat-scroll marker above.
                                    --}}
                                    {{-- typeof guard: if the bundle failed to load, degrade to the fallback poll instead of throwing mid-update. --}}
                                    typeof window.listenToChatConversation === 'function'
                                        && window.listenToChatConversation({{ (int) $selectedConversationId }}, (e) => $wire.onConversationMessage(e));
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

                                {{-- No wire:poll here anymore: the AI reply is pushed over Echo; the 60s fallbackSync clears a stuck indicator if the socket drops. --}}
                                @if($isAiSelected && $awaitingReply)
                                    <div class="d-flex justify-content-start mb-10">
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
