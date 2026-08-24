<?php

use App\Livewire\Concerns\InteractsWithChatMessages;
use App\Models\Company;
use App\Services\Chat\AiChatService;
use App\Services\Chat\CompanyChatService;
use App\Services\Chat\Exceptions\CompanyChatException;
use App\Services\Chat\Exceptions\CompanyChatFailureReason;
use App\Services\Chat\Exceptions\SupportTransferException;
use App\Services\Chat\SupportTransferService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Musonza\Chat\Models\Conversation;

/**
 * Single shared chat modal: three tabs (ViraBot, direct company contact,
 * support) replacing the old separate header drawer and company-page drawer.
 * Mounted with no companyId from the header toolbar (tab 2 absent there);
 * mounted with a companyId from the company page (tab 2 present unless the
 * viewer owns that company). Every instance gets its own DOM ids via
 * $this->getId(), because both a company page's own instance AND the
 * header's site-wide instance can be present on the same page at once.
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

    public ?int $companyId = null;

    /**
     * Published (localized) company name, passed from the public company
     * page so no extra query is needed for labels.
     */
    public string $companyName = '';

    public bool $open = false;

    /**
     * Which tab is active: 'ai', 'company', or 'support'.
     */
    public string $activeTab = 'ai';

    public bool $aiLoaded = false;

    public ?int $aiConversationId = null;

    /**
     * @var array<int, array{id: int, body: string, bodyHtml: ?string, senderName: string, senderType: ?string, senderAvatar: ?string, isOwn: bool, time: ?string, type: string}>
     */
    public array $aiMessages = [];

    public bool $awaitingReply = false;

    public ?int $pollDeadline = null;

    public bool $companyLoaded = false;

    public ?int $companyConversationId = null;

    /**
     * @var array<int, array{id: int, body: string, bodyHtml: ?string, senderName: string, senderType: ?string, senderAvatar: ?string, isOwn: bool, time: ?string, type: string}>
     */
    public array $companyMessages = [];

    /**
     * Computed in mount() from auth()->user() directly (never from
     * participant(), which would resolve/create a Guest row on every page
     * load) so tab 2's visibility is correct on first paint instead of
     * flashing in before disappearing once the drawer opens.
     */
    public bool $isCompanyOwner = false;

    /**
     * Set when startOrGetConversation() throws for the company tab (no owner
     * to answer, or — defensively — the crafted-call self-message case).
     * Shown inline in the company tab instead of a toast.
     */
    public ?string $companyError = null;

    public bool $supportLoaded = false;

    public ?int $supportConversationId = null;

    /**
     * @var array<int, array{id: int, body: string, bodyHtml: ?string, senderName: string, senderType: ?string, senderAvatar: ?string, isOwn: bool, time: ?string, type: string}>
     */
    public array $supportMessages = [];

    /**
     * True once transfer() has confirmed no agent is available at all. Shown
     * as a friendly empty state in the support tab, with the input disabled
     * — never a silent failure or a red toast.
     */
    public bool $supportUnavailable = false;

    public string $body = '';

    public ?string $sendError = null;

    public function mount(): void
    {
        if ($this->companyId !== null && auth()->check()) {
            $ownerId = Company::query()->whereKey($this->companyId)->value('user_id');

            $this->isCompanyOwner = $ownerId !== null && (int) $ownerId === (int) auth()->id();
        }
    }

    /**
     * Resolve the AI conversation, and check for (but don't create) any
     * existing company/support conversation, the first time the drawer is
     * opened — so nothing is queried for visitors who never open the chat.
     */
    public function openDrawer(): void
    {
        $this->open = true;

        if ($this->aiLoaded) {
            return;
        }

        $participant = $this->participant();

        $this->loadAiConversation($participant);

        if ($this->companyId !== null && ! $this->isCompanyOwner) {
            $this->refreshCompanyState($participant);
        }

        $this->refreshSupportState($participant);
    }

    public function closeDrawer(): void
    {
        $this->open = false;
    }

    /**
     * Tabs load lazily on first switch — no separate "connect" button. The
     * company/support tabs have no prerequisite: the first click itself
     * starts (or adopts) the conversation.
     */
    public function switchTab(string $tab): void
    {
        if (! in_array($tab, ['ai', 'company', 'support'], true)) {
            return;
        }

        if ($tab === 'company' && ($this->companyId === null || $this->isCompanyOwner)) {
            return;
        }

        $this->activeTab = $tab;

        if ($tab === 'company' && ! $this->companyLoaded) {
            $this->loadCompanyConversation();
        }

        if ($tab === 'support' && ! $this->supportLoaded) {
            $this->loadSupportConversation();
        }
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

        if ($this->activeTab === 'support' && $this->supportUnavailable) {
            return;
        }

        if ($this->activeTab === 'company' && $this->companyError !== null) {
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

        match ($this->activeTab) {
            'company' => $this->sendCompanyMessage($participant, $body),
            'support' => $this->sendSupportMessage($participant, $body),
            default => $this->sendAiMessage($participant, $body),
        };

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

        $this->awaitingReply = true;
        $this->pollDeadline = now()->addSeconds(self::POLL_TIMEOUT_SECONDS)->timestamp;
    }

    protected function sendCompanyMessage(Model $participant, string $body): void
    {
        if ($this->companyConversationId === null) {
            return;
        }

        try {
            $message = app(CompanyChatService::class)->sendMessage($participant, $this->company(), $body);
        } catch (CompanyChatException) {
            // Audited failure path (see 1C): never swallowed, always surfaced.
            $this->sendError = __('chat.generic_error');

            return;
        }

        $this->companyMessages[] = $this->presentMessage(
            $message->load('participation.messageable'),
            $participant,
        );
    }

    protected function sendSupportMessage(Model $participant, string $body): void
    {
        if ($this->supportConversationId === null) {
            return;
        }

        $conversation = Conversation::findOrFail($this->supportConversationId);

        $message = Chat::message($body)->from($participant)->to($conversation)->send();

        $this->supportMessages[] = $this->presentMessage(
            $message->load('participation.messageable'),
            $participant,
        );
    }

    /**
     * Fetches messages newer than the last one shown in whichever
     * conversations are loaded. Triggered primarily by Echo MessageWasSent
     * pushes for authenticated users (see the template's subscription
     * block); guests — who can't authorize private channels — still poll it.
     * A slow 60s wire:poll remains for authenticated users purely as a
     * fallback for dropped WebSocket events.
     */
    public function pollForReply(): void
    {
        $participant = $this->participant();

        if ($this->awaitingReply && $this->aiConversationId !== null) {
            $this->pollAiReply($participant);
        }

        if ($this->activeTab === 'company' && $this->companyConversationId !== null) {
            $this->pollCompanyReply($participant);
        }

        if ($this->activeTab === 'support' && $this->supportConversationId !== null) {
            $this->pollSupportReply($participant);
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

    protected function pollSupportReply(Model $participant): void
    {
        $lastKnownId = (int) (collect($this->supportMessages)->max('id') ?? 0);

        $newMessages = Conversation::findOrFail($this->supportConversationId)
            ->messages()
            ->with('participation.messageable')
            ->where('id', '>', $lastKnownId)
            ->orderBy('id')
            ->get();

        foreach ($newMessages as $newMessage) {
            $this->supportMessages[] = $this->presentMessage($newMessage, $participant);
        }
    }

    /**
     * Locates the body of a previously presented message for
     * InteractsWithChatMessages::toggleTranslation() — message ids are
     * globally unique, so a single lookup across all three tabs is safe.
     */
    protected function locateMessageBody(int $messageId): ?string
    {
        $message = collect($this->aiMessages)->firstWhere('id', $messageId)
            ?? collect($this->companyMessages)->firstWhere('id', $messageId)
            ?? collect($this->supportMessages)->firstWhere('id', $messageId);

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

    /**
     * Read-only check on open: shows a conversation the participant already
     * has, but never starts a new one just because the drawer was opened.
     */
    protected function refreshCompanyState(Model $participant): void
    {
        $existing = app(CompanyChatService::class)->existingConversation($participant, $this->company());

        if (! $existing) {
            return;
        }

        $this->companyConversationId = $existing->id;
        $this->companyMessages = $this->fetchMessages($existing, $participant);
        $this->companyLoaded = true;
    }

    /**
     * Called on first switch to the company tab: starts (or adopts) the
     * conversation with no prerequisite. Every failure path is surfaced as a
     * Persian message here — nothing is ever caught and discarded.
     */
    protected function loadCompanyConversation(): void
    {
        $participant = $this->participant();

        try {
            $conversation = app(CompanyChatService::class)->startOrGetConversation($participant, $this->company());
        } catch (CompanyChatException $e) {
            $this->companyError = $e->reason === CompanyChatFailureReason::NoOwner
                ? __('chat.company_no_owner')
                : __('chat.company_is_owner');
            $this->companyLoaded = true;

            return;
        }

        $this->companyConversationId = $conversation->id;
        $this->companyMessages = $this->fetchMessages($conversation, $participant);
        $this->companyLoaded = true;
    }

    /**
     * Read-only check on open: shows a conversation the participant already
     * has, but never starts a new one (and never picks an agent) just
     * because the drawer was opened.
     */
    protected function refreshSupportState(Model $participant): void
    {
        $existing = app(SupportTransferService::class)->existingConversation($participant);

        if (! $existing) {
            return;
        }

        $this->supportConversationId = $existing->id;
        $this->supportMessages = $this->fetchMessages($existing, $participant);
        $this->supportLoaded = true;
    }

    /**
     * Called on first switch to the support tab: starts (or adopts) the
     * conversation with no prerequisite. The only failure path
     * (noAgentAvailable) is surfaced as the friendly empty state below,
     * never silently — see 1C.
     */
    protected function loadSupportConversation(): void
    {
        $participant = $this->participant();

        try {
            $conversation = app(SupportTransferService::class)->transfer($participant);
        } catch (SupportTransferException) {
            $this->supportUnavailable = true;
            $this->supportLoaded = true;

            return;
        }

        $this->supportConversationId = $conversation->id;
        $this->supportMessages = $this->fetchMessages($conversation, $participant);
        $this->supportLoaded = true;
    }

    protected function company(): Company
    {
        return Company::findOrFail($this->companyId);
    }

    protected function tabTitle(): string
    {
        return match ($this->activeTab) {
            'company' => $this->companyName,
            'support' => __('chat.tab_support'),
            default => __('chat.header_title'),
        };
    }

    protected function tabStatus(): string
    {
        return match ($this->activeTab) {
            'company' => __('chat.company_header_status'),
            'support' => __('chat.support_header_status'),
            default => __('chat.header_status'),
        };
    }
};
?>

<div>
    @if($companyId === null)
        <!--begin::Chat trigger (header icon)-->
        <div class="position-relative btn btn-icon btn-active-light-primary btn-custom w-30px h-30px w-md-40px h-md-40px" id="kt_drawer_chat_modal_toggle_{{ $this->getId() }}">
            <i class="ki-duotone ki-message-text-2 fs-1">
                <span class="path1"></span>
                <span class="path2"></span>
                <span class="path3"></span>
            </i>
            <span class="bullet bullet-dot bg-success h-6px w-6px position-absolute translate-middle top-0 start-50 animation-blink"></span>
        </div>
        <!--end::Chat trigger (header icon)-->
    @else
        <!--begin::Chat trigger (company page button)-->
        <button type="button" class="btn btn-primary w-100" id="kt_drawer_chat_modal_toggle_{{ $this->getId() }}">
            <i class="ki-duotone ki-message-text-2 fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
                <span class="path3"></span>
            </i>
            {{ __('chat.company_chat_trigger') }}
        </button>
        <!--end::Chat trigger (company page button)-->
    @endif

    {{--
        wire:ignore.self keeps Livewire from morphing this element's own
        attributes. Metronic's KTDrawer adds/removes a "drawer-on" class
        directly on this node when it opens/closes; without ignoring it,
        Livewire's DOM diffing on every action (send/poll/translate) wipes
        that class the instant a response comes back, snapping the drawer
        shut. openDrawer()/closeDrawer() are triggered from Metronic's own
        "kt.drawer.shown"/"kt.drawer.hide" events (fired on this element)
        instead of wire:click on the toggle button, so the lazy-load fires
        exactly once per open and polling stops once closed.

        The drawer name/ids are suffixed with $this->getId() because both the
        header's site-wide instance and a company page's own instance of this
        SAME component can be mounted on one page at once.
    --}}
    <div
        id="kt_drawer_chat_modal_{{ $this->getId() }}"
        class="bg-body"
        wire:ignore.self
        x-data
        x-init="typeof KTEventHandler !== 'undefined' && (() => {
            KTEventHandler.on($el, 'kt.drawer.shown', () => $wire.openDrawer());
            KTEventHandler.on($el, 'kt.drawer.hide', () => $wire.closeDrawer());
        })()"
        {{--
            drawer-direction is logical, not physical: the RTLCSS-flipped
            bundle maps "start" to the right edge on RTL locales and the LTR
            bundle maps it to the left edge, so the drawer docks on the
            correct side for every locale without hardcoding one.
        --}}
        data-kt-drawer="true" data-kt-drawer-name="chat-modal-{{ $this->getId() }}" data-kt-drawer-activate="true" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'300px', 'md': '500px'}" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_drawer_chat_modal_toggle_{{ $this->getId() }}" data-kt-drawer-close="#kt_drawer_chat_modal_close_{{ $this->getId() }}">
        @auth
            {{--
                Primary real-time mechanism (authenticated users only):
                subscribe to each loaded conversation's channel as soon as its
                id is known — ids load lazily after mount, so subscriptions go
                through window.listenToChatConversation (resources/js/echo.js)
                instead of getListeners(). Each MessageWasSent push triggers
                the same pollForReply() the old polls used. Guests cannot pass
                /broadcasting/auth (see routes/channels.php) and keep the poll
                fallbacks below.

                The typeof guard is load-bearing: these $watch callbacks run
                inside Livewire's response processing, so if the Vite bundle
                failed to load (e.g. stale cache after a deploy) an unguarded
                call would throw and abort the rest of the UI update. Guarded,
                a missing helper just degrades to the fallback poll.
            --}}
            <div class="d-none" x-data x-init="(() => {
                const subscribe = (id) => id
                    && typeof window.listenToChatConversation === 'function'
                    && window.listenToChatConversation(id, () => $wire.pollForReply());
                subscribe($wire.aiConversationId);
                subscribe($wire.companyConversationId);
                subscribe($wire.supportConversationId);
                $wire.$watch('aiConversationId', subscribe);
                $wire.$watch('companyConversationId', subscribe);
                $wire.$watch('supportConversationId', subscribe);
            })()"></div>
        @endauth
        <!--begin::Messenger-->
        <div class="card w-100 border-0 rounded-0" id="kt_drawer_chat_modal_messenger_{{ $this->getId() }}">
            <!--begin::کارت header-->
            <div class="card-header pe-5" id="kt_drawer_chat_modal_messenger_header_{{ $this->getId() }}">
                <!--begin::Title-->
                <div class="card-title">
                    <div class="d-flex justify-content-center flex-column me-3">
                        <span class="fs-4 fw-bold text-gray-900 me-1 mb-2 lh-1">
                            {{ $this->tabTitle() }}
                        </span>
                        <!--begin::Info-->
                        <div class="mb-0 lh-1">
                            <span class="badge badge-success badge-circle w-10px h-10px me-1"></span>
                            <span class="fs-7 fw-semibold text-muted">
                                {{ $this->tabStatus() }}
                            </span>
                        </div>
                        <!--end::Info-->

                        <!--begin::Tabs-->
                        <div class="btn-group btn-group-sm mt-2" role="group">
                            <button type="button" class="btn btn-sm {{ $activeTab === 'ai' ? 'btn-primary' : 'btn-light' }}" wire:click="switchTab('ai')">
                                {{ __('chat.tab_ai') }}
                            </button>
                            @if($companyId !== null && ! $isCompanyOwner)
                                <button type="button" class="btn btn-sm {{ $activeTab === 'company' ? 'btn-primary' : 'btn-light' }}" wire:click="switchTab('company')">
                                    {{ __('chat.tab_company') }}
                                </button>
                            @endif
                            <button type="button" class="btn btn-sm {{ $activeTab === 'support' ? 'btn-primary' : 'btn-light' }}" wire:click="switchTab('support')">
                                {{ __('chat.tab_support') }}
                            </button>
                        </div>
                        <!--end::Tabs-->
                    </div>
                </div>
                <!--end::Title-->
                <!--begin::کارت toolbar-->
                <div class="card-toolbar">
                    <!--begin::Close-->
                    <div class="btn btn-sm btn-icon btn-active-color-primary" id="kt_drawer_chat_modal_close_{{ $this->getId() }}">
                        <i class="ki-duotone ki-cross-square fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::کارت toolbar-->
            </div>
            <!--end::کارت header-->
            <!--begin::کارت body-->
            <div class="card-body" id="kt_drawer_chat_modal_messenger_body_{{ $this->getId() }}">
                @php
                    $activeMessages = match ($activeTab) {
                        'company' => $companyMessages,
                        'support' => $supportMessages,
                        default => $aiMessages,
                    };
                @endphp

                @if($activeTab === 'support' && $supportUnavailable)
                    <!--begin::Empty state (no agent available)-->
                    <div class="d-flex flex-column px-9">
                        <div class="pt-10 pb-0">
                            <h3 class="text-gray-900 text-center fw-bold">{{ __('chat.support_unavailable_title') }}</h3>
                            <div class="text-center text-gray-600 fw-semibold pt-1">{{ __('chat.support_unavailable_text') }}</div>
                        </div>
                        <div class="text-center px-4">
                            <img class="mw-100 mh-200px" alt="" src="{{ asset('theme/1/media/illustrations/sigma-1/1.png') }}" />
                        </div>
                    </div>
                    <!--end::Empty state (no agent available)-->
                @elseif($activeTab === 'company' && $companyError !== null)
                    <!--begin::Empty state (company contact unavailable)-->
                    <div class="d-flex flex-column px-9">
                        <div class="pt-10 pb-0">
                            <h3 class="text-gray-900 text-center fw-bold">{{ __('chat.company_chat_trigger') }}</h3>
                            <div class="text-center text-gray-600 fw-semibold pt-1">{{ $companyError }}</div>
                        </div>
                        <div class="text-center px-4">
                            <img class="mw-100 mh-200px" alt="" src="{{ asset('theme/1/media/illustrations/sigma-1/1.png') }}" />
                        </div>
                    </div>
                    <!--end::Empty state (company contact unavailable)-->
                @else
                    <!--begin::پیام ها-->
                    <div
                        class="scroll-y me-n5 pe-5"
                        data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_drawer_chat_modal_messenger_header_{{ $this->getId() }}, #kt_drawer_chat_modal_messenger_footer_{{ $this->getId() }}" data-kt-scroll-wrappers="#kt_drawer_chat_modal_messenger_body_{{ $this->getId() }}" data-kt-scroll-offset="0px"
                        data-chat-scroll
                        {{--
                            wire:ignore.self: KTScroll sets an inline height
                            style on this element (via data-kt-scroll) that
                            never appears in the server-rendered HTML. Without
                            ignoring this element's own attributes, every
                            morph (send, poll, Echo-triggered reply) strips
                            that inline style, the container stops
                            overflowing, and the drawer's own wrapper starts
                            scrolling instead — so the global 'morphed' hook's
                            scrollTop write in resources/js/echo.js lands on
                            an element that no longer has anything to scroll.
                            Children (messages) still morph normally.
                        --}}
                        wire:ignore.self
                        x-data
                        x-init="$el.scrollTop = $el.scrollHeight"
                    >
                        {{-- Scroll-to-bottom on send/receive/open is handled by the global 'morphed' Livewire hook in resources/js/echo.js via the data-chat-scroll marker above. --}}
                        @if($activeTab === 'ai' && $aiLoaded && empty($activeMessages) && ! $awaitingReply)
                            <!--begin::Empty state-->
                            <div class="text-center text-muted fs-6 py-10 px-5">
                                {{ $companyId !== null ? __('chat.company_empty_state', ['company' => $companyName]) : __('chat.empty_state') }}
                            </div>
                            <!--end::Empty state-->
                        @endif

                        @foreach($activeMessages as $msg)
                            @include('components.chat-elements.message-item', ['msg' => $msg, 'translations' => $translations])
                        @endforeach

                        @if($activeTab === 'ai' && $awaitingReply)
                            {{-- Guests still poll for the AI reply (no broadcasting auth); authenticated users get it pushed over Echo. --}}
                            <!--begin::Typing indicator-->
                            <div class="d-flex justify-content-start mb-3" @guest wire:poll.3s="pollForReply" @endguest>
                                <div class="p-3 rounded bg-light-info text-muted fs-7 fst-italic">
                                    {{ __('chat.typing') }}
                                </div>
                            </div>
                            <!--end::Typing indicator-->
                        @endif

                        @guest
                            {{-- Guests keep the pre-Reverb poll as their primary mechanism — they cannot subscribe to private channels. --}}
                            @if($open && $activeTab === 'company' && $companyConversationId !== null)
                                <div wire:poll.5s="pollForReply" class="d-none"></div>
                            @endif
                            @if($open && $activeTab === 'support' && $supportConversationId !== null)
                                <div wire:poll.5s="pollForReply" class="d-none"></div>
                            @endif
                        @else
                            {{-- FALLBACK ONLY, not the primary mechanism: Echo pushes above drive updates; this slow poll recovers missed/dropped WebSocket events. --}}
                            @if($open)
                                <div wire:poll.60s="pollForReply" class="d-none"></div>
                            @endif
                        @endguest
                    </div>
                    <!--end::پیام ها-->
                @endif
            </div>
            <!--end::کارت body-->
            <!--begin::کارت footer-->
            <div class="card-footer pt-4" id="kt_drawer_chat_modal_messenger_footer_{{ $this->getId() }}">
                @error('body')
                    <div class="text-danger fs-8 mb-2">{{ $message }}</div>
                @enderror
                @if($sendError)
                    <div class="text-danger fs-8 mb-2">{{ $sendError }}</div>
                @endif
                @php
                    $inputDisabled = ($activeTab === 'support' && $supportUnavailable)
                        || ($activeTab === 'company' && $companyError !== null);
                @endphp
                <!--begin::Input-->
                <textarea class="form-control form-control-flush mb-3" rows="1" wire:model="body" wire:keydown.enter.prevent="sendMessage" placeholder="{{ __('chat.placeholder') }}" @disabled($inputDisabled)></textarea>
                <!--end::Input-->
                <!--begin:Toolbar-->
                <div class="d-flex flex-stack justify-content-end">
                    <!--begin::ارسال-->
                    <button class="btn btn-primary" type="button" wire:click="sendMessage" wire:target="sendMessage" wire:loading.attr="disabled" @disabled($inputDisabled)>
                        {{ __('chat.send') }}
                    </button>
                    <!--end::ارسال-->
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::کارت footer-->
        </div>
        <!--end::Messenger-->
    </div>
    <!--end::chat drawer-->
</div>
