<?php

use App\Models\Company;
use App\Models\Rfq;
use App\Notifications\NewRfqReceived;
use App\Services\Turnstile\TurnstileVerifier;
use App\Support\PlanFeature;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

new class extends Component {
    public Company $company;

    public string $buyerName = '';

    public string $buyerEmail = '';

    public string $buyerPhone = '';

    public string $buyerCountry = '';

    public string $message = '';

    /** Filled by the Turnstile widget's success callback, cleared on every failure. */
    public string $turnstileToken = '';

    /**
     * A single visitor's cap. Generous on purpose: Iranian mobile carriers and
     * office networks NAT heavily, so one address can legitimately be several
     * buyers at once — a per-minute cap would lock out real people. An hour is
     * long enough that a scripted flood hits the wall quickly, while a genuine
     * buyer contacting a handful of suppliers in one sitting never notices.
     */
    public const MAX_PER_IP_HOURLY = 10;

    /**
     * One company's inbox cap, checked independently of the IP cap so a
     * rotating-IP flood still cannot drain the owner's SMS credits or bury
     * real leads. Set above the per-IP cap so several unrelated buyers reaching
     * the same popular company in one hour stay unaffected.
     */
    public const MAX_PER_COMPANY_HOURLY = 30;

    /** Both windows are one hour; see the constants above. */
    public const RATE_LIMIT_DECAY_SECONDS = 3600;

    /**
     * Whether the company's plan currently grants the RFQ feature.
     *
     * Read live from the subscription on every render (PlanFeature caches
     * nothing), so a downgrade or a mid-cycle revocation takes the form off
     * the page immediately — same convention as the intro-video gate in
     * pages::dashboard.settings.
     */
    public function isEnabled(): bool
    {
        return PlanFeature::value($this->company, 'rfq-system') === 'true';
    }

    public function submit(): void
    {
        // The gate is re-checked here, not just at render: a payload can be
        // posted at a component that was rendered while the plan still
        // granted the feature.
        if (! $this->isEnabled()) {
            abort(404);
        }

        if ($this->isRateLimited()) {
            // Deliberately generic — naming the window or the remaining
            // attempts would tell a script exactly how to pace itself.
            $this->failWith(__('rfq.error_rate_limited'));

            return;
        }

        $this->validate(
            [
                'buyerName' => ['required', 'string', 'max:150'],
                'buyerEmail' => ['required', 'email', 'max:255'],
                'buyerPhone' => ['nullable', 'string', 'max:50'],
                'buyerCountry' => ['nullable', 'string', 'max:100'],
                'message' => ['required', 'string', 'max:5000'],
            ],
            [
                'buyerName.required' => __('rfq.buyer_name_required'),
                'buyerName.max' => __('rfq.buyer_name_too_long'),
                'buyerEmail.required' => __('rfq.buyer_email_required'),
                'buyerEmail.email' => __('rfq.buyer_email_invalid'),
                'buyerPhone.max' => __('rfq.buyer_phone_too_long'),
                'buyerCountry.max' => __('rfq.buyer_country_too_long'),
                'message.required' => __('rfq.message_required'),
                'message.max' => __('rfq.message_too_long'),
            ],
        );

        $verdict = app(TurnstileVerifier::class)->verify(
            $this->turnstileToken,
            request()->ip(),
        );

        if (! $verdict->passed) {
            $this->failWith(
                $verdict->needsFreshToken()
                    ? __('rfq.error_turnstile_expired')
                    : __('rfq.error_turnstile_failed'),
            );

            return;
        }

        // Only a submission that got this far counts against either window, so
        // a visitor who mistypes their email is not penalised for it.
        RateLimiter::hit($this->ipRateLimitKey(), self::RATE_LIMIT_DECAY_SECONDS);
        RateLimiter::hit($this->companyRateLimitKey(), self::RATE_LIMIT_DECAY_SECONDS);

        $rfq = Rfq::create([
            'company_id' => $this->company->getKey(),
            'buyer_name' => $this->buyerName,
            'buyer_email' => $this->buyerEmail,
            'buyer_phone' => $this->buyerPhone ?: null,
            'buyer_country' => $this->buyerCountry ?: null,
            'message' => $this->message,
            // The language the buyer was browsing in — the owner's only hint
            // about which language to answer in.
            //
            // This is trustworthy inside a Livewire action even though
            // /livewire/update is NOT under the locale-prefixed route group
            // and mcamara's middleware therefore never runs on it. Livewire
            // records app()->getLocale() into the component snapshot when the
            // page renders and restores it on hydrate
            // (Livewire\Features\SupportLocales), so this reads the locale the
            // form was rendered in — which is exactly the buyer's. Changing
            // language re-navigates and re-mounts, so it cannot go stale.
            'locale' => app()->getLocale(),
            'ip_address' => (string) request()->ip(),
        ]);

        // Recipient resolution lives on the model (Phase 1), so "who owns this
        // company" is answered in exactly one place.
        Notification::send($rfq->recipients(), new NewRfqReceived($rfq));

        $this->reset('buyerName', 'buyerEmail', 'buyerPhone', 'buyerCountry', 'message', 'turnstileToken');
        $this->resetValidation();

        session()->flash('flash_success', __('rfq.submitted'));

        // A solved token is single-use, so the widget must start over even on
        // the happy path (docs/RESEARCH.md §1).
        $this->dispatch('rfq-reset-turnstile');
    }

    /**
     * Both windows are consulted before either is incremented, so hitting one
     * cap does not consume budget from the other.
     */
    protected function isRateLimited(): bool
    {
        return RateLimiter::tooManyAttempts($this->ipRateLimitKey(), self::MAX_PER_IP_HOURLY)
            || RateLimiter::tooManyAttempts($this->companyRateLimitKey(), self::MAX_PER_COMPANY_HOURLY);
    }

    protected function ipRateLimitKey(): string
    {
        return 'rfq-submission:ip:'.request()->ip();
    }

    protected function companyRateLimitKey(): string
    {
        return 'rfq-submission:company:'.$this->company->getKey();
    }

    /** Every rejected path ends the same way: toast, and a fresh challenge. */
    protected function failWith(string $error): void
    {
        $this->turnstileToken = '';

        session()->flash('flash_error', $error);

        $this->dispatch('rfq-reset-turnstile');
    }
}; ?>

@if (! $this->isEnabled())
    {{-- The company's plan does not include the RFQ system. Nothing is
         rendered at all — a disabled button would advertise a feature the
         visitor cannot use and invite support questions the owner has to
         field. --}}
    <div></div>
@else
    <div>
        {{-- Flashed inside a Livewire round trip that never redirects, so this
             partial has to be here as well as in the layout. Deliberately
             without $includeErrorBag: field errors render inline below. --}}
        @include('partials.flash-alerts')

        {{-- Card shape is the public company page's sidebar pattern
             (`card` > `card-header border-0 pt-6` > `card-title` > h2 >
             `card-body pt-0`), matching the contact-info and social-links
             boxes it sits under. The subtitle moved into the body because
             that pattern's header carries a heading and nothing else. --}}
        <!--begin::RFQ form-->
        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <h2>{{ __('rfq.form_title') }}</h2>
                </div>
            </div>

            <div class="card-body pt-0">
                <div class="text-muted fs-7 mb-5">{{ __('rfq.form_subtitle') }}</div>

                <form wire:submit="submit">
                    {{-- One field per row: the only host is a 300-350px
                         sidebar column, and col-md-6 splits on the viewport
                         width rather than the column's, so paired fields
                         would collapse to ~110px each on any desktop. --}}
                    <div class="row">
                        <div class="col-12 mb-5">
                            <label class="form-label required" for="rfq-buyer-name">{{ __('rfq.buyer_name') }}</label>
                            <input id="rfq-buyer-name" type="text" class="form-control form-control-solid"
                                   wire:model="buyerName" maxlength="150">
                            @error('buyerName') <div class="fs-7 text-danger mt-2">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 mb-5">
                            <label class="form-label required" for="rfq-buyer-email">{{ __('rfq.buyer_email') }}</label>
                            <input id="rfq-buyer-email" type="email" class="form-control form-control-solid"
                                   wire:model="buyerEmail" maxlength="255" dir="ltr">
                            @error('buyerEmail') <div class="fs-7 text-danger mt-2">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 mb-5">
                            <label class="form-label" for="rfq-buyer-phone">{{ __('rfq.buyer_phone') }}</label>
                            <input id="rfq-buyer-phone" type="text" class="form-control form-control-solid"
                                   wire:model="buyerPhone" maxlength="50" dir="ltr">
                            @error('buyerPhone') <div class="fs-7 text-danger mt-2">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 mb-5">
                            <label class="form-label" for="rfq-buyer-country">{{ __('rfq.buyer_country') }}</label>
                            <input id="rfq-buyer-country" type="text" class="form-control form-control-solid"
                                   wire:model="buyerCountry" maxlength="100">
                            @error('buyerCountry') <div class="fs-7 text-danger mt-2">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 mb-5">
                            <label class="form-label required" for="rfq-message">{{ __('rfq.message') }}</label>
                            <textarea id="rfq-message" class="form-control form-control-solid" rows="5"
                                      wire:model="message" maxlength="5000"
                                      placeholder="{{ __('rfq.message_placeholder') }}"></textarea>
                            @error('message') <div class="fs-7 text-danger mt-2">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- wire:ignore is what keeps Cloudflare's iframe alive across
                         Livewire's morph; without it the widget is torn out (and
                         the solved token with it) on the first validation error.
                         The token itself is carried by $wire.set() from the
                         widget callback below — NOT by Turnstile's own hidden
                         response-field input, which lives inside this ignored
                         subtree where Livewire would never see it
                         (docs/RESEARCH.md §1). --}}
                    <div class="mb-5" wire:ignore>
                        <div id="rfq-turnstile-{{ $this->getId() }}"></div>
                    </div>

                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submit">{{ __('rfq.submit') }}</span>
                        <span wire:loading wire:target="submit">{{ __('rfq.submitting') }}</span>
                    </button>
                </form>
            </div>
        </div>
        <!--end::RFQ form-->
    </div>

    {{-- @assets is fetched once per page no matter how many instances render,
         which is exactly what api.js needs — loading it twice double-registers
         Cloudflare's render pass. The promise is defined in the same block and
         BEFORE the deferred api.js tag, so every component instance can await
         the same load regardless of which one renders first. --}}
    @assets
        <script>
            window.turnstileReady ??= new Promise((resolve) => {
                window.onloadTurnstileCallback = resolve;
            });
        </script>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=onloadTurnstileCallback" defer></script>
    @endassets

    {{-- @script, not a bare <script>: Livewire's single-file compiler only
         lifts a <script> that starts at column 0 (Compiler\Parser\
         SingleFileParser::extractScriptPortion), so this indented one stayed
         inline in the view, where $wire does not exist — every $wire call
         below threw ReferenceError. @script blocks are excluded from that
         extraction and evaluated by Livewire with $wire in scope, after the
         component initialises and again after each wire:navigate, which is
         exactly the lifecycle the comment below describes. --}}
    @script
    <script>
        // A component script runs after page load but before this component
        // renders, and runs again each time the component enters the page via
        // wire:navigate — which is why the widget is rendered from here rather
        // than from Turnstile's auto-render pass: after a navigate the
        // container is a brand-new, empty element that pass has already missed.
        const container = document.getElementById('rfq-turnstile-{{ $this->getId() }}');
        const sitekey = @js(config('services.turnstile.site_key'));

        let widgetId = null;

        if (container && sitekey) {
            window.turnstileReady?.then(() => {
                widgetId = turnstile.render(container, {
                    sitekey,
                    // The third argument keeps this from firing its own network
                    // round trip: the token rides along with the next submit.
                    callback: (token) => $wire.set('turnstileToken', token, false),
                    // A token dies after 300 seconds; clearing the property
                    // keeps the server from being handed one it will only
                    // reject as invalid-input-response.
                    'expired-callback': () => $wire.set('turnstileToken', '', false),
                    'error-callback': () => $wire.set('turnstileToken', '', false),
                });
            });
        }

        // Tokens are single-use, so every outcome — success, validation
        // failure, rate limit, Turnstile rejection — needs a fresh challenge.
        $wire.on('rfq-reset-turnstile', () => {
            if (widgetId !== null) {
                turnstile.reset(widgetId);
            }
        });
    </script>
    @endscript
@endif
