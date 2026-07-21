<?php

namespace App\Services\Payment;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\CompanySubscriptionService;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Exceptions\PurchaseFailedException;
use Shetabit\Payment\Facade\Payment;

class InvoicePaymentService
{
    public function __construct(private readonly CompanySubscriptionService $subscriptions) {}

    /**
     * Purchase the invoice at the gateway and return the URL the browser
     * should be redirected to in order to complete payment.
     */
    public function purchase(Invoice $invoice): string
    {
        try {
            return Payment::via($invoice->gateway)
                ->callbackUrl(route('payment.callback'))
                ->amount($invoice->amount)
                ->detail('invoice_id', $invoice->id)
                ->purchase(
                    finalizeCallback: function ($driver, $transactionId) use ($invoice): void {
                        $invoice->update(['transaction_id' => $transactionId]);
                    }
                )
                ->pay()
                ->getUrl();
        } catch (PurchaseFailedException $e) {
            $invoice->update([
                'status' => InvoiceStatus::Failed,
                'failure_reason' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Verify a pending invoice against the gateway's callback and, on
     * success, activate the purchased plan. Returns whether the invoice is
     * now paid.
     */
    public function verify(Invoice $invoice): bool
    {
        if ($invoice->status !== InvoiceStatus::Pending) {
            return $invoice->status === InvoiceStatus::Paid;
        }

        if (request('Status') !== 'OK') {
            $invoice->update([
                'status' => InvoiceStatus::Canceled,
                'failure_reason' => 'Canceled by the user at the gateway.',
            ]);

            return false;
        }

        try {
            $receipt = Payment::via($invoice->gateway)
                ->amount($invoice->amount)
                ->transactionId($invoice->transaction_id)
                ->verify();
        } catch (InvalidPaymentException $e) {
            $invoice->update([
                'status' => InvoiceStatus::Failed,
                'failure_reason' => $e->getMessage(),
            ]);

            return false;
        }

        $invoice->update([
            'status' => InvoiceStatus::Paid,
            'gateway_ref' => $receipt->getReferenceId(),
        ]);

        $this->activate($invoice);

        return true;
    }

    /**
     * Switch (or start) the company's subscription to the invoice's plan.
     * Paid activations always start billing immediately from now — the
     * plan's built-in trial window is only meant for the explicit,
     * one-time-per-user trial grant, not for a plan that was just paid for.
     */
    public function activate(Invoice $invoice): void
    {
        $this->subscriptions->switchToPlan($invoice->company, $invoice->plan);
    }
}
