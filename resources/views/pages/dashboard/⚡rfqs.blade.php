<?php

use App\Enums\RfqStatus;
use App\Models\Rfq;
use App\Support\LocalizedDate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts::landing')]
class extends Component {
    use WithPagination;

    /**
     * Small on purpose: the list is a sidebar column next to the detail
     * pane, bounded to roughly six rows before it scrolls, so a page holds
     * a little more than fits and the controls stay one click away.
     * ⚡company-list's PER_PAGE = 12 sizes a full-width grid instead.
     */
    private const PER_PAGE = 10;

    /** Filter: an RfqStatus value, or '' for every status. */
    public string $statusFilter = '';

    /** Sort direction on created_at — the only column worth ordering by here. */
    public string $sortDirection = 'desc';

    public ?int $selectedRfqId = null;

    /**
     * Every RFQ addressed to a company this user owns, newest first.
     *
     * Scoping is by companies.user_id, the same ownership column the rest of
     * the dashboard uses. It is applied here AND re-checked in every action
     * that takes an id, so a crafted request cannot reach another company's
     * inbox by guessing a primary key.
     */
    protected function scopedRfqs(): Builder
    {
        return Rfq::query()->whereIn(
            'company_id',
            auth()->user()->companies()->select('id'),
        );
    }

    /**
     * One page of the list, carrying no buyer details.
     *
     * A list row is a pointer, not a preview: the buyer's name, address and
     * phone belong to the request the owner has deliberately opened, not to
     * a column that sits on screen next to whoever walks past the desk. Only
     * the id, the company, the buyer's language and the date survive here —
     * everything else is read from the model in the detail pane.
     *
     * @return LengthAwarePaginator<int, array{id: int, locale: string, status: string, status_label: string, status_color: string, created_at: string, company_name: string}>
     */
    public function getRfqsProperty(): LengthAwarePaginator
    {
        return $this->scopedRfqs()
            ->when(
                $this->statusFilter !== '',
                fn (Builder $query) => $query->where('status', $this->statusFilter),
            )
            ->with('company')
            ->orderBy('created_at', $this->sortDirection === 'asc' ? 'asc' : 'desc')
            ->paginate(self::PER_PAGE)
            ->through(fn (Rfq $rfq): array => [
                'id' => $rfq->id,
                'locale' => $rfq->locale,
                'status' => $rfq->status->value,
                'status_label' => $rfq->status->getLabel(),
                'status_color' => $rfq->status->getColor(),
                'created_at' => LocalizedDate::format($rfq->created_at, LocalizedDate::FORMAT_DATETIME),
                'company_name' => (string) $rfq->company?->name,
            ]);
    }

    /** The open request, or null when nothing is selected. */
    public function selectedRfq(): ?Rfq
    {
        if ($this->selectedRfqId === null) {
            return null;
        }

        return $this->scopedRfqs()->with('company')->find($this->selectedRfqId);
    }

    /**
     * Page 3 of the old filter is not page 3 of the new one — and is often
     * past the end of it, which renders an empty list with no way back.
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /** Flips the created_at ordering; the list is the only thing that changes. */
    public function toggleSort(): void
    {
        $this->sortDirection = $this->sortDirection === 'desc' ? 'asc' : 'desc';
        $this->resetPage();
    }

    /** A closed request stays readable; it just loses the close button. */
    public function canClose(): bool
    {
        return $this->selectedRfq()?->status !== RfqStatus::Closed;
    }

    public function selectRfq(int $rfqId): void
    {
        $rfq = $this->scopedRfqs()->find($rfqId);

        abort_if($rfq === null, 404);

        $this->selectedRfqId = $rfq->id;
    }

    /**
     * The only transition the app performs. Closing means "I have dealt with
     * this", whether the owner emailed, phoned or decided to ignore it.
     *
     * Two enum cases are now reserved rather than reachable:
     *
     *  - Expired — no automation ages requests out yet; reserved for a future
     *    scheduled command.
     *  - Responded — was written by the in-platform reply, which is gone. With
     *    the owner answering over email or phone, the app never learns that a
     *    reply happened, so it cannot honestly set it. Rows written before the
     *    removal keep the value and stay filterable.
     *
     * That leaves pending → closed as the whole working lifecycle, which is
     * what the inbox badge already counts (RfqStatus::needsAttention()).
     */
    public function closeRfq(): void
    {
        $rfq = $this->selectedRfq();

        abort_if($rfq === null, 404);

        $rfq->update(['status' => RfqStatus::Closed]);

        session()->flash('flash_success', __('rfq.closed'));
    }

    /**
     * The filter offers only the lifecycle the app actually writes.
     *
     * Responded and Expired stay defined on the enum (closeRfq() explains
     * why) and rows that already carry them stay filterable if $statusFilter
     * is set to one — the query below does not care where the value came
     * from. They are simply not offered as choices, because picking one
     * today can only ever return old rows, and never anything the owner did.
     *
     * @return array<string, string>
     */
    public function statusOptions(): array
    {
        $options = ['' => __('rfq.filter_all_statuses')];

        foreach ([RfqStatus::Pending, RfqStatus::Closed] as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }

    public function render()
    {
        return $this->view()->title(__('rfq.inbox_title').' | '.__('globals.viravach'));
    }
}; ?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">
        <livewire:dashboard-elements.infobar/>

        @include('partials.flash-alerts')

        <!--begin::Card-->
        <div class="card">
            <div class="card-header border-0 pt-6">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-900">{{ __('rfq.inbox_title') }}</span>
                    <span class="text-muted mt-1 fw-semibold fs-7">{{ __('rfq.inbox_subtitle') }}</span>
                </h3>

                <div class="card-toolbar">
                    <select class="form-select form-select-solid w-200px me-3" wire:model.live="statusFilter">
                        @foreach ($this->statusOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <button type="button" class="btn btn-light-primary"
                            wire:click="toggleSort"
                            wire:loading.attr="disabled">
                        {{ $sortDirection === 'desc' ? __('rfq.sort_newest') : __('rfq.sort_oldest') }}
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="d-flex flex-column flex-xl-row p-md-7">
                    <!--begin::Sidebar-->
                    <div class="flex-column flex-xl-row-auto w-100 w-xl-400px mb-15 mb-xl-0 me-xl-15">
                        {{-- Bounded and scrolled like the support-chats
                             conversation column: about six rows before it
                             scrolls, so the request opposite stays in view
                             however long the inbox grows. --}}
                        <div class="scroll-y mh-350px pe-md-4">
                            @forelse ($this->rfqs as $rfq)
                                {{-- A row is a pointer, not a preview. The
                                     buyer's name and contact details are in
                                     the pane opposite, behind a deliberate
                                     click, rather than on permanent display. --}}
                                <div class="d-flex align-items-center mb-5 cursor-pointer" wire:key="rfq-{{ $rfq['id'] }}"
                                     wire:click="selectRfq({{ $rfq['id'] }})">
                                    <i class="ki-duotone ki-questionnaire-tablet fs-2x me-4 ms-n1 text-{{ $rfq['status_color'] }}">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>

                                    <div class="d-flex flex-column overflow-hidden flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            {{-- <bdi> keeps the id, locale code and date
                                                 internally LTR inside RTL copy without
                                                 pulling the block's own alignment along
                                                 (DESIGN.md §8). --}}
                                            <span class="fs-5 fw-semibold {{ $selectedRfqId === $rfq['id'] ? 'text-primary' : 'text-gray-900 text-hover-primary' }}"><bdi>#{{ $rfq['id'] }}</bdi></span>
                                            <span class="badge badge-light-{{ $rfq['status_color'] }} flex-shrink-0">{{ $rfq['status_label'] }}</span>
                                        </div>

                                        <span class="text-muted fw-semibold fs-7 text-truncate">
                                            <bdi>{{ $rfq['created_at'] }}</bdi>
                                            · {{ $rfq['company_name'] }}
                                            · <bdi>{{ strtoupper($rfq['locale']) }}</bdi>
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted fs-6 fw-semibold py-4">{{ __('rfq.inbox_empty') }}</div>
                            @endforelse
                        </div>

                        @if ($this->rfqs->hasPages())
                            {{-- Classic pages, outside the scroll box: an
                                 auto-loading list inside a bounded scroller
                                 gives the owner no way back to an old request
                                 except by scrolling past every newer one.

                                 Bare ->links(), like ⚡payments and
                                 ⚡my-companies, rather than
                                 ⚡company-list's 'pagination::bootstrap-5':
                                 that view renders plain hrefs, and the full
                                 page load they cause would drop the request
                                 the owner has open in the pane opposite. --}}
                            <div class="mt-4">
                                {{ $this->rfqs->links() }}
                            </div>
                        @endif
                    </div>
                    <!--end::Sidebar-->

                    <!--begin::Request-->
                    <div class="flex-lg-row-fluid">
                        @php $selected = $this->selectedRfq(); @endphp

                        @if ($selected === null)
                            <div class="text-muted fs-5 fw-semibold py-10 text-center">{{ __('rfq.select_hint') }}</div>
                        @else
                            <div class="d-flex flex-stack mb-8">
                                <div class="d-flex flex-column">
                                    <span class="fs-3 fw-bold text-gray-900">{{ $selected->buyer_name }}</span>
                                    <span class="text-muted fw-semibold fs-7">
                                        {{ $selected->status->getLabel() }}
                                        · <bdi>{{ \App\Support\LocalizedDate::format($selected->created_at, \App\Support\LocalizedDate::FORMAT_DATETIME) }}</bdi>
                                    </span>
                                </div>

                                @if ($this->canClose())
                                    <button type="button" class="btn btn-light-danger" wire:click="closeRfq">
                                        {{ __('rfq.close_action') }}
                                    </button>
                                @endif
                            </div>

                            {{-- Contact panel. Every channel here leaves the
                                 platform: the buyer is a guest with no account
                                 and no inbox, so the owner answers by email,
                                 phone or WhatsApp and marks the request closed
                                 when they are done. --}}
                            <div class="card card-flush bg-light mb-8">
                                <div class="card-body">
                                    <div class="fw-bold text-gray-900 fs-5 mb-4">{{ __('rfq.contact_heading') }}</div>

                                    <div class="d-flex flex-column gap-3">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="text-muted fw-semibold fs-7 w-100px">{{ __('rfq.buyer_email') }}</span>
                                            <a href="mailto:{{ $selected->buyer_email }}" class="fw-semibold text-hover-primary">
                                                <bdi>{{ $selected->buyer_email }}</bdi>
                                            </a>
                                        </div>

                                        @if ($selected->telNumber())
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <span class="text-muted fw-semibold fs-7 w-100px">{{ __('rfq.buyer_phone') }}</span>
                                                <a href="tel:{{ $selected->telNumber() }}" class="fw-semibold text-hover-primary">
                                                    <bdi>{{ $selected->buyer_phone }}</bdi>
                                                </a>

                                                @if ($selected->whatsappNumber())
                                                    <a href="https://wa.me/{{ $selected->whatsappNumber() }}"
                                                       target="_blank" rel="noopener"
                                                       class="btn btn-sm btn-light-success py-1 px-3">
                                                        {{ __('rfq.contact_whatsapp') }}
                                                    </a>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($selected->buyer_country)
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <span class="text-muted fw-semibold fs-7 w-100px">{{ __('rfq.buyer_country') }}</span>
                                                <span class="fw-semibold text-gray-900">{{ $selected->buyer_country }}</span>
                                            </div>
                                        @endif

                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="text-muted fw-semibold fs-7 w-100px">{{ __('rfq.reply_language') }}</span>
                                            <span class="fw-semibold text-gray-900"><bdi>{{ strtoupper($selected->locale) }}</bdi></span>
                                        </div>
                                    </div>

                                    <div class="text-muted fs-8 mt-4">{{ __('rfq.contact_hint') }}</div>
                                </div>
                            </div>

                            <div class="fw-bold text-gray-900 fs-5 mb-3">{{ __('rfq.buyer_message') }}</div>
                            <div class="bg-light-info rounded p-5 fs-6 text-gray-900"
                                 style="white-space: pre-line">{{ $selected->message }}</div>
                        @endif
                    </div>
                    <!--end::Request-->
                </div>
            </div>
        </div>
        <!--end::Card-->
    </div>
</div>
