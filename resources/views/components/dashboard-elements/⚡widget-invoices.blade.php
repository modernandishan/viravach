<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Support\LocalizedDate;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Widget 5 — the user's five most recent invoices, with lifetime spend in
 * the header (the stat_total_spent metric, folded in here rather than given
 * a tile of its own: the infobar already carries the header stats).
 *
 * Cost: two indexed queries on invoices.user_id — one for the latest five,
 * one SUM for the paid total. Not cached: both are single indexed lookups,
 * and the only write path is the payment callback, which already clears the
 * other widgets. Caching two cheap queries to save nothing would only add a
 * surface that can go stale.
 *
 * Summarises ⚡payments; links there rather than repeating its table.
 */
new class extends Component
{
    private const LIMIT = 5;

    /**
     * @return Collection<int, Invoice>
     */
    #[Computed]
    public function invoices(): Collection
    {
        return Invoice::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(self::LIMIT)
            ->get();
    }

    /**
     * Lifetime spend: paid invoices only — pending and failed ones were
     * never money that left the account.
     */
    #[Computed]
    public function totalSpent(): int
    {
        return (int) Invoice::query()
            ->where('user_id', auth()->id())
            ->where('status', InvoiceStatus::Paid)
            ->sum('amount');
    }
};
?>

<div class="card card-flush">
    <div class="card-header align-items-center py-5">
        <div class="card-title d-flex flex-column">
            <h2 class="fs-4 mb-1">{{ __('dashboard.latest_invoices_title') }}</h2>
            <span class="fs-7 text-muted">
                {{ __('dashboard.stat_total_spent') }}:
                {{ __('payments.amount_toman_suffix', ['amount' => number_format($this->totalSpent)]) }}
            </span>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('payments') }}" class="btn btn-sm btn-light">
                {{ __('dashboard.view_all_payments') }}
            </a>
        </div>
    </div>
    <div class="card-body pt-0">
        @forelse ($this->invoices as $invoice)
            <div class="d-flex flex-stack py-3 {{ ! $loop->last ? 'border-bottom border-gray-300 border-dashed' : '' }}">
                <div class="d-flex flex-column overflow-hidden me-3">
                    <span class="fw-semibold text-gray-800">
                        {{ __('payments.amount_toman_suffix', ['amount' => number_format($invoice->amount)]) }}
                    </span>
                    <span class="fs-7 text-muted">
                        {{ LocalizedDate::format($invoice->created_at, LocalizedDate::FORMAT_DATETIME) }}
                    </span>
                </div>
                <span class="badge badge-light-{{ $invoice->status->getColor() }} flex-shrink-0">
                    {{ __('payments.status_'.$invoice->status->value) }}
                </span>
            </div>
        @empty
            <div class="vv-dash-widget-empty">
                <i class="ki-duotone ki-bill vv-dash-widget-empty-icon">
                    <span class="path1"></span><span class="path2"></span>
                    <span class="path3"></span><span class="path4"></span>
                    <span class="path5"></span><span class="path6"></span>
                </i>
                <p class="vv-dash-widget-empty-text">{{ __('dashboard.empty_no_invoices') }}</p>
            </div>
        @endforelse
    </div>
</div>
