<?php

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Invoice;
use App\Models\Plan;
use App\Services\CompanySubscriptionService;
use App\Services\Payment\InvoicePaymentService;
use App\Support\LocalizedDate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Shetabit\Multipay\Exceptions\PurchaseFailedException;

new
#[Layout('layouts::landing')]
class extends Component {
    #[Url]
    public ?int $selectedCompanyId = null;

    #[Url]
    public string $duration = 'quarterly';

    /** Maps each duration key to the plan slug suffix defined by the seeder. */
    protected array $durationSuffixes = [
        'quarterly' => '-3-months',
        'semiannual' => '-6-months',
        'yearly' => '-1-year',
    ];

    public function mount(?Company $company = null): void
    {
        if ($company) {
            abort_unless($company->user_id === auth()->id(), 403);
            $this->selectedCompanyId = $company->id;
        }

        if (! $this->selectedCompanyId) {
            $this->selectedCompanyId = $this->myCompanies()->first()?->id;
        }

        if (! array_key_exists($this->duration, $this->durationSuffixes)) {
            $this->duration = 'quarterly';
        }
    }

    public function setDuration(string $duration): void
    {
        if (array_key_exists($duration, $this->durationSuffixes)) {
            $this->duration = $duration;
            unset($this->visiblePlans, $this->featureRows);
        }
    }

    #[Computed]
    public function myCompanies(): Collection
    {
        return Company::where('user_id', auth()->id())->orderBy('id')->get();
    }

    public function selectedCompany(): ?Company
    {
        return $this->myCompanies()->firstWhere('id', $this->selectedCompanyId);
    }

    #[Computed]
    public function allPlans(): Collection
    {
        return Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with('features')
            ->get();
    }

    /**
     * Free plan + the paid plans matching the selected duration.
     */
    #[Computed]
    public function visiblePlans(): Collection
    {
        $suffix = $this->durationSuffixes[$this->duration];

        return $this->allPlans()
            ->filter(fn (Plan $plan) => $plan->isFree() || str_ends_with($plan->slug, $suffix))
            ->values();
    }

    /**
     * Union of base feature keys across visible plans, keeping seeder order.
     * Feature slugs are prefixed with the plan slug, so the prefix is stripped
     * to obtain a comparable base key.
     *
     * @return BaseCollection<int, array{key: string, name: string}>
     */
    #[Computed]
    public function featureRows(): BaseCollection
    {
        $rows = collect();

        foreach ($this->visiblePlans() as $plan) {
            foreach ($plan->features->sortBy('sort_order') as $feature) {
                $key = $this->baseFeatureKey($plan, $feature->slug);
                if (! $rows->has($key)) {
                    $rows->put($key, ['key' => $key, 'name' => $feature->name]);
                }
            }
        }

        return $rows->values();
    }

    protected function baseFeatureKey(Plan $plan, string $featureSlug): string
    {
        return str_starts_with($featureSlug, $plan->slug.'-')
            ? substr($featureSlug, strlen($plan->slug) + 1)
            : $featureSlug;
    }

    /**
     * Returns the raw feature value for a plan, or null when absent.
     */
    public function featureValue(Plan $plan, string $baseKey): ?string
    {
        $feature = $plan->features->first(
            fn ($f) => $this->baseFeatureKey($plan, $f->slug) === $baseKey
        );

        return $feature?->value;
    }

    public function subscribeToPlan(int $planId): mixed
    {
        $company = $this->selectedCompany();
        abort_unless($company, 404);

        $plan = Plan::findOrFail($planId);

        // The free plan needs no payment — switch instantly.
        if ($plan->isFree()) {
            app(CompanySubscriptionService::class)->switchToPlan($company, $plan);

            unset($this->myCompanies);
            session()->flash('subscription-status', __('subscriptions.switch_success'));

            return null;
        }

        $amount = (int) round($plan->price);

        if ($amount < config('pricing.min_purchase_amount')) {
            session()->flash('flash_error', __('subscriptions.amount_below_minimum'));

            return null;
        }

        $invoice = Invoice::create([
            'user_id' => auth()->id(),
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'amount' => $amount,
            'status' => InvoiceStatus::Pending,
            'gateway' => 'zarinpal',
        ]);

        session(['pending_invoice_id' => $invoice->id]);

        try {
            return redirect(app(InvoicePaymentService::class)->purchase($invoice));
        } catch (PurchaseFailedException) {
            session()->flash('flash_error', __('subscriptions.purchase_failed'));

            return null;
        }
    }

    /**
     * Grants the one-time, 14-day Pro Plus trial to the currently selected
     * company (the same company the rest of this page's controls act on).
     */
    public function startTrial(): void
    {
        $company = $this->selectedCompany();
        abort_unless($company, 404);

        app(CompanySubscriptionService::class)->startProPlusTrial($company);

        unset($this->myCompanies);
        session()->flash('subscription-status', __('subscriptions.trial_started_successfully'));
    }

    /**
     * The user's currently-active trial subscription (if any), across all
     * of their companies — a user may have used their one-time trial on a
     * company other than the one currently selected in the switcher.
     */
    #[Computed]
    public function activeTrialSubscription(): ?CompanySubscription
    {
        if (! auth()->user()->hasUsedTrial()) {
            return null;
        }

        return CompanySubscription::query()
            ->where('subscriber_type', Company::class)
            ->whereIn('subscriber_id', $this->myCompanies()->pluck('id'))
            ->where('is_trial', true)
            ->first();
    }

    public function render()
    {
        return $this->view()->title(
            __('subscriptions.page_title').' | '.__('auth.user-dashboard').' | '.__('globals.viravach')
        );
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">
        <livewire:dashboard-elements.infobar/>

        @include('partials.flash-alerts')

        @if ($this->myCompanies()->isEmpty())
            <div class="card">
                <div class="card-body text-center py-15">
                    <p class="fs-4 text-gray-700 mb-5">{{ __('subscriptions.no_companies_notice') }}</p>
                    <a href="{{ route('create.company') }}" class="btn btn-primary">
                        {{ __('subscriptions.create_company_cta') }}
                    </a>
                </div>
            </div>
        @else
            {{-- Company selector + current plan summary --}}
            <div class="card mb-5">
                <div class="card-body d-flex flex-wrap align-items-center gap-5">
                    <div class="w-100 mw-300px">
                        <label class="form-label mb-1">{{ __('subscriptions.select_company_label') }}</label>
                        <select wire:model.live="selectedCompanyId" class="form-select form-select-solid">
                            @foreach ($this->myCompanies() as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($company = $this->selectedCompany())
                        <div>
                            <div class="text-muted fs-7">{{ __('subscriptions.current_plan_label') }}</div>
                            @if ($subscription = $company->activeSubscription())
                                <div class="fw-bold fs-5">{{ $subscription->plan->name }}</div>
                                <div class="text-muted fs-7">
                                    {{ $subscription->ends_at ? __('subscriptions.expires_at', ['date' => LocalizedDate::format($subscription->ends_at)]) : __('subscriptions.never_expires') }}
                                </div>
                            @else
                                <div class="fw-bold fs-5">{{ __('subscriptions.no_active_plan') }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Trial box --}}
            <div class="card mb-5">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-5">
                    @if ($trial = $this->activeTrialSubscription())
                        <div>
                            <span class="badge badge-light-warning fw-bold mb-2">{{ __('subscriptions.trial_badge') }}</span>
                            <div class="fw-bold fs-5">{{ $trial->subscriber->name }}</div>
                            <div class="text-muted fs-7">
                                {{ __('subscriptions.trial_ends_at', ['date' => LocalizedDate::format($trial->ends_at)]) }}
                            </div>
                        </div>
                    @elseif (auth()->user()->hasUsedTrial())
                        <div class="text-muted fs-6">{{ __('subscriptions.trial_used_notice') }}</div>
                    @else
                        @php
                            $selectedCompanyPlan = $this->selectedCompany()?->activeSubscription()?->plan;
                            $canStartTrial = $selectedCompanyPlan === null || $selectedCompanyPlan->isFree();
                        @endphp
                        <div class="text-muted fs-6">{{ __('subscriptions.trial_availability_notice') }}</div>
                        <button type="button"
                                class="btn btn-light-warning"
                                wire:click="startTrial"
                                wire:loading.attr="disabled"
                                @disabled(! $canStartTrial)>
                            {{ __('subscriptions.start_trial_button') }}
                        </button>
                    @endif
                </div>
            </div>

            {{-- Pricing --}}
            <div class="card" id="kt_pricing">
                <div class="card-body p-lg-17">
                    <div class="d-flex flex-column">
                        {{-- Heading --}}
                        <div class="mb-13 text-center">
                            <h1 class="fs-2hx fw-bold mb-5">{{ __('subscriptions.choose_plan_title') }}</h1>
                            <div class="text-gray-600 fw-semibold fs-5">
                                {{ __('subscriptions.choose_plan_subtitle') }}
                            </div>
                        </div>

                        {{-- Duration selector --}}
                        <div class="nav-group nav-group-outline mx-auto mb-15">
                            @foreach (['quarterly' => __('subscriptions.duration_quarterly'), 'semiannual' => __('subscriptions.duration_semiannual'), 'yearly' => __('subscriptions.duration_yearly')] as $key => $label)
                                <button type="button"
                                        wire:click="setDuration('{{ $key }}')"
                                        class="btn btn-color-gray-600 btn-active btn-active-secondary px-6 py-3 {{ $duration === $key ? 'active' : '' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        {{-- Plan cards --}}
                        <div class="row g-10">
                            @foreach ($this->visiblePlans() as $plan)
                                @php
                                    $activeSubscription = $this->selectedCompany()?->activeSubscription();
                                    $isCurrent = $activeSubscription?->plan_id === $plan->id;
                                @endphp
                                <div class="col-xl-4">
                                    <div class="d-flex h-100 align-items-center">
                                        <div class="w-100 d-flex flex-column flex-center rounded-3 bg-light bg-opacity-75 py-15 px-10">
                                            {{-- Heading --}}
                                            <div class="mb-7 text-center">
                                                <h2 class="text-gray-900 mb-5 fw-bolder">{{ $plan->name }}</h2>
                                                <div class="text-gray-600 fw-semibold mb-5">
                                                    {{ $plan->description }}
                                                </div>
                                                <div class="text-center">
                                                    @if ($plan->isFree())
                                                        <span class="fs-3x fw-bold text-success">{{ __('subscriptions.free_label') }}</span>
                                                        <span class="fs-7 fw-semibold opacity-50">/ {{ __('subscriptions.forever_label') }}</span>
                                                    @else
                                                        <span class="fs-3x fw-bold text-primary">{{ number_format((float) $plan->price) }}</span>
                                                        <span class="mb-2 text-primary">{{ __('subscriptions.currency_toman') }}</span>
                                                        <span class="fs-7 fw-semibold opacity-50">/ {{ __('subscriptions.duration_'.$duration) }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Feature rows --}}
                                            <div class="w-100 mb-10">
                                                @foreach ($this->featureRows() as $row)
                                                    @php $value = $this->featureValue($plan, $row['key']); @endphp
                                                    <div class="d-flex align-items-center mb-5">
                                                        <span class="fw-semibold fs-6 flex-grow-1 pe-3 {{ $value !== null ? 'text-gray-800' : 'text-gray-600' }}">
                                                            {{ $row['name'] }}
                                                            @if ($value !== null && $value !== 'true')
                                                                <span class="text-muted fs-7">
                                                                    ({{ $value === 'unlimited' ? __('subscriptions.unlimited_label') : $value }})
                                                                </span>
                                                            @endif
                                                        </span>
                                                        @if ($value !== null)
                                                            <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        @else
                                                            <i class="ki-duotone ki-cross-circle fs-1">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>

                                            {{-- CTA: free plan is auto-assigned and has no button --}}
                                            @if (! $plan->isFree())
                                                @if ($isCurrent)
                                                    <span class="btn btn-sm btn-light-success disabled">
                                                        {{ __('subscriptions.current_plan_badge') }}
                                                    </span>
                                                @else
                                                    <button type="button"
                                                            class="btn btn-sm btn-primary"
                                                            wire:click="subscribeToPlan({{ $plan->id }})"
                                                            wire:loading.attr="disabled">
                                                        {{ __('subscriptions.select_plan_button') }}
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
