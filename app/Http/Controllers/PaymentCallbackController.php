<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\Payment\InvoicePaymentService;
use App\Support\DashboardWidgetCache;
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

        // Returning from the gateway is the moment the owner looks at their
        // dashboard again, so it must not be stale. A successful payment that
        // activates a plan already clears these via
        // CompanySubscriptionService::switchToPlan(); this covers the paths
        // that change invoice state without going through it.
        DashboardWidgetCache::forgetForUser($invoice->user_id);

        return redirect()->route('subscriptions')->with(
            'subscription-status',
            $paid ? __('payments.callback_success') : __('payments.callback_failed'),
        );
    }
}
