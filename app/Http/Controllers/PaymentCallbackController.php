<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\Payment\InvoicePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    public function __invoke(Request $request, InvoicePaymentService $paymentService): RedirectResponse
    {
        $invoiceId = $request->session()->pull('pending_invoice_id');
        $invoice = $invoiceId ? Invoice::find($invoiceId) : null;

        if (! $invoice || $invoice->user_id !== $request->user()->id) {
            return redirect()->route('subscriptions')->with(
                'subscription-status',
                __('payments.callback_invoice_not_found'),
            );
        }

        $paid = $paymentService->verify($invoice);

        return redirect()->route('subscriptions')->with(
            'subscription-status',
            $paid ? __('payments.callback_success') : __('payments.callback_failed'),
        );
    }
}
