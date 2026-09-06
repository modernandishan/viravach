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

{{--
    Markup follows Metronic's Support Center template (apps/support-center:
    tickets/list.html for the hero + queue rows, tickets/view.html for the
    thread, and its #kt_modal_new_ticket modal for the create form). Every
    asset resolves from theme/1, which ships the same Metronic release.

    Direction: Bootstrap's me-*/ms-*/ps-*/pe-*/text-end utilities are logical
    here — the layout loads style.bundle.rtl.css for fa/ar and
    style.bundle.css otherwise — so no physical left/right is used anywhere
    below (DESIGN.md §8).
--}}
<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">
        {{-- Same infobar + tab bar every dashboard page renders; the Tickets
             tab activates itself via request()->routeIs('tickets'). --}}
        <livewire:dashboard-elements.infobar/>

        <!--begin::Hero-->
        <div class="bgi-no-repeat bgi-position-center bgi-size-cover d-flex flex-column justify-content-center h-200px h-lg-250px"
             style="background-image: url('{{ asset('theme/1/media/misc/menu-header-bg.jpg') }}')">
            <!--begin::Container-->
            <div class="container">
                <!--begin::Head-->
                <div class="d-flex flex-stack py-3 py-lg-8">
                    <!--begin::Title-->
                    <h2 class="fw-bold text-white pe-2 m-0">{{ __('tickets.title') }}</h2>
                    <!--end::Title-->
                    <!--begin::Actions-->
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#kt_modal_new_ticket">
                        {{ __('tickets.create_title') }}
                    </button>
                    <!--end::Actions-->
                </div>
                <!--end::Head-->
            </div>
            <!--end::Container-->
            <!--begin::Container-->
            <div class="container pb-5 pb-lg-10">
                <h3 class="fs-2x fw-bold text-white text-center m-0">{{ __('tickets.hero_subtitle') }}</h3>
            </div>
            <!--end::Container-->
        </div>
        <!--end::Hero-->
        <!--begin::Svg-->
        <div class="mt-n8 text-page-bg">
            <svg width="100%" height="56px" viewBox="0 0 100 100" version="1.1" preserveAspectRatio="none">
                <path d="M0,0 C16.6666667,66 33.3333333,99 50,99 C66.6666667,99 83.3333333,66 100,0 L100,100 L0,100 L0,0 Z" fill="currentColor"></path>
            </svg>
        </div>
        <!--end::Svg-->

        <!--begin::Card-->
        <div class="card">
            <!--begin::Card body-->
            <div class="card-body">
                <!--begin::Layout-->
                <div class="d-flex flex-column flex-xl-row p-md-7">
                    <!--begin::Sidebar-->
                    <div class="flex-column flex-xl-row-auto w-100 w-xl-350px mb-15 mb-xl-0 me-xl-15" wire:poll.60s="refreshThread">
                        <!--begin::Heading-->
                        <h1 class="text-gray-900 mb-10">{{ __('tickets.my_tickets') }}</h1>
                        <!--end::Heading-->
                        <!--begin::Tickets list-->
                        <div class="mb-0">
                            @forelse ($this->tickets as $ticket)
                                @php
                                    // Mirrors the demo's two row glyphs: an "incoming" file icon for
                                    // tickets still awaiting us, an "added" one once they are handled.
                                    $rowIcon = $ticket['status'] === 'open' ? 'ki-add-files' : 'ki-file-added';
                                    $rowIconPaths = $ticket['status'] === 'open' ? 3 : 2;
                                @endphp
                                <!--begin::Ticket-->
                                <div class="d-flex mb-10 cursor-pointer" wire:key="ticket-{{ $ticket['id'] }}" wire:click="selectTicket({{ $ticket['id'] }})">
                                    <!--begin::Symbol-->
                                    <i class="ki-duotone {{ $rowIcon }} fs-2x me-5 ms-n1 mt-2 text-{{ $ticket['status_color'] }}">
                                        @for ($path = 1; $path <= $rowIconPaths; $path++)
                                            <span class="path{{ $path }}"></span>
                                        @endfor
                                    </i>
                                    <!--end::Symbol-->
                                    <!--begin::Section-->
                                    <div class="d-flex flex-column overflow-hidden">
                                        <!--begin::Content-->
                                        <div class="d-flex align-items-center mb-2">
                                            <!--begin::Title-->
                                            <span class="fs-4 me-3 fw-semibold text-truncate {{ $selectedTicketId === $ticket['id'] ? 'text-primary' : 'text-gray-900 text-hover-primary' }}">{{ $ticket['subject'] }}</span>
                                            <!--end::Title-->
                                            <!--begin::Tags-->
                                            <span class="badge badge-light-{{ $ticket['status_color'] }} my-1 flex-shrink-0">{{ $ticket['status_label'] }}</span>
                                            <!--end::Tags-->
                                        </div>
                                        <!--end::Content-->
                                        <!--begin::Text-->
                                        {{-- <bdi> keeps reference numbers and dates internally LTR inside RTL copy
                                             without dragging the block's own alignment along (DESIGN.md §8). --}}
                                        <span class="text-muted fw-semibold fs-6"><bdi>{{ $ticket['reference_number'] }}</bdi></span>
                                        <span class="text-muted fw-semibold fs-7"><bdi>{{ $ticket['updated_at'] }}</bdi></span>
                                        <!--end::Text-->
                                    </div>
                                    <!--end::Section-->
                                </div>
                                <!--end::Ticket-->
                            @empty
                                <div class="text-muted fs-6 fw-semibold py-4">{{ __('tickets.empty') }}</div>
                            @endforelse
                        </div>
                        <!--end::Tickets list-->
                    </div>
                    <!--end::Sidebar-->

                    <!--begin::Content-->
                    <div class="flex-lg-row-fluid">
                        @if ($selectedTicketId === null)
                            <!--begin::Empty state-->
                            <div class="d-flex flex-column flex-center py-20">
                                <i class="ki-duotone ki-questionnaire-tablet fs-5tx text-gray-300 mb-6">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <div class="fs-4 fw-semibold text-muted">{{ __('tickets.select_hint') }}</div>
                            </div>
                            <!--end::Empty state-->
                        @else
                            @php
                                $selected = collect($this->tickets)->firstWhere('id', $selectedTicketId);
                                $selectedColor = $selected['status_color'] ?? 'secondary';
                            @endphp
                            <!--begin::Ticket view-->
                            <div class="mb-0">
                                <!--begin::Heading-->
                                <div class="d-flex align-items-start mb-12">
                                    <!--begin::Icon-->
                                    <i class="ki-duotone ki-file-added fs-4qx text-{{ $selectedColor }} ms-n2 me-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <!--end::Icon-->
                                    <!--begin::Content-->
                                    <div class="d-flex flex-column">
                                        <!--begin::Title-->
                                        <h1 class="text-gray-800 fw-semibold">{{ $selected['subject'] ?? '' }}</h1>
                                        <!--end::Title-->
                                        <!--begin::Info-->
                                        <div class="">
                                            <!--begin::Tags-->
                                            <span class="fw-semibold text-muted me-6">{{ __('tickets.reference') }}:
                                                <bdi class="fw-bold text-gray-600">{{ $selected['reference_number'] ?? '' }}</bdi></span>
                                            <!--end::Tags-->
                                            <!--begin::Tags-->
                                            <span class="fw-semibold text-muted me-6">{{ __('tickets.status') }}:
                                                <span class="badge badge-light-{{ $selectedColor }}">{{ $selected['status_label'] ?? '' }}</span></span>
                                            <!--end::Tags-->
                                            <!--begin::Tags-->
                                            <span class="fw-semibold text-muted">{{ __('tickets.updated') }}:
                                                <bdi class="fw-bold text-gray-600">{{ $selected['updated_at'] ?? '' }}</bdi></span>
                                            <!--end::Tags-->
                                        </div>
                                        <!--end::Info-->
                                    </div>
                                    <!--end::Content-->
                                </div>
                                <!--end::Heading-->

                                <!--begin::Comments-->
                                <div class="mb-15">
                                    @foreach ($messages as $message)
                                        @if ($message['type'] === 'system')
                                            <!--begin::System notice-->
                                            <div class="text-center text-muted fs-7 my-5" wire:key="msg-{{ $message['id'] }}">{{ $message['body'] }}</div>
                                            <!--end::System notice-->
                                        @else
                                            <!--begin::Comment-->
                                            <div class="mb-9 {{ $message['isOwn'] ? '' : 'ms-xl-9' }}" wire:key="msg-{{ $message['id'] }}">
                                                <!--begin::Card-->
                                                <div class="card card-bordered w-100 {{ $message['isOwn'] ? '' : 'bg-light-primary border-primary border-opacity-25' }}">
                                                    <!--begin::Body-->
                                                    <div class="card-body">
                                                        <!--begin::Wrapper-->
                                                        <div class="w-100 d-flex flex-stack mb-5">
                                                            <!--begin::Container-->
                                                            <div class="d-flex align-items-center">
                                                                <!--begin::Author-->
                                                                <div class="symbol symbol-50px me-5">
                                                                    @if ($message['senderAvatar'] ?? null)
                                                                        <img src="{{ $message['senderAvatar'] }}" alt="{{ $message['senderName'] }}"/>
                                                                    @else
                                                                        <div class="symbol-label fs-1 fw-bold {{ $message['isOwn'] ? 'bg-light-info text-info' : 'bg-light-primary text-primary' }}">{{ \Illuminate\Support\Str::substr($message['senderName'], 0, 1) }}</div>
                                                                    @endif
                                                                </div>
                                                                <!--end::Author-->
                                                                <!--begin::Info-->
                                                                <div class="d-flex flex-column fw-semibold fs-5 text-gray-600">
                                                                    <!--begin::Text-->
                                                                    <div class="d-flex align-items-center">
                                                                        <!--begin::Username-->
                                                                        <span class="text-gray-800 fw-bold fs-5 me-3">{{ $message['senderName'] }}</span>
                                                                        <!--end::Username-->
                                                                        @if ($message['isOwn'])
                                                                            <span class="badge badge-light-info">{{ __('tickets.requester') }}</span>
                                                                        @endif
                                                                    </div>
                                                                    <!--end::Text-->
                                                                    <!--begin::Date-->
                                                                    <span class="text-muted fw-semibold fs-6"><bdi>{{ $message['time'] }}</bdi></span>
                                                                    <!--end::Date-->
                                                                </div>
                                                                <!--end::Info-->
                                                            </div>
                                                            <!--end::Container-->
                                                        </div>
                                                        <!--end::Wrapper-->
                                                        <!--begin::Desc-->
                                                        <div class="fw-normal fs-5 text-gray-700 m-0" style="white-space: pre-wrap;" dir="auto">{!! ($message['bodyHtml'] ?? null) !== null ? $message['bodyHtml'] : e(trim($message['body'])) !!}</div>
                                                        <!--end::Desc-->
                                                    </div>
                                                    <!--end::Body-->
                                                </div>
                                                <!--end::Card-->
                                            </div>
                                            <!--end::Comment-->
                                        @endif
                                    @endforeach
                                </div>
                                <!--end::Comments-->

                                <!--begin::Reply-->
                                <div class="mb-0">
                                    <h3 class="text-gray-900 fw-semibold mb-5">{{ __('tickets.reply_title') }}</h3>
                                    @if ($sendError !== null)
                                        <div class="fs-7 text-danger mb-3">{{ $sendError }}</div>
                                    @endif
                                    @error('replyBody') <div class="fs-7 text-danger mb-3">{{ $message }}</div> @enderror
                                    <form wire:submit="sendReply">
                                        <textarea class="form-control form-control-solid fw-bold fs-4 ps-9 pt-7" rows="6" wire:model="replyBody" placeholder="{{ __('tickets.reply_placeholder') }}"></textarea>
                                        <div class="d-flex justify-content-end mt-n20 mb-10 pe-7 position-relative">
                                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">{{ __('tickets.send') }}</button>
                                        </div>
                                    </form>
                                </div>
                                <!--end::Reply-->
                            </div>
                            <!--end::Ticket view-->
                        @endif
                    </div>
                    <!--end::Content-->
                </div>
                <!--end::Layout-->
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->

        <!--begin::Modal - Create ticket-->
        {{--
            The create form lives in the demo's #kt_modal_new_ticket modal.
            createTicket() already dispatches 'ticket-created' on success, so
            the modal closes itself on that browser event; a validation
            failure dispatches nothing and the modal stays open with errors.
        --}}
        <div class="modal fade" id="kt_modal_new_ticket" tabindex="-1" aria-hidden="true"
             x-data
             x-on:ticket-created.window="window.bootstrap && window.bootstrap.Modal.getOrCreateInstance($el).hide()">
            <!--begin::Modal dialog-->
            <div class="modal-dialog modal-dialog-centered mw-750px">
                <!--begin::Modal content-->
                <div class="modal-content rounded">
                    <!--begin::Modal header-->
                    <div class="modal-header pb-0 border-0 justify-content-end">
                        <!--begin::Close-->
                        <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                            <i class="ki-duotone ki-cross fs-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>
                        <!--end::Close-->
                    </div>
                    <!--end::Modal header-->
                    <!--begin::Modal body-->
                    <div class="modal-body scroll-y px-10 px-lg-15 pt-0 pb-15">
                        <!--begin::Form-->
                        <form id="kt_modal_new_ticket_form" class="form" wire:submit="createTicket">
                            <!--begin::Heading-->
                            <div class="mb-13 text-center">
                                <!--begin::Title-->
                                <h1 class="mb-3">{{ __('tickets.create_title') }}</h1>
                                <!--end::Title-->
                                <!--begin::Description-->
                                <div class="text-gray-500 fw-semibold fs-5">{{ __('tickets.create_hint') }}</div>
                                <!--end::Description-->
                            </div>
                            <!--end::Heading-->
                            <!--begin::Input group-->
                            <div class="d-flex flex-column mb-8 fv-row">
                                <!--begin::Label-->
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2" for="ticket-subject">
                                    <span class="required">{{ __('tickets.subject') }}</span>
                                    <span class="ms-2" data-bs-toggle="tooltip" title="{{ __('tickets.subject_hint') }}">
                                        <i class="ki-duotone ki-information fs-7">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                    </span>
                                </label>
                                <!--end::Label-->
                                <input id="ticket-subject" type="text" class="form-control form-control-solid" wire:model="subject" maxlength="150" placeholder="{{ __('tickets.subject_placeholder') }}">
                                @error('subject') <div class="fs-7 text-danger mt-2">{{ $message }}</div> @enderror
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="d-flex flex-column mb-8 fv-row">
                                <label class="required fs-6 fw-semibold mb-2" for="ticket-body">{{ __('tickets.message') }}</label>
                                <textarea id="ticket-body" class="form-control form-control-solid" rows="5" wire:model="createBody" placeholder="{{ __('tickets.message_placeholder') }}"></textarea>
                                @error('createBody') <div class="fs-7 text-danger mt-2">{{ $message }}</div> @enderror
                            </div>
                            <!--end::Input group-->
                            <!--begin::Actions-->
                            <div class="text-center">
                                <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('tickets.cancel') }}</button>
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="createTicket">{{ __('tickets.submit') }}</span>
                                    <span wire:loading wire:target="createTicket">{{ __('tickets.please_wait') }}
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                            <!--end::Actions-->
                        </form>
                        <!--end::Form-->
                    </div>
                    <!--end::Modal body-->
                </div>
                <!--end::Modal content-->
            </div>
            <!--end::Modal dialog-->
        </div>
        <!--end::Modal - Create ticket-->
    </div>
</div>
