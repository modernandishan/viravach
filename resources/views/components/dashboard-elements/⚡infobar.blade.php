<?php

use App\Livewire\Concerns\AggregatesCompanyViews;
use App\Models\Company;
use App\Models\CompanyPublication;
use App\Support\LocalizedDate;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    use AggregatesCompanyViews;

    /** Window the views trend compares against the one before it. */
    private const TREND_WINDOW_DAYS = 30;

    /**
     * Short TTL rather than rememberForever. This renders on every single
     * dashboard page, so it must not re-run ~6 queries per request — but a
     * stat that lags behind a publication or a plan change is worse than a
     * stat that costs a query, and there are no cache-busting hooks in this
     * codebase. A 10-minute TTL self-heals without wiring Cache::forget()
     * into CompanyPublicationService::publish() and
     * CompanySubscriptionService (see the report for that alternative).
     */
    private const CACHE_TTL_MINUTES = 10;

    /**
     * The three header metrics. Locale is part of the key because the plan
     * name and the formatted expiry date are localized, matching the
     * per-locale caching convention used by ⚡footer and ⚡world-globe.
     *
     * @return array{
     *     company_count: int,
     *     published_count: int,
     *     has_publications: bool,
     *     total_views: int,
     *     views_trend_percent: ?int,
     *     plan_name: ?string,
     *     plan_days_remaining: ?int,
     *     plan_expires_at: ?string,
     *     plan_never_expires: bool,
     * }
     */
    #[Computed]
    public function stats(): array
    {
        $userId = (int) auth()->id();
        $locale = app()->getLocale();

        return Cache::remember(
            "dashboard.infobar.stats.v1.{$locale}.{$userId}",
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn (): array => $this->computeStats($userId),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function computeStats(int $userId): array
    {
        $companyIds = Company::query()
            ->where('user_id', $userId)
            ->orderBy('id')
            ->pluck('id');

        // "Published" means an approved snapshot whose published_at has
        // actually arrived — scopeActive(), the same rule the public site
        // uses, so this count can never claim more than a visitor can see.
        $publishedIds = $companyIds->isEmpty()
            ? collect()
            : CompanyPublication::query()
                ->active()
                ->whereIn('company_id', $companyIds)
                ->pluck('id');

        return [
            'company_count' => $companyIds->count(),
            'published_count' => $publishedIds->count(),
            'has_publications' => $publishedIds->isNotEmpty(),
            ...$this->viewStats($publishedIds),
            ...$this->planStats($companyIds->first()),
        ];
    }

    /**
     * Total views plus a genuine 30-days-versus-previous-30-days trend. The
     * previous window is derived as (last 60 days − last 30 days) so both
     * numbers come from AggregatesCompanyViews as it already stands; no new
     * query helper is introduced.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $publishedIds
     * @return array<string, mixed>
     */
    private function viewStats($publishedIds): array
    {
        if ($publishedIds->isEmpty()) {
            return ['total_views' => 0, 'views_trend_percent' => null];
        }

        $recent = $this->viewsSinceForPublications($publishedIds, now()->subDays(self::TREND_WINDOW_DAYS));
        $previous = $this->viewsSinceForPublications($publishedIds, now()->subDays(self::TREND_WINDOW_DAYS * 2)) - $recent;

        return [
            'total_views' => $this->totalViewsForPublications($publishedIds),
            // A percentage against zero is undefined, not "infinite growth",
            // so the indicator is simply omitted for a first active window.
            'views_trend_percent' => $previous > 0
                ? (int) round((($recent - $previous) / $previous) * 100)
                : null,
        ];
    }

    /**
     * Plans are attached to a Company, not to a User, so a user with several
     * companies has several plans. This shows the first company's — the same
     * default ⚡subscriptions.blade.php falls back to — and the box links
     * through to that page where every company's plan is listed.
     *
     * @return array<string, mixed>
     */
    private function planStats(?int $primaryCompanyId): array
    {
        $subscription = $primaryCompanyId === null
            ? null
            : Company::query()->find($primaryCompanyId)?->activeSubscription();

        if ($subscription === null) {
            // Every key is present in both branches so the template can read
            // them without guarding for a missing index.
            return [
                'plan_name' => null,
                'plan_days_remaining' => null,
                'plan_expires_at' => null,
                'plan_never_expires' => false,
            ];
        }

        return [
            'plan_name' => (string) $subscription->plan?->name,
            // LocalizedDate formats dates, not durations, so the day count is
            // a plain number through the project's number_format convention;
            // the expiry date itself goes through LocalizedDate in the title.
            'plan_days_remaining' => $subscription->ends_at
                ? max(0, (int) now()->diffInDays($subscription->ends_at, false))
                : null,
            'plan_expires_at' => $subscription->ends_at
                ? LocalizedDate::format($subscription->ends_at)
                : null,
            'plan_never_expires' => $subscription->ends_at === null,
        ];
    }
};
?>

@php
    $stats = $this->stats;
@endphp

<div class="card mb-5 mb-xl-10">
    <div class="card-body pt-9 pb-0">
        <!--begin::Details-->
        <div class="d-flex flex-wrap flex-sm-nowrap">
            <!--begin: Pic-->
            <div class="me-7 mb-4">
                <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
                    <img src="{{ auth()->user()->getFirstMediaUrl('avatar', 'webp') ?: asset('theme/1/media/avatars/avatar-placeholder.png') }}" alt="image">
                    <div class="position-absolute translate-middle bottom-0 start-100 mb-6 bg-success rounded-circle border border-4 border-body h-20px w-20px"></div>
                </div>
            </div>
            <!--end::Pic-->
            <!--begin::Info-->
            <div class="flex-grow-1">
                <!--begin::Title-->
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                    <!--begin::user-->
                    <div class="d-flex flex-column">
                        <!--begin::نام-->
                        <div class="d-flex align-items-center mb-2">
                            <a href="#" class="text-gray-900 text-hover-primary fs-2 fw-bold me-1">
                                {{ auth()->user()->name . ' ' . auth()->user()->family }}
                            </a>
                            <a href="#">
                                <i class="ki-duotone ki-verify fs-1 text-primary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </a>
                        </div>
                        <!--end::نام-->
                        <!--begin::Info-->
                        <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                            <a href="#" class="d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2">
                                <i class="ki-duotone ki-profile-circle fs-4 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                                {{ auth()->user()->getRoleNames()->map(fn($role) => __("roles.{$role}"))->join('، ') }}
                            </a>
                            <a href="#" class="d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2">
                                <i class="ki-duotone ki-geolocation fs-4 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ auth()->user()->profile?->country?->name ?? __('menu.not_registered') }}
                            </a>
                            <span href="#" class="d-flex align-items-center text-gray-500 text-hover-primary mb-2">
                                        <i class="ki-duotone ki-sms fs-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        {{ auth()->user()->email }}
                                    </span>
                        </div>
                        <!--end::Info-->
                    </div>
                    <!--end::user-->
                    <!--begin::Actions-->
                    <div class="d-flex my-4">
                        <a href="{{ route('settings') }}" class="btn btn-sm btn-primary me-3">
                            {{ __('menu.settings') }}
                        </a>
                    </div>
                    <!--end::Actions-->
                </div>
                <!--end::Title-->
                <!--begin::Stats-->
                <div class="d-flex flex-wrap flex-stack">
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-column flex-grow-1 pe-8">
                        <!--begin::Stats-->
                        <div class="d-flex flex-wrap">
                            <!--begin::Published companies-->
                            {{-- No trend indicator: there is no stored previous
                                 value to compare a company count against, and a
                                 fabricated one would be worse than none. --}}
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="fs-2 fw-bold">
                                        {{ $stats['company_count'] === 0 ? '—' : number_format($stats['published_count']) }}
                                    </div>
                                </div>
                                <div class="fw-semibold fs-6 text-gray-500">
                                    {{ $stats['company_count'] === 0
                                        ? __('dashboard.stats.no_companies_yet')
                                        : __('dashboard.stats.published_companies') }}
                                </div>
                            </div>
                            <!--end::Published companies-->
                            <!--begin::Total views-->
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                <div class="d-flex align-items-center">
                                    @if ($stats['views_trend_percent'] !== null)
                                        {{-- Real 30d-vs-previous-30d comparison from the
                                             views table; only rendered when the previous
                                             window actually had views to compare with. --}}
                                        <i class="ki-duotone {{ $stats['views_trend_percent'] >= 0 ? 'ki-arrow-up fs-3 text-success' : 'ki-arrow-down fs-3 text-danger' }} me-2"
                                           title="{{ __('dashboard.stats.views_trend_window') }}">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    @endif
                                    <div class="fs-2 fw-bold">
                                        {{ $stats['has_publications'] ? number_format($stats['total_views']) : '—' }}
                                    </div>
                                    @if ($stats['views_trend_percent'] !== null)
                                        <span class="fs-7 fw-semibold {{ $stats['views_trend_percent'] >= 0 ? 'text-success' : 'text-danger' }} ms-2">
                                            {{ number_format(abs($stats['views_trend_percent'])) }}%
                                        </span>
                                    @endif
                                </div>
                                <div class="fw-semibold fs-6 text-gray-500">
                                    {{ $stats['has_publications']
                                        ? __('dashboard.stats.total_views')
                                        : __('dashboard.stats.views_no_pages') }}
                                </div>
                            </div>
                            <!--end::Total views-->
                            <!--begin::Subscription-->
                            {{-- Actionable: the whole box links to the plans page,
                                 where every owned company's plan is listed. --}}
                            <a href="{{ route('subscriptions') }}"
                               class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3 text-hover-primary">
                                <div class="d-flex align-items-center">
                                    <div class="fs-2 fw-bold text-gray-900">
                                        {{ $stats['plan_name'] ?? '—' }}
                                    </div>
                                </div>
                                <div class="fw-semibold fs-6 text-gray-500">
                                    @if ($stats['plan_name'] === null)
                                        {{ __('dashboard.stats.no_active_plan') }}
                                    @elseif ($stats['plan_never_expires'])
                                        {{ __('subscriptions.never_expires') }}
                                    @else
                                        <span title="{{ __('dashboard.subscription_expires_at', ['date' => $stats['plan_expires_at']]) }}">
                                            {{ __('dashboard.stats.plan_days_remaining', ['count' => number_format($stats['plan_days_remaining'])]) }}
                                        </span>
                                    @endif
                                </div>
                            </a>
                            <!--end::Subscription-->
                        </div>
                        <!--end::Stats-->
                    </div>
                    <!--end::Wrapper-->
                    <!--begin::پردازش-->
                    <livewire:dashboard-elements.profile-progress />
                    <!--end::پردازش-->
                </div>
                <!--end::Stats-->
            </div>
            <!--end::Info-->
        </div>
        <!--end::Details-->
        <!--begin::Navs-->
        <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
            <!--begin::Nav item-->
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    {{ __('menu.user_dashboard') }}
                </a>
            </li>
            <!--end::Nav item-->
            <!--begin::Nav item-->
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('my-companies') ? 'active' : '' }}" href="{{ route('my-companies') }}">
                    {{ __('menu.my_companies') }}
                </a>
            </li>
            <!--end::Nav item-->
            <!--begin::Nav item-->
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('subscriptions') ? 'active' : '' }}" href="{{ route('subscriptions') }}">
                    {{ __('menu.subscriptions_and_plans') }}
                </a>
            </li>
            <!--end::Nav item-->
            <!--begin::Nav item-->
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('payments') ? 'active' : '' }}" href="{{ route('payments') }}">
                    {{ __('menu.payment_history') }}
                </a>
            </li>
            <!--end::Nav item-->
            <!--begin::Nav item-->
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('profile') ? 'active' : '' }}" href="{{ route('profile') }}">
                    {{ __('menu.my_profile') }}
                </a>
            </li>
            <!--end::Nav item-->
            <!--begin::Nav item-->
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('company-views') ? 'active' : '' }}" href="{{ route('company-views') }}">
                    {{ __('menu.company_visit_statistics') }}
                </a>
            </li>
            <!--end::Nav item-->
            <!--begin::Nav item-->
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('chat') ? 'active' : '' }}" href="{{ route('chat') }}">
                    {{ __('menu.chat') }}
                </a>
            </li>
            <!--end::Nav item-->
            <!--begin::Nav item-->
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('tickets') ? 'active' : '' }}" href="{{ route('tickets') }}">
                    {{ __('menu.tickets') }}
                </a>
            </li>
            <!--end::Nav item-->
            <!--begin::Nav item-->
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('wordpress-content') ? 'active' : '' }}" href="{{ route('wordpress-content') }}">
                    {{ __('menu.wordpress_content') }}
                </a>
            </li>
            <!--end::Nav item-->
            <!--begin::Nav item-->
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('settings') ? 'active' : '' }}" href="{{ route('settings') }}">
                    {{ __('menu.settings') }}
                </a>
            </li>
            <!--end::Nav item-->
        </ul>
        <!--begin::Navs-->
    </div>
</div>
