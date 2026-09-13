<?php

namespace Tests\Feature\Rfq;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Rfq;
use App\Notifications\NewRfqReceived;
use App\Services\CompanySubscriptionService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class RfqFormTest extends TestCase
{
    use RefreshDatabase;

    /** The IP every request carries in the test HTTP kernel. */
    private const TEST_IP = '127.0.0.1';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);

        config([
            'services.turnstile.site_key' => 'test-site-key',
            'services.turnstile.secret_key' => 'test-secret-key',
        ]);
    }

    /**
     * A company on a plan that grants 'rfq-system' (Pro and above do; Free
     * does not).
     */
    private function companyWithRfq(): Company
    {
        $company = Company::factory()->create();

        app(CompanySubscriptionService::class)->switchToPlan(
            $company,
            Plan::where('slug', 'pro-3-months')->firstOrFail(),
        );

        return $company->fresh();
    }

    private function companyWithoutRfq(): Company
    {
        $company = Company::factory()->create();

        app(CompanySubscriptionService::class)->switchToPlan(
            $company,
            Plan::where('slug', 'free')->firstOrFail(),
        );

        return $company->fresh();
    }

    /** Cloudflare's verdict for one submission. */
    private function fakeTurnstile(bool $passes, array $errorCodes = []): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success' => $passes,
                'error-codes' => $errorCodes,
            ]),
        ]);
    }

    private function fillForm(Testable $component): Testable
    {
        return $component
            ->set('buyerName', 'Ada Lovelace')
            ->set('buyerEmail', 'ada@example.com')
            ->set('buyerPhone', '+441234567890')
            ->set('buyerCountry', 'United Kingdom')
            ->set('message', 'Please quote 500 units with delivery to London.')
            ->set('turnstileToken', 'a-solved-token');
    }

    public function test_it_renders_nothing_when_the_plan_does_not_grant_the_feature(): void
    {
        $component = Livewire::test('rfq-form', ['company' => $this->companyWithoutRfq()]);

        // Not a disabled button — no form at all.
        $component->assertDontSee(__('rfq.form_title'))
            ->assertDontSee('rfq-turnstile');
    }

    public function test_it_renders_the_form_when_the_plan_grants_the_feature(): void
    {
        Livewire::test('rfq-form', ['company' => $this->companyWithRfq()])
            ->assertSee(__('rfq.form_title'))
            ->assertSee(__('rfq.submit'));
    }

    public function test_a_gated_company_cannot_be_submitted_to_even_with_a_crafted_payload(): void
    {
        $company = $this->companyWithoutRfq();
        $this->fakeTurnstile(true);

        $this->fillForm(Livewire::test('rfq-form', ['company' => $company]))
            ->call('submit')
            ->assertStatus(404);

        $this->assertSame(0, Rfq::count());
    }

    public function test_a_valid_submission_stores_the_request_and_notifies_the_owner(): void
    {
        Notification::fake();
        $this->fakeTurnstile(true);

        $company = $this->companyWithRfq();

        // Set BEFORE the component mounts, because that is the only order a
        // browser can produce: the visitor is already on /ru/companies/... when
        // the form renders. Livewire records the locale of that render in the
        // component snapshot and restores it on every later action
        // (Livewire\Features\SupportLocales), so setting it afterwards here
        // would be overwritten by the restore and would test nothing real.
        $this->app->setLocale('ru');

        $this->fillForm(Livewire::test('rfq-form', ['company' => $company]))
            ->call('submit')
            ->assertHasNoErrors()
            // A solved token is single-use, so the widget restarts even on success.
            ->assertDispatched('rfq-reset-turnstile')
            ->assertSet('buyerName', '')
            ->assertSet('turnstileToken', '');

        $rfq = Rfq::sole();

        $this->assertSame($company->id, $rfq->company_id);
        $this->assertSame('Ada Lovelace', $rfq->buyer_name);
        $this->assertSame('ada@example.com', $rfq->buyer_email);
        // Captured at submission time — the owner's reply-language hint.
        $this->assertSame('ru', $rfq->locale);
        $this->assertSame(self::TEST_IP, $rfq->ip_address);

        Notification::assertSentTo($company->user, NewRfqReceived::class);
    }

    public function test_it_sends_the_token_and_visitor_ip_to_cloudflare(): void
    {
        Notification::fake();
        $this->fakeTurnstile(true);

        $this->fillForm(Livewire::test('rfq-form', ['company' => $this->companyWithRfq()]))
            ->call('submit');

        Http::assertSent(fn ($request): bool => $request->url() === config('services.turnstile.verify_url')
            && $request['secret'] === 'test-secret-key'
            && $request['response'] === 'a-solved-token'
            && $request['remoteip'] === self::TEST_IP);
    }

    public function test_it_rejects_a_submission_cloudflare_declines(): void
    {
        Notification::fake();
        $this->fakeTurnstile(false, ['invalid-input-response']);

        $this->fillForm(Livewire::test('rfq-form', ['company' => $this->companyWithRfq()]))
            ->call('submit')
            ->assertDispatched('rfq-reset-turnstile')
            ->assertSet('turnstileToken', '');

        $this->assertSame(0, Rfq::count());
        Notification::assertNothingSent();
    }

    public function test_it_validates_the_buyer_fields(): void
    {
        $this->fakeTurnstile(true);

        Livewire::test('rfq-form', ['company' => $this->companyWithRfq()])
            ->set('buyerName', '')
            ->set('buyerEmail', 'not-an-email')
            ->set('message', '')
            ->call('submit')
            ->assertHasErrors(['buyerName', 'buyerEmail', 'message']);

        $this->assertSame(0, Rfq::count());
    }

    public function test_it_stops_a_visitor_who_passed_the_per_ip_cap(): void
    {
        Notification::fake();
        $this->fakeTurnstile(true);

        $company = $this->companyWithRfq();

        // Mirrors the component's own key so a change to either side fails loudly.
        $ipKey = 'rfq-submission:ip:'.self::TEST_IP;

        for ($attempt = 0; $attempt < 10; $attempt++) {
            RateLimiter::hit($ipKey, 3600);
        }

        $this->fillForm(Livewire::test('rfq-form', ['company' => $company]))
            ->call('submit')
            ->assertDispatched('rfq-reset-turnstile');

        $this->assertSame(0, Rfq::count());
        Notification::assertNothingSent();
    }

    public function test_it_stops_a_flood_of_one_company_arriving_from_rotating_ips(): void
    {
        Notification::fake();
        $this->fakeTurnstile(true);

        $company = $this->companyWithRfq();

        // The per-IP window is untouched here: only the company's own cap is
        // full, which is exactly the rotating-address case.
        $companyKey = 'rfq-submission:company:'.$company->id;

        for ($attempt = 0; $attempt < 30; $attempt++) {
            RateLimiter::hit($companyKey, 3600);
        }

        $this->fillForm(Livewire::test('rfq-form', ['company' => $company]))
            ->call('submit')
            ->assertDispatched('rfq-reset-turnstile');

        $this->assertSame(0, Rfq::count());
        Notification::assertNothingSent();
    }

    public function test_a_rejected_submission_does_not_consume_rate_limit_budget(): void
    {
        Notification::fake();
        $this->fakeTurnstile(false, ['invalid-input-response']);

        $company = $this->companyWithRfq();

        $this->fillForm(Livewire::test('rfq-form', ['company' => $company]))
            ->call('submit');

        $this->assertSame(0, RateLimiter::attempts('rfq-submission:ip:'.self::TEST_IP));
        $this->assertSame(0, RateLimiter::attempts('rfq-submission:company:'.$company->id));
    }

    public function test_a_successful_submission_consumes_both_windows(): void
    {
        Notification::fake();
        $this->fakeTurnstile(true);

        $company = $this->companyWithRfq();

        $this->fillForm(Livewire::test('rfq-form', ['company' => $company]))
            ->call('submit');

        $this->assertSame(1, RateLimiter::attempts('rfq-submission:ip:'.self::TEST_IP));
        $this->assertSame(1, RateLimiter::attempts('rfq-submission:company:'.$company->id));
    }
}
