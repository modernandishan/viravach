<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\User;
use App\Services\Payment\InvoicePaymentService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Receipt;
use Shetabit\Payment\Facade\Payment;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeInvoice(): Invoice
    {
        $this->seed(PlanSeeder::class);

        $user = User::factory()->create();
        $company = Company::factory()->for($user)->create();
        $plan = Plan::where('slug', 'pro-3-months')->firstOrFail();

        return Invoice::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'amount' => 900_000,
            'status' => InvoiceStatus::Pending,
            'transaction_id' => 'AUTH-123',
        ]);
    }

    public function test_purchase_stores_the_gateways_transaction_id_and_returns_a_redirect_url(): void
    {
        $invoice = $this->makeInvoice();
        $invoice->update(['transaction_id' => null]);

        Payment::shouldReceive('via')->once()->with('zarinpal')->andReturnSelf();
        Payment::shouldReceive('callbackUrl')->once()->andReturnSelf();
        Payment::shouldReceive('amount')->once()->with(900_000)->andReturnSelf();
        Payment::shouldReceive('detail')->once()->with('invoice_id', $invoice->id)->andReturnSelf();
        Payment::shouldReceive('purchase')->once()->andReturnUsing(function ($invoiceArg, $finalizeCallback) {
            $finalizeCallback(null, 'AUTH-456');

            return Payment::getFacadeRoot();
        });
        Payment::shouldReceive('pay')->once()->andReturn(new class
        {
            public function getUrl(): string
            {
                return 'https://sandbox.zarinpal.com/pg/StartPay/AUTH-456';
            }
        });

        $url = app(InvoicePaymentService::class)->purchase($invoice);

        $this->assertSame('https://sandbox.zarinpal.com/pg/StartPay/AUTH-456', $url);
        $this->assertSame('AUTH-456', $invoice->fresh()->transaction_id);
    }

    public function test_verify_marks_the_invoice_paid_and_activates_the_plan_on_success(): void
    {
        $invoice = $this->makeInvoice();

        $this->withoutMiddleware();
        request()->merge(['Status' => 'OK']);

        Payment::shouldReceive('via')->once()->with('zarinpal')->andReturnSelf();
        Payment::shouldReceive('amount')->once()->with(900_000)->andReturnSelf();
        Payment::shouldReceive('transactionId')->once()->with('AUTH-123')->andReturnSelf();
        Payment::shouldReceive('verify')->once()->andReturn(new Receipt('zarinpal', 'REF-789'));

        $paid = app(InvoicePaymentService::class)->verify($invoice);

        $this->assertTrue($paid);

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertSame('REF-789', $invoice->gateway_ref);

        $this->assertTrue($invoice->company->subscribedTo($invoice->plan_id));
        $subscription = $invoice->company->activeSubscription();
        $this->assertNull($subscription->trial_ends_at);
    }

    public function test_verify_marks_the_invoice_canceled_when_the_gateway_reports_nok_without_calling_verify(): void
    {
        $invoice = $this->makeInvoice();

        request()->merge(['Status' => 'NOK']);

        Payment::shouldReceive('verify')->never();

        $paid = app(InvoicePaymentService::class)->verify($invoice);

        $this->assertFalse($paid);
        $this->assertSame(InvoiceStatus::Canceled, $invoice->fresh()->status);
        $this->assertFalse($invoice->company->fresh()->subscribedTo($invoice->plan_id));
    }

    public function test_verify_marks_the_invoice_failed_when_the_gateway_rejects_it(): void
    {
        $invoice = $this->makeInvoice();

        request()->merge(['Status' => 'OK']);

        Payment::shouldReceive('via')->once()->andReturnSelf();
        Payment::shouldReceive('amount')->once()->andReturnSelf();
        Payment::shouldReceive('transactionId')->once()->andReturnSelf();
        Payment::shouldReceive('verify')->once()->andThrow(new InvalidPaymentException('bad transaction'));

        $paid = app(InvoicePaymentService::class)->verify($invoice);

        $this->assertFalse($paid);
        $this->assertSame(InvoiceStatus::Failed, $invoice->fresh()->status);
    }

    public function test_the_callback_route_verifies_the_session_bound_invoice_and_redirects_with_a_message(): void
    {
        $invoice = $this->makeInvoice();

        Payment::shouldReceive('via')->once()->andReturnSelf();
        Payment::shouldReceive('amount')->once()->andReturnSelf();
        Payment::shouldReceive('transactionId')->once()->andReturnSelf();
        Payment::shouldReceive('verify')->once()->andReturn(new Receipt('zarinpal', 'REF-CB'));

        $response = $this->actingAs($invoice->user)
            ->withSession(['pending_invoice_id' => $invoice->id])
            ->get(route('payment.callback', ['Authority' => 'AUTH-123', 'Status' => 'OK']));

        $response->assertRedirect(route('subscriptions'));
        $response->assertSessionHas('subscription-status', __('payments.callback_success'));

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
    }
}
