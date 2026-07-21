<?php

use App\Models\Invoice;
use App\Support\LocalizedDate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts::landing')]
class extends Component
{
    use WithPagination;

    #[Computed]
    public function invoices(): LengthAwarePaginator
    {
        return Invoice::where('user_id', auth()->id())
            ->with(['plan', 'company'])
            ->latest()
            ->paginate(10);
    }

    public function render()
    {
        return $this->view()->title(__('payments.page_title').' | '.__('auth.user-dashboard').' | '.__('globals.viravach'));
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">
        <livewire:dashboard-elements.infobar/>

        <div class="card card-flush">
            <div class="card-header pt-5">
                <div class="card-title">
                    <h2>{{ __('payments.page_title') }}</h2>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th>{{ __('payments.table_plan') }}</th>
                            <th>{{ __('payments.table_company') }}</th>
                            <th>{{ __('payments.table_amount') }}</th>
                            <th>{{ __('payments.table_status') }}</th>
                            <th>{{ __('payments.table_reference') }}</th>
                            <th>{{ __('payments.table_date') }}</th>
                        </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600">
                        @forelse ($this->invoices as $invoice)
                            <tr wire:key="invoice-{{ $invoice->id }}">
                                <td>{{ $invoice->plan?->name }}</td>
                                <td>{{ $invoice->company?->name }}</td>
                                <td>{{ __('payments.amount_toman_suffix', ['amount' => number_format($invoice->amount)]) }}</td>
                                <td>
                                    <span class="badge badge-light-{{ $invoice->status->getColor() }}">
                                        {{ __('payments.status_'.$invoice->status->value) }}
                                    </span>
                                </td>
                                <td>{{ $invoice->gateway_ref ?? '—' }}</td>
                                <td>{{ LocalizedDate::format($invoice->created_at, LocalizedDate::FORMAT_DATETIME) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-10">
                                    {{ __('payments.no_invoices_found') }}
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-5">
                    {{ $this->invoices->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
