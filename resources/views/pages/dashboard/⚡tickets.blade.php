<?php

use App\Enums\TicketStatus;
use App\Events\ChatConversationStarted;
use App\Events\TicketCreated;
use App\Livewire\Concerns\InteractsWithChatMessages;
use App\Models\Ticket;
use App\Services\Chat\TicketService;
use App\Support\LocalizedDate;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Musonza\Chat\Models\Conversation;

new
#[Layout('layouts::landing')]
class extends Component {
    use InteractsWithChatMessages;

    public string $subject = '';

    public string $createBody = '';

    public ?int $selectedTicketId = null;

    /**
     * @var array<int, array{id: int, body: string, bodyHtml: ?string, senderName: string, senderType: ?string, senderAvatar: ?string, isOwn: bool, time: ?string, type: string}>
     */
    public array $messages = [];

    public string $replyBody = '';

    public ?string $sendError = null;

    public function mount(): void
    {
        $this->loadTickets();
    }

    /**
     * The user's own tickets, newest first.
     *
     * @return array<int, array{id: int, reference_number: string, subject: string, status: string, status_label: string, status_color: string, updated_at: string, sort_key: string}>
     */
    public function getTicketsProperty(): array
    {
        return auth()->user()
            ->tickets()
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Ticket $ticket): array => [
                'id' => $ticket->id,
                'reference_number' => $ticket->reference_number,
                'subject' => $ticket->subject,
                'status' => $ticket->status->value,
                'status_label' => $ticket->status->getLabel(),
                'status_color' => $ticket->status->getColor(),
                'updated_at' => LocalizedDate::format($ticket->updated_at, LocalizedDate::FORMAT_DATETIME),
                'sort_key' => (string) $ticket->updated_at,
            ])
            ->all();
    }

    public function createTicket(): void
    {
        $this->validate(
            [
                'subject' => ['required', 'string', 'max:150'],
                'createBody' => ['required', 'string', 'max:5000'],
            ],
            [
                'subject.required' => __('tickets.subject_required'),
                'subject.max' => __('tickets.subject_too_long'),
                'createBody.required' => __('tickets.message_required'),
                'createBody.max' => __('tickets.message_too_long'),
            ],
        );

        app(TicketService::class)->create(auth()->user(), $this->subject, $this->createBody);

        $this->reset('subject', 'createBody');
        $this->dispatch('ticket-created');
    }

    /**
     * Open a ticket's thread. Only the owner may view it; staff reach
     * tickets from the support-chats inbox instead.
     */
    public function selectTicket(int $ticketId): void
    {
        $ticket = Ticket::findOrFail($ticketId);

        abort_unless($ticket->user_id === auth()->id(), 403);

        $this->selectedTicketId = $ticketId;
        $this->messages = $this->fetchMessages($ticket->conversation, $this->participant());
        $this->sendError = null;

        Chat::conversation($ticket->conversation)->setParticipant($this->participant())->readAll();
    }

    public function sendReply(): void
    {
        $this->sendError = null;

        $this->validate(
            ['replyBody' => ['required', 'string', 'max:2000']],
            [
                'replyBody.required' => __('tickets.message_required'),
                'replyBody.max' => __('tickets.message_too_long'),
            ],
        );

        $ticket = Ticket::findOrFail((int) $this->selectedTicketId);

        abort_unless($ticket->user_id === auth()->id(), 403);

        if (RateLimiter::tooManyAttempts($this->sendRateLimitKey($this->participant()), $this->sendRateLimitMax())) {
            $this->sendError = __('chat.rate_limited_send');

            return;
        }

        app(TicketService::class)->addUserReply($ticket, auth()->user(), $this->replyBody);

        $message = $ticket->conversation->messages()->orderByDesc('id')->first();

        $this->messages[] = $this->presentMessage($message->load('participation.messageable'), $this->participant());

        $this->reset('replyBody');

        Chat::conversation($ticket->conversation)->setParticipant($this->participant())->readAll();
    }

    public function locateMessageBody(int $messageId): ?string
    {
        foreach ($this->messages as $message) {
            if ($message['id'] === $messageId) {
                return $message['body'];
            }
        }

        return null;
    }

    public function getListeners(): array
    {
        $user = auth()->user();

        $listeners = [
            // New staff reply in the open thread (musonza's own conversation
            // channel), same subscription ⚡chat uses.
            "echo-private:mc-chat-conversation.{$this->selectedTicketId},.Musonza\\Chat\\Eventing\\MessageWasSent" => 'refreshThread',
        ];

        foreach ($user->tickets()->pluck('conversation_id') as $conversationId) {
            $listeners["echo-private:mc-chat-conversation.{$conversationId},.Musonza\\Chat\\Eventing\\MessageWasSent"] = 'refreshThread';
        }

        return $listeners;
    }

    /**
     * Echo handler for musonza's MessageWasSent: reload the open thread (a
     * staff reply arrived) and the ticket list (its status may have moved).
     */
    public function refreshThread(): void
    {
        if ($this->selectedTicketId !== null) {
            $ticket = Ticket::find($this->selectedTicketId);

            if ($ticket) {
                $this->messages = $this->fetchMessages($ticket->conversation, $this->participant());
            }
        }
    }

    protected function loadTickets(): void
    {
        unset($this->tickets);
    }

    public function render()
    {
        return $this->view()->title(__('tickets.title').' | '.__('globals.viravach'));
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">
        {{-- Same infobar + tab bar every dashboard page renders; the Tickets
             tab activates itself via request()->routeIs('tickets'). --}}
        <livewire:dashboard-elements.infobar/>

        <div class="d-flex flex-column flex-lg-row">
            <!--begin::Sidebar-->
            <div class="flex-column flex-lg-row-auto w-100 w-lg-350px mb-10 mb-lg-0">
                {{-- Create form --}}
                <div class="card card-flush mb-6">
                    <div class="card-header pt-7">
                        <div class="card-title"><h2 class="fs-4">{{ __('tickets.create_title') }}</h2></div>
                    </div>
                    <div class="card-body">
                        <form wire:submit="createTicket">
                            <div class="mb-4">
                                <label class="fs-7 fw-semibold text-gray-600 mb-2 d-block" for="ticket-subject">{{ __('tickets.subject') }}</label>
                                <input id="ticket-subject" type="text" class="form-control form-control-solid" wire:model="subject" maxlength="150">
                                @error('subject') <div class="fs-8 text-danger mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-5">
                                <label class="fs-7 fw-semibold text-gray-600 mb-2 d-block" for="ticket-body">{{ __('tickets.message') }}</label>
                                <textarea id="ticket-body" class="form-control form-control-solid" rows="4" wire:model="createBody"></textarea>
                                @error('createBody') <div class="fs-8 text-danger mt-1">{{ $message }}</div> @enderror
                            </div>
                            <button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled">
                                {{ __('tickets.submit') }}
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Own tickets list --}}
                <div class="card card-flush" wire:poll.60s="refreshThread">
                    <div class="card-header pt-7">
                        <div class="card-title"><h2 class="fs-4">{{ __('tickets.title') }}</h2></div>
                    </div>
                    <div class="card-body pt-4">
                        @forelse ($this->tickets as $ticket)
                            <div class="menu-item rounded p-2 mb-1 cursor-pointer {{ $selectedTicketId === $ticket['id'] ? 'bg-light-primary' : '' }}" wire:key="ticket-{{ $ticket['id'] }}" wire:click="selectTicket({{ $ticket['id'] }})">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-gray-800 text-truncate">{{ $ticket['subject'] }}</span>
                                    <span class="badge badge-light-{{ $ticket['status_color'] }} flex-shrink-0 ms-2">{{ $ticket['status_label'] }}</span>
                                </div>
                                <div class="fs-8 text-muted mt-1">{{ $ticket['reference_number'] }} · {{ $ticket['updated_at'] }}</div>
                            </div>
                        @empty
                            <div class="text-muted fs-7 py-4 text-center">{{ __('tickets.empty') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <!--end::Sidebar-->

            <!--begin::Thread-->
            <div class="flex-column flex-lg-row-fluid w-100 mb-10">
                <div class="card card-flush h-lg-700px d-flex flex-column">
                    <div class="card-header pt-7">
                        <div class="card-title">
                            @if ($selectedTicketId !== null)
                                @php $selected = collect($this->tickets)->firstWhere('id', $selectedTicketId); @endphp
                                <h2 class="fs-4">{{ $selected['subject'] ?? '' }}</h2>
                                <span class="badge badge-light-{{ $selected['status_color'] ?? 'secondary' }} ms-3">{{ $selected['status_label'] ?? '' }}</span>
                                <span class="fs-8 text-muted ms-3">{{ $selected['reference_number'] ?? '' }}</span>
                            @else
                                <h2 class="fs-4 text-muted">{{ __('tickets.select_hint') }}</h2>
                            @endif
                        </div>
                    </div>

                    <div class="card-body flex-row-fluid overflow-y-auto">
                        @foreach ($messages as $message)
                            <div class="d-flex {{ $message['isOwn'] ? 'justify-content-end' : 'justify-content-start' }} mb-4" wire:key="msg-{{ $message['id'] }}">
                                <div class="max-w-600px">
                                    @if (! $message['isOwn'])
                                        <div class="fs-8 fw-semibold text-muted mb-1">{{ $message['senderName'] }}</div>
                                    @endif
                                    <div class="p-4 rounded {{ $message['isOwn'] ? 'bg-light-primary' : 'bg-light' }}">
                                        <div class="fs-7">{{ $message['body'] }}</div>
                                    </div>
                                    <div class="fs-8 text-muted mt-1 {{ $message['isOwn'] ? 'text-end' : '' }}">{{ $message['time'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($selectedTicketId !== null)
                        <div class="card-footer border-0 pt-0">
                            @if ($sendError !== null)
                                <div class="fs-8 text-danger mb-2">{{ $sendError }}</div>
                            @endif
                            <form wire:submit="sendReply" class="d-flex gap-3">
                                <input type="text" class="form-control form-control-solid" wire:model="replyBody" placeholder="{{ __('tickets.reply_placeholder') }}" autocomplete="off">
                                <button type="submit" class="btn btn-primary flex-shrink-0" wire:loading.attr="disabled">{{ __('tickets.send') }}</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
            <!--end::Thread-->
        </div>
    </div>
</div>
