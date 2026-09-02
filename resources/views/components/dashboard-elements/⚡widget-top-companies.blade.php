<?php

use App\Livewire\Concerns\AggregatesCompanyViews;
use App\Models\Company;
use App\Support\DashboardWidgetCache;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Widget 2 — the user's companies ranked by total views.
 *
 * Cost: two queries (companies with their publication id, then one grouped
 * count over `views`). Summarises what ⚡company-views reports in full, so it
 * links there instead of repeating its table.
 *
 * TTL-cached for the same reason as the chart: views have no chokepoint.
 */
new class extends Component
{
    use AggregatesCompanyViews;

    private const LIMIT = 5;

    /**
     * @return array<int, array{name: string, views: int}>
     */
    #[Computed]
    public function rows(): array
    {
        $userId = (int) auth()->id();

        return DashboardWidgetCache::rememberViews(
            DashboardWidgetCache::TOP_COMPANIES,
            $userId,
            function () use ($userId): array {
                $companies = Company::query()
                    ->where('user_id', $userId)
                    ->get(['id', 'name']);

                if ($companies->isEmpty()) {
                    return [];
                }

                $publicationIds = $this->publicationIdsByCompany($companies->pluck('id'));

                if ($publicationIds->isEmpty()) {
                    return [];
                }

                $totals = $this->viewCountsPerPublication($publicationIds->values());

                return $companies
                    ->map(fn (Company $company): array => [
                        'name' => (string) $company->name,
                        'views' => (int) ($totals[$publicationIds->get($company->id)] ?? 0),
                    ])
                    ->filter(fn (array $row): bool => $row['views'] > 0)
                    ->sortByDesc('views')
                    ->take(self::LIMIT)
                    ->values()
                    ->all();
            },
        );
    }
};
?>

<div class="card card-flush">
    <div class="card-header align-items-center py-5">
        <div class="card-title">
            <h2 class="fs-4">{{ __('dashboard.top_companies_title') }}</h2>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('company-views') }}" class="btn btn-sm btn-light">
                {{ __('dashboard.view_all_companies') }}
            </a>
        </div>
    </div>
    <div class="card-body pt-0">
        @forelse ($this->rows as $row)
            <div class="d-flex flex-stack py-3 {{ ! $loop->last ? 'border-bottom border-gray-300 border-dashed' : '' }}">
                <span class="fw-semibold text-gray-800 text-truncate">{{ $row['name'] }}</span>
                <span class="fw-bold text-gray-900 flex-shrink-0 ms-3">{{ number_format($row['views']) }}</span>
            </div>
        @empty
            <div class="vv-dash-widget-empty">
                <i class="ki-duotone ki-eye vv-dash-widget-empty-icon">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <p class="vv-dash-widget-empty-text">{{ __('dashboard.empty_no_views') }}</p>
            </div>
        @endforelse
    </div>
</div>
