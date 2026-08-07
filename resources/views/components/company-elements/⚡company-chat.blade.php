<?php

use App\Livewire\Concerns\InteractsWithChatMessages;
use App\Models\Company;
use App\Services\Chat\AiChatService;
use App\Services\Chat\CompanyChatService;
use App\Services\Chat\Exceptions\CompanyChatException;
use App\Services\Chat\Exceptions\CompanyChatFailureReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Musonza\Chat\Models\Conversation;

/**
 * Company-page chat drawer: the header drawer's flow parameterized with a
 * company id. The AI tab talks to a SEPARATE company-scoped ViraBot
 * conversation (AiChatService with $companyId), and the "company" tab is the
 * direct conversation with the Company itself via CompanyChatService. Uses
 * its own KTDrawer DOM ids so it coexists with the general header drawer
 * rendered on every page.
 */
new class extends Component
{
    use InteractsWithChatMessages;

    /**
     * Stop polling for an AI reply after this many seconds even if none
     * arrives, so a stuck queue worker doesn't leave the typing indicator
     * forever.
     */
    protected const POLL_TIMEOUT_SECONDS = 60;

    public int $companyId;

    /**
     * Published (localized) company name, passed from the public company
     * page so no extra query is needed for labels.
     */
    public string $companyName = '';

    public bool $open = false;

    /**
     * Which conversation the drawer is currently showing: 'ai' (the
     * company-scoped ViraBot conversation) or 'company' (direct).
     */
    public string $activeConversation = 'ai';

    public bool $aiLoaded = false;

    public ?int $aiConversationId = null;

    /**
     * @var array<int, array{id: int, body: string, senderName: string, senderType: ?string, isOwn: bool, time: ?string, type: string}>
     */
    public array $aiMessages = [];

    public bool $awaitingReply = false;

    public ?int $pollDeadline = null;

    public bool $companyLoaded = false;

    public ?int $companyConversationId = null;

    /**
     * @var array<int, array{id: int, body: string, senderName: string, senderType: ?string, isOwn: bool, time: ?string, type: string}>
     */
    public array $companyMessages = [];

    /**
     * Whether the participant has chatted with ViraBot about THIS company
     * enough to be offered a direct company conversation.
     */
    public bool $companyEligible = false;

    public bool $companyHasOwner = false;

    public ?string $transferError = null;

    public string $body = '';

    public ?string $sendError = null;

    /**
     * Resolve the company-scoped AI conversation (and any existing direct
     * company conversation) the first time the drawer is opened, so nothing
     * is queried for visitors who never open the chat.
     */
    public function openDrawer(): void
    {
        $this->open = true;

        if ($this->aiLoaded) {
            return;
        }

        $participant = $this->participant();

        $this->loadAiConversation($participant);
        $this->refreshCompanyState($participant);
    }

    public function closeDrawer(): void
    {
        $this->open = false;
    }

    public function switchConversation(string $conversation): void
    {
        if (in_array($conversation, ['ai', 'company'], true)) {
            $this->activeConversation = $conversation;
        }
    }

    public function connectToCompany(): void
    {
        $this->transferError = null;

        $participant = $this->participant();

        try {
            $conversation = app(CompanyChatService::class)->transfer($participant, $this->company());
        } catch (CompanyChatException $e) {
            $this->transferError = $e->reason === CompanyChatFailureReason::NoOwner
                ? __('chat.company_no_owner')
                : __('chat.company_transfer_not_eligible');

            return;
        }

        $this->companyConversationId = $conversation->id;
        $this->companyMessages = $this->fetchMessages($conversation, $participant);
        $this->companyLoaded = true;
        $this->activeConversation = 'company';
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

        if ($this->activeConversation === 'company') {
            if ($this->companyConversationId === null) {
                return;
            }

            $this->sendCompanyMessage($participant, $body);
        } else {
            $this->sendAiMessage($participant, $body);
        }

        $this->body = '';
    }

    protected function sendAiMessage(Model $participant, string $body): void
    {
        if ($this->aiConversationId === null) {
            $this->loadAiConversation($participant);
        }

        // Context is intentionally not built here: GenerateAiChatReply builds
        // it FRESH at reply time from the conversation's company_id, so
        // republished company edits are picked up immediately.
        $message = app(AiChatService::class)->sendUserMessage($participant, $body, null, $this->companyId);

        $this->aiMessages[] = $this->presentMessage(
            $message->load('participation.messageable'),
            $participant,
        );

        // Their first AI message about this company makes them eligible for
        // a direct company conversation — reflect that without a reopen.
        $this->companyEligible = true;

        $this->awaitingReply = true;
        $this->pollDeadline = now()->addSeconds(self::POLL_TIMEOUT_SECONDS)->timestamp;
    }

    protected function sendCompanyMessage(Model $participant, string $body): void
    {
        $conversation = Conversation::findOrFail($this->companyConversationId);

        $message = Chat::message($body)->from($participant)->to($conversation)->send();

        $this->companyMessages[] = $this->presentMessage(
            $message->load('participation.messageable'),
            $participant,
        );
    }

    /**
     * Fetches messages newer than the last one shown. Triggered primarily by
     * Echo MessageWasSent pushes for authenticated users (see the template's
     * subscription block); guests — who can't authorize private channels —
     * still poll it (3s while awaiting an AI reply, 5s on the company tab).
     * A slow 60s wire:poll remains for authenticated users purely as a
     * fallback for dropped WebSocket events.
     */
    public function pollForReply(): void
    {
        $participant = $this->participant();

        if ($this->awaitingReply && $this->aiConversationId !== null) {
            $this->pollAiReply($participant);
        }

        if ($this->activeConversation === 'company' && $this->companyConversationId !== null) {
            $this->pollCompanyReply($participant);
        }
    }

    protected function pollAiReply(Model $participant): void
    {
        if ($this->pollDeadline !== null && now()->timestamp >= $this->pollDeadline) {
            $this->awaitingReply = false;

            return;
        }

        $lastKnownId = (int) (collect($this->aiMessages)->max('id') ?? 0);

        $newMessages = Conversation::findOrFail($this->aiConversationId)
            ->messages()
            ->with('participation.messageable')
            ->where('id', '>', $lastKnownId)
            ->orderBy('id')
            ->get();

        if ($newMessages->isEmpty()) {
            return;
        }

        foreach ($newMessages as $newMessage) {
            $this->aiMessages[] = $this->presentMessage($newMessage, $participant);
        }

        $this->awaitingReply = false;
    }

    protected function pollCompanyReply(Model $participant): void
    {
        $lastKnownId = (int) (collect($this->companyMessages)->max('id') ?? 0);

        $newMessages = Conversation::findOrFail($this->companyConversationId)
            ->messages()
            ->with('participation.messageable')
            ->where('id', '>', $lastKnownId)
            ->orderBy('id')
            ->get();

        foreach ($newMessages as $newMessage) {
            $this->companyMessages[] = $this->presentMessage($newMessage, $participant);
        }
    }

    /**
     * Locates the body of a previously presented message for
     * InteractsWithChatMessages::toggleTranslation() — message ids are
     * globally unique, so a single lookup across both tabs is safe.
     */
    protected function locateMessageBody(int $messageId): ?string
    {
        $message = collect($this->aiMessages)->firstWhere('id', $messageId)
            ?? collect($this->companyMessages)->firstWhere('id', $messageId);

        return $message['body'] ?? null;
    }

    protected function loadAiConversation(?Model $participant = null): void
    {
        $participant ??= $this->participant();

        $conversation = app(AiChatService::class)->startOrGetConversation($participant, $this->companyId);

        $this->aiConversationId = $conversation->id;
        $this->aiMessages = $this->fetchMessages($conversation, $participant);
        $this->aiLoaded = true;
    }

    protected function refreshCompanyState(Model $participant): void
    {
        $company = $this->company();
        $chatService = app(CompanyChatService::class);

        $this->companyHasOwner = $chatService->hasOwner($company);
        $this->companyEligible = $chatService->isEligible($participant, $company);

        $existing = $chatService->existingConversation($participant, $company);

        if (! $existing) {
            return;
        }

        $this->companyConversationId = $existing->id;
        $this->companyMessages = $this->fetchMessages($existing, $participant);
        $this->companyLoaded = true;
    }

    protected function company(): Company
    {
        return Company::findOrFail($this->companyId);
    }
};
?>

<div>
    <!--begin::Company chat trigger-->
    <button type="button" class="btn btn-primary w-100" id="kt_drawer_company_chat_toggle">
        <i class="ki-duotone ki-message-text-2 fs-2">
            <span class="path1"></span>
            <span class="path2"></span>
            <span class="path3"></span>
        </i>
        {{ __('chat.company_chat_trigger') }}
    </button>
    <!--end::Company chat trigger-->
    <!--begin::Company chat drawer-->
    {{--
        Same wire:ignore.self + KTEventHandler wiring as the header chat
        drawer (see its comment): Metronic's KTDrawer toggles "drawer-on"
        directly on this node, and openDrawer()/closeDrawer() ride on the
        drawer's own shown/hide events so lazy-loading fires once per open.
    --}}
    <div
        id="kt_drawer_company_chat"
        class="bg-body"
        wire:ignore.self
        x-data
        x-init="typeof KTEventHandler !== 'undefined' && (() => {
            KTEventHandler.on($el, 'kt.drawer.shown', () => $wire.openDrawer());
            KTEventHandler.on($el, 'kt.drawer.hide', () => $wire.closeDrawer());
        })()"
        data-kt-drawer="true" data-kt-drawer-name="company-chat" data-kt-drawer-activate="true" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'300px', 'md': '500px'}" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_drawer_company_chat_toggle" data-kt-drawer-close="#kt_drawer_company_chat_close">
        @auth
            {{--
                Primary real-time mechanism (authenticated users only): same
                lazy-id subscription pattern as the header chat drawer — ids
                appear after mount, so subscriptions go through
                window.listenToChatConversation (resources/js/echo.js) and
                each MessageWasSent push triggers the same pollForReply() the
                old polls used. Guests cannot pass /broadcasting/auth (see
                routes/channels.php) and keep the poll fallbacks below.
            --}}
            {{--
                The typeof guard is load-bearing: these $watch callbacks run
                inside Livewire's response processing, so if the Vite bundle
                failed to load (e.g. stale cache after a deploy) an unguarded
                call would throw and abort the rest of the UI update — that
                is exactly what broke the connect-to-company tab switch once.
                Guarded, a missing helper just degrades to the fallback poll.
            --}}
            <div class="d-none" x-data x-init="(() => {
                const subscribe = (id) => id
                    && typeof window.listenToChatConversation === 'function'
                    && window.listenToChatConversation(id, () => $wire.pollForReply());
                subscribe($wire.aiConversationId);
                subscribe($wire.companyConversationId);
                $wire.$watch('aiConversationId', subscribe);
                $wire.$watch('companyConversationId', subscribe);
            })()"></div>
        @endauth
        <!--begin::Messenger-->
        <div class="card w-100 border-0 rounded-0" id="kt_drawer_company_chat_messenger">
            <!--begin::Header-->
            <div class="card-header pe-5" id="kt_drawer_company_chat_messenger_header">
                <!--begin::Title-->
                <div class="card-title">
                    <div class="d-flex justify-content-center flex-column me-3">
                        <span class="fs-4 fw-bold text-gray-900 me-1 mb-2 lh-1">
                            {{ $activeConversation === 'company' ? $companyName : __('chat.header_title') }}
                        </span>
                        <div class="mb-0 lh-1">
                            <span class="badge badge-success badge-circle w-10px h-10px me-1"></span>
                            <span class="fs-7 fw-semibold text-muted">
                                {{ $activeConversation === 'company' ? __('chat.company_header_status') : __('chat.header_status') }}
                            </span>
                        </div>

                        {{--
                            Discoverability hint: always shown until a direct
                            company conversation exists, so visitors know
                            up-front that talking to ViraBot unlocks direct
                            contact — the eligibility gate itself (≥1 AI
                            message) stays as is.
                        --}}
                        @if($companyConversationId === null)
                            <div class="fs-8 text-muted mt-2">{{ __('chat.company_drawer_hint') }}</div>
                        @endif

                        @if($companyConversationId !== null)
                            <!--begin::Tabs-->
                            <div class="btn-group btn-group-sm mt-2" role="group">
                                <button type="button" class="btn btn-sm {{ $activeConversation === 'ai' ? 'btn-primary' : 'btn-light' }}" wire:click="switchConversation('ai')">
                                    {{ __('chat.tab_ai') }}
                                </button>
                                <button type="button" class="btn btn-sm {{ $activeConversation === 'company' ? 'btn-primary' : 'btn-light' }}" wire:click="switchConversation('company')">
                                    {{ __('chat.tab_company') }}
                                </button>
                            </div>
                            <!--end::Tabs-->
                        @endif
                    </div>
                </div>
                <!--end::Title-->
                <!--begin::Toolbar-->
                <div class="card-toolbar">
                    @if($aiLoaded && $companyConversationId === null)
                        <!--begin::Connect to company-->
                        @if($companyEligible && $companyHasOwner)
                            <button type="button" class="btn btn-sm btn-light-primary me-2" wire:click="connectToCompany" wire:target="connectToCompany" wire:loading.attr="disabled">
                                {{ __('chat.connect_to_company') }}
                            </button>
                        @else
                            {{-- Disabled buttons swallow pointer events, so the tooltip title sits on the wrapper span. --}}
                            <span class="d-inline-block me-2" tabindex="0" data-bs-toggle="tooltip" title="{{ $companyHasOwner ? __('chat.company_transfer_not_eligible') : __('chat.company_no_owner') }}">
                                <button type="button" class="btn btn-sm btn-light-primary" disabled>
                                    {{ __('chat.connect_to_company') }}
                                </button>
                            </span>
                        @endif
                        <!--end::Connect to company-->
                    @endif
                    <!--begin::Close-->
                    <div class="btn btn-sm btn-icon btn-active-color-primary" id="kt_drawer_company_chat_close">
                        <i class="ki-duotone ki-cross-square fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Header-->
            @if($transferError)
                <div class="text-danger fs-8 px-9 pt-3">{{ $transferError }}</div>
            @endif
            <!--begin::Body-->
            <div class="card-body" id="kt_drawer_company_chat_messenger_body">
                <div
                    class="scroll-y me-n5 pe-5"
                    data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_drawer_company_chat_messenger_header, #kt_drawer_company_chat_messenger_footer" data-kt-scroll-wrappers="#kt_drawer_company_chat_messenger_body" data-kt-scroll-offset="0px"
                    data-chat-scroll
                    {{--
                        wire:ignore.self: KTScroll sets an inline height style
                        on this element (via data-kt-scroll) that never
                        appears in the server-rendered HTML. Without ignoring
                        this element's own attributes, every morph (send,
                        poll, Echo-triggered reply) strips that inline style,
                        the container stops overflowing, and the drawer's own
                        wrapper starts scrolling instead — so the global
                        'morphed' hook's scrollTop write in resources/js/echo.js
                        lands on an element that no longer has anything to
                        scroll. Children (messages) still morph normally.
                    --}}
                    wire:ignore.self
                    x-data
                    x-init="$el.scrollTop = $el.scrollHeight"
                >
                    {{-- Scroll-to-bottom on send/receive/open is handled by the global 'morphed' Livewire hook in resources/js/echo.js via the data-chat-scroll marker above. --}}
                    @php $activeMessages = $activeConversation === 'company' ? $companyMessages : $aiMessages; @endphp

                    @if($activeConversation === 'ai' && $aiLoaded && empty($activeMessages) && ! $awaitingReply)
                        <!--begin::Empty state-->
                        <div class="text-center text-muted fs-6 py-10 px-5">
                            {{ __('chat.company_empty_state', ['company' => $companyName]) }}
                        </div>
                        <!--end::Empty state-->
                    @endif

                    @foreach($activeMessages as $msg)
                        @include('components.chat-elements.message-item', ['msg' => $msg, 'translations' => $translations])
                    @endforeach

                    @if($activeConversation === 'ai' && $awaitingReply)
                        {{-- Guests still poll for the AI reply (no broadcasting auth); authenticated users get it pushed over Echo. --}}
                        <!--begin::Typing indicator-->
                        <div class="d-flex justify-content-start mb-10" @guest wire:poll.3s="pollForReply" @endguest>
                            <div class="p-3 rounded bg-light-info text-muted fs-7 fst-italic">
                                {{ __('chat.typing') }}
                            </div>
                        </div>
                        <!--end::Typing indicator-->
                    @endif

                    @guest
                        {{-- Guests keep the pre-Reverb poll as their primary mechanism — they cannot subscribe to private channels. --}}
                        @if($open && $activeConversation === 'company' && $companyConversationId !== null)
                            <div wire:poll.5s="pollForReply" class="d-none"></div>
                        @endif
                    @else
                        {{-- FALLBACK ONLY, not the primary mechanism: Echo pushes above drive updates; this slow poll recovers missed/dropped WebSocket events. --}}
                        @if($open)
                            <div wire:poll.60s="pollForReply" class="d-none"></div>
                        @endif
                    @endguest
                </div>
            </div>
            <!--end::Body-->
            <!--begin::Footer-->
            <div class="card-footer pt-4" id="kt_drawer_company_chat_messenger_footer">
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
            <!--end::Footer-->
        </div>
        <!--end::Messenger-->
    </div>
    <!--end::Company chat drawer-->
</div>
