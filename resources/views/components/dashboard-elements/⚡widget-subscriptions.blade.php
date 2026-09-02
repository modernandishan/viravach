<?php

use App\Models\Company;
use App\Support\DashboardWidgetCache;
use App\Support\LocalizedDate;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Widget 4 — one row per company, showing that company's plan and expiry.
 *
 * Plans are attached to a Company, not to a User, so a user with several
 * companies has several plans, possibly different ones. Every row is
 * labelled with its company name for exactly that reason; the box never
 * claims "your plan" in the singular.
 *
 * Cost: one query, with `planSubscriptions.plan` eager loaded.
 * activeSubscription() reads the already-loaded $planSubscriptions
 * collection (see HasPlanSubscriptions), so eager loading turns what would
 * be two queries per company into two queries total.
 *
 * Cached until a chokepoint clears it — plan changes are discrete events.
 */
new class extends Component
{
    /**
     * @return array<int, array{
     *     company: string,
     *     plan: ?string,
     *     expires_at: ?string,
     *     days_remaining: ?int,
     *     never_expires: bool,
     * }>
     */
    #[Computed]
    public function rows(): array
    {
        $userId = (int) auth()->id();

        return DashboardWidgetCache::remember(
            DashboardWidgetCache::SUBSCRIPTIONS,
            $userId,
            fn (): array => Company::query()
                ->where('user_id', $userId)
                ->with('planSubscriptions.plan')
                ->orderBy('id')
                ->get()
                ->map(function (Company $company): array {
                    $subscription = $company->activeSubscription();

                    return [
                        'company' => (string) $company->name,
                        'plan' => $subscription?->plan?->name ? (string) $subscription->plan->name : null,
                        'expires_at' => $subscription?->ends_at
                            ? LocalizedDate::format($subscription->ends_at)
                            : null,
                        'days_remaining' => $subscription?->ends_at
                            ? max(0, (int) now()->diffInDays($subscription->ends_at, false))
                            : null,
                        'never_expires' => $subscription !== null && $subscription->ends_at === null,
                    ];
                })
                ->values()
                ->all(),
        );
    }

    public function hasAnyPlan(): bool
    {
        return collect($this->rows)->contains(fn (array $row): bool => $row['plan'] !== null);
    }
};
?>

<div class="card card-flush">
    <div class="card-header align-items-center py-5">
        <div class="card-title">
            <h2 class="fs-4">{{ __('dashboard.subscription_status_title') }}</h2>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('subscriptions') }}" class="btn btn-sm btn-light">
                {{ __('dashboard.view_plans_cta') }}
            </a>
        </div>
    </div>
    <div class="card-body pt-0">
        @if ($this->hasAnyPlan())
            @foreach ($this->rows as $row)
                <div class="d-flex flex-stack py-3 {{ ! $loop->last ? 'border-bottom border-gray-300 border-dashed' : '' }}">
                    <div class="d-flex flex-column overflow-hidden me-3">
                        <span class="fw-semibold text-gray-800 text-truncate">{{ $row['company'] }}</span>
                        <span class="fs-7 text-muted">
                            @if ($row['plan'] === null)
                                {{ __('dashboard.stats.no_active_plan') }}
                            @elseif ($row['never_expires'])
                                {{ __('subscriptions.never_expires') }}
                            @else
                                {{ __('dashboard.subscription_expires_at', ['date' => $row['expires_at']]) }}
                            @endif
                        </span>
                    </div>
                    <span class="flex-shrink-0">
                        @if ($row['plan'] === null)
                            <a href="{{ route('subscriptions') }}" class="btn btn-sm btn-light-primary">
                                {{ __('dashboard.view_plans_cta') }}
                            </a>
                        @else
                            <span class="badge badge-light-primary">{{ $row['plan'] }}</span>
                        @endif
                    </span>
                </div>
            @endforeach
        @else
            <div class="vv-dash-widget-empty">
                <i class="ki-duotone ki-crown vv-dash-widget-empty-icon">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <p class="vv-dash-widget-empty-text">{{ __('dashboard.stats.no_active_plan') }}</p>
                <a href="{{ route('subscriptions') }}" class="btn btn-sm btn-primary">
                    {{ __('dashboard.view_plans_cta') }}
                </a>
            </div>
        @endif
    </div>
</div>
