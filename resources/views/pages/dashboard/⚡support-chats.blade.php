<?php

use App\Enums\TicketStatus;
use App\Events\ChatConversationStarted;
use App\Models\Ticket;
use App\Models\Company;
use App\Models\User;
use App\Services\Chat\Exceptions\TranslationException;
use App\Services\Chat\TicketService;
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
        } elseif ($type === 'ticket') {
            // Ticket threads are staff-visible by role, not participation —
            // the shared queue means every support/admin/super_admin user can
            // open any ticket conversation.
            abort_unless(Ticket::isStaff(auth()->user())
                && Ticket::query()->where('conversation_id', $conversation->id)->exists(), 403);
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

        $ticket = $this->selectedTicket();

        if ($ticket !== null) {
            // Claiming the ticket: addStaffReply joins the staff member as an
            // explicit participant (musonza read bookkeeping), sends the
            // message and moves the ticket to answered.
            $message = app(TicketService::class)->addStaffReply($ticket, $actingAs, $body);
        } else {
            $message = Chat::message($body)->from($actingAs)->to($conversation)->send();
        }

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

        // Ticket queue: every staff member (the same fallback roles
        // SupportTransferService draws from) sees ALL open/answered tickets —
        // they are Ticket rows, not participations, so no assignment is
        // involved and any staff member can pick one up. Closed tickets leave
        // the queue.
        if (Ticket::isStaff($user)) {
            $rows = $rows->merge(
                Ticket::query()
                    ->whereIn('status', [TicketStatus::Open, TicketStatus::Answered])
                    ->with('user')
                    ->orderByDesc('updated_at')
                    ->get()
                    ->map(fn (Ticket $ticket) => $this->presentTicketConversation($ticket))
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
     * A ticket row rendered as an inbox entry: participantName carries the
     * creator, companyName slot shows the reference number, and the type tag
     * routes the thread through the ticket authorization path.
     *
     * @return array{id: int, type: 'ticket', companyId: null, companyName: ?string, participantName: string, lastMessage: ?string, unreadCount: int, updatedAt: ?string, updatedAtSort: ?string, ticketStatus: ?string, ticketStatusLabel: ?string, ticketStatusColor: ?string, ticketId: int}
     */
    protected function presentTicketConversation(Ticket $ticket): array
    {
        $conversation = $ticket->conversation;

        return [
            'id' => $conversation->id,
            'type' => 'ticket',
            'companyId' => null,
            'companyName' => $ticket->reference_number.' · '.__('tickets.status_'.$ticket->status->value),
            'participantName' => $ticket->subject,
            'lastMessage' => $conversation->last_message?->body,
            'unreadCount' => 0,
            'updatedAt' => $ticket->updated_at?->format('Y/m/d H:i'),
            'updatedAtSort' => $ticket->updated_at?->toIso8601String(),
            'ticketId' => $ticket->id,
            'ticketStatusColor' => $ticket->status->getColor(),
            'creatorName' => (string) ($ticket->user?->getParticipantDetails()['name'] ?? ''),
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
     * The Ticket behind the currently selected conversation, when it is a
     * ticket thread; null for support/company rows.
     */
    protected function selectedTicket(): ?Ticket
    {
        if ($this->selectedType !== 'ticket') {
            return null;
        }

        return Ticket::query()
            ->where('conversation_id', $this->selectedConversationId)
            ->first();
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

{{--
    Queue rows and the ticket thread follow Metronic's Support Center
    template (apps/support-center/tickets/list.html and tickets/view.html);
    assets all resolve from theme/1, the same Metronic release. Bootstrap's
    me-*/ms-*/ps-*/pe-* utilities are logical here (the layout swaps
    style.bundle.rtl.css in for fa/ar), so nothing below uses a physical
    left/right (DESIGN.md §8).
--}}
<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">
        {{-- Same infobar + tab bar as the rest of the dashboard. This inbox has
             no tab of its own (it is role-gated — support agents and company
             owners only — so a permanently visible tab would be wrong for most
             users), which means no tab shows as active here. The navigation
             back to the other dashboard pages is still worth having, and the
             scroll panes below are fixed-height (mh-500px / mh-400px), so the
             infobar changes nothing structurally. --}}
        <livewire:dashboard-elements.infobar/>

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
                        <div class="scroll-y mh-500px pe-md-4">
                            @forelse($conversations as $conversation)
                                @php
                                    // Ticket rows carry the demo's colour-coded file glyph; support and
                                    // company threads keep the participant initial they already had.
                                    $isTicket = $conversation['type'] === 'ticket';
                                    $ticketColor = $conversation['ticketStatusColor'] ?? 'primary';
                                    $ticketIcon = $ticketColor === 'warning' ? 'ki-add-files' : 'ki-file-added';
                                    $ticketIconPaths = $ticketColor === 'warning' ? 3 : 2;
                                @endphp
                                <!--begin::Conversation item-->
                                <div
                                    class="d-flex p-4 mb-2 rounded cursor-pointer {{ $selectedConversationId === $conversation['id'] ? 'bg-light-primary' : 'bg-hover-light' }}"
                                    wire:click="selectConversation({{ $conversation['id'] }}, '{{ $conversation['type'] }}'{{ $conversation['companyId'] !== null ? ', '.$conversation['companyId'] : '' }})"
                                    wire:key="support-conversation-{{ $conversation['id'] }}"
                                >
                                    @if($isTicket)
                                        <!--begin::Symbol-->
                                        <i class="ki-duotone {{ $ticketIcon }} fs-2x me-4 ms-n1 mt-1 text-{{ $ticketColor }}">
                                            @for($path = 1; $path <= $ticketIconPaths; $path++)
                                                <span class="path{{ $path }}"></span>
                                            @endfor
                                        </i>
                                        <!--end::Symbol-->
                                    @else
                                        <div class="symbol symbol-40px me-3">
                                            <span class="symbol-label bg-light-info text-info fw-bold">
                                                {{ \Illuminate\Support\Str::substr($conversation['participantName'], 0, 1) }}
                                            </span>
                                        </div>
                                    @endif
                                    <!--begin::Section-->
                                    <div class="flex-grow-1 overflow-hidden">
                                        <!--begin::Content-->
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold fs-6 text-truncate {{ $selectedConversationId === $conversation['id'] ? 'text-primary' : 'text-gray-900 text-hover-primary' }}">{{ $conversation['participantName'] }}</span>
                                            @if($conversation['unreadCount'] > 0)
                                                <span class="badge badge-circle badge-primary flex-shrink-0 ms-2">{{ $conversation['unreadCount'] }}</span>
                                            @endif
                                        </div>
                                        <!--end::Content-->
                                        @if($isTicket && ($conversation['creatorName'] ?? null))
                                            <div class="text-muted fw-semibold fs-8 text-truncate">{{ $conversation['creatorName'] }}</div>
                                        @endif
                                        @if($conversation['companyName'])
                                            {{-- Ticket rows put "reference · status" here; company rows put the company name. --}}
                                            <div class="fw-semibold fs-8 text-truncate {{ $isTicket ? 'text-'.$ticketColor : 'text-muted' }}">{{ $conversation['companyName'] }}</div>
                                        @endif
                                        <!--begin::Text-->
                                        <div class="text-muted fw-semibold fs-8 text-truncate">
                                            <bdi>{{ $conversation['lastMessage'] ?? __('chat.no_message_preview') }}</bdi>
                                        </div>
                                        <div class="text-muted fs-9"><bdi>{{ $conversation['updatedAt'] }}</bdi></div>
                                        <!--end::Text-->
                                    </div>
                                    <!--end::Section-->
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
                            <div class="d-flex flex-column flex-center flex-grow-1 py-10">
                                <i class="ki-duotone ki-questionnaire-tablet fs-5tx text-gray-300 mb-6">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <div class="fs-4 fw-semibold text-muted">{{ __('chat.select_conversation') }}</div>
                            </div>
                        @else
                            @php $selectedTicketRow = collect($conversations)->firstWhere('id', $selectedConversationId); @endphp
                            @if($selectedType === 'ticket' && $selectedTicketRow)
                                @php $selectedTicketColor = $selectedTicketRow['ticketStatusColor'] ?? 'primary'; @endphp
                                <!--begin::Ticket heading-->
                                <div class="d-flex align-items-start mb-8">
                                    <!--begin::Icon-->
                                    <i class="ki-duotone ki-file-added fs-3qx text-{{ $selectedTicketColor }} ms-n2 me-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <!--end::Icon-->
                                    <!--begin::Content-->
                                    <div class="d-flex flex-column">
                                        <h2 class="text-gray-800 fw-semibold mb-1">{{ $selectedTicketRow['participantName'] }}</h2>
                                        <div>
                                            @if($selectedTicketRow['creatorName'] ?? null)
                                                <span class="fw-semibold text-muted me-6">{{ __('tickets.requester') }}:
                                                    <span class="fw-bold text-gray-600">{{ $selectedTicketRow['creatorName'] }}</span></span>
                                            @endif
                                            @if($selectedTicketRow['companyName'])
                                                {{-- "reference · status" is a mixed-script string, so it follows the paragraph's
                                                     own direction — forcing LTR flips the two halves round in fa/ar (DESIGN.md §8). --}}
                                                <span class="fw-semibold text-muted">{{ __('tickets.reference') }}:
                                                    <span class="fw-bold text-gray-600">{{ $selectedTicketRow['companyName'] }}</span></span>
                                            @endif
                                        </div>
                                    </div>
                                    <!--end::Content-->
                                </div>
                                <!--end::Ticket heading-->
                            @endif
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
                                    @if($selectedType === 'ticket')
                                        {{--
                                            Ticket threads render the demo's comment card instead of the
                                            shared bubble. Every capability of
                                            components/chat-elements/message-item is carried over verbatim:
                                            the system-notice branch, the sanitized bodyHtml fallback, the
                                            sender avatar, and the toggleTranslation() control (support here
                                            is multilingual, so translation must not be lost) — only the
                                            markup and classes differ.
                                        --}}
                                        @if($msg['type'] === 'system')
                                            <!--begin::System notice-->
                                            <div class="text-center text-muted fs-8 my-3" wire:key="chat-message-{{ $msg['id'] }}">
                                                {{ $msg['body'] }}
                                            </div>
                                            <!--end::System notice-->
                                        @else
                                            <!--begin::Comment-->
                                            <div class="mb-5 {{ $msg['isOwn'] ? '' : 'ms-lg-9' }}" wire:key="chat-message-{{ $msg['id'] }}">
                                                <!--begin::Card-->
                                                <div class="card card-bordered w-100 {{ $msg['isOwn'] ? '' : 'bg-light-primary border-primary border-opacity-25' }}">
                                                    <!--begin::Body-->
                                                    <div class="card-body p-5">
                                                        <!--begin::Wrapper-->
                                                        <div class="w-100 d-flex flex-stack mb-4">
                                                            <!--begin::Container-->
                                                            <div class="d-flex align-items-center">
                                                                <!--begin::Author-->
                                                                <div class="symbol symbol-45px me-4">
                                                                    @if($msg['senderAvatar'] ?? null)
                                                                        <img src="{{ $msg['senderAvatar'] }}" alt="{{ $msg['senderName'] }}"/>
                                                                    @else
                                                                        <div class="symbol-label fs-2 fw-bold {{ $msg['isOwn'] ? 'bg-light-info text-info' : 'bg-light-primary text-primary' }}">{{ \Illuminate\Support\Str::substr($msg['senderName'], 0, 1) }}</div>
                                                                    @endif
                                                                </div>
                                                                <!--end::Author-->
                                                                <!--begin::Info-->
                                                                <div class="d-flex flex-column fw-semibold text-gray-600">
                                                                    <!--begin::Text-->
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="text-gray-800 fw-bold fs-6 me-3">{{ $msg['senderName'] }}</span>
                                                                        @unless($msg['isOwn'])
                                                                            <span class="badge badge-light-info">{{ __('tickets.requester') }}</span>
                                                                        @endunless
                                                                    </div>
                                                                    <!--end::Text-->
                                                                    <!--begin::Date-->
                                                                    <span class="text-muted fw-semibold fs-7"><bdi>{{ $msg['time'] }}</bdi></span>
                                                                    <!--end::Date-->
                                                                </div>
                                                                <!--end::Info-->
                                                            </div>
                                                            <!--end::Container-->
                                                            <!--begin::Actions-->
                                                            {{-- ViraBot replies are already in the user's language, so translating them is wrong and wasteful. --}}
                                                            @unless($msg['isOwn'] || ($msg['senderType'] ?? null) === 'ai')
                                                                <div class="m-0">
                                                                    <button type="button" class="btn btn-color-gray-500 btn-active-color-primary p-0 fw-bold fs-7" wire:click="toggleTranslation({{ $msg['id'] }})" wire:target="toggleTranslation({{ $msg['id'] }})" wire:loading.attr="disabled">
                                                                        <span wire:loading.remove wire:target="toggleTranslation({{ $msg['id'] }})">
                                                                            {{ ($translations[$msg['id']]['visible'] ?? false) ? __('chat.hide_translation') : __('chat.translate_link') }}
                                                                        </span>
                                                                        <span wire:loading wire:target="toggleTranslation({{ $msg['id'] }})" class="spinner-border spinner-border-sm align-middle"></span>
                                                                    </button>
                                                                </div>
                                                            @endunless
                                                            <!--end::Actions-->
                                                        </div>
                                                        <!--end::Wrapper-->
                                                        <!--begin::Desc-->
                                                        <div class="fw-normal fs-6 text-gray-700 m-0" style="white-space: pre-wrap;" dir="auto">{!! ($msg['bodyHtml'] ?? null) !== null ? $msg['bodyHtml'] : e(trim($msg['body'])) !!}</div>
                                                        <!--end::Desc-->
                                                        @if($translations[$msg['id']]['visible'] ?? false)
                                                            <!--begin::Translation-->
                                                            <div class="text-muted fs-7 fw-normal mt-3 pt-3 border-top border-gray-300" style="white-space: pre-wrap;" dir="auto">
                                                                @if($translations[$msg['id']]['error'] ?? null)
                                                                    <span class="text-danger">{{ $translations[$msg['id']]['error'] }}</span>
                                                                @else
                                                                    {{ $translations[$msg['id']]['text'] ?? '' }}
                                                                @endif
                                                            </div>
                                                            <!--end::Translation-->
                                                        @endif
                                                    </div>
                                                    <!--end::Body-->
                                                </div>
                                                <!--end::Card-->
                                            </div>
                                            <!--end::Comment-->
                                        @endif
                                    @else
                                        @include('components.chat-elements.message-item', ['msg' => $msg, 'translations' => $translations])
                                    @endif
                                @endforeach
                            </div>

                            @error('body')
                                <div class="text-danger fs-8 mb-2">{{ $message }}</div>
                            @enderror
                            @if($sendError)
                                <div class="text-danger fs-8 mb-2">{{ $sendError }}</div>
                            @endif

                            <textarea class="form-control form-control-solid mb-3" rows="2" wire:model="body" wire:keydown.enter.prevent="sendMessage" placeholder="{{ __('chat.placeholder') }}"></textarea>
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
