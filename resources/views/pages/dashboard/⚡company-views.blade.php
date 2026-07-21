<?php

use App\Livewire\Concerns\AggregatesCompanyViews;
use App\Models\Company;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts::landing')]
class extends Component
{
    use AggregatesCompanyViews;

    /**
     * Per-company view stats. Public visits are recorded against the
     * company's publication snapshot, so counts are resolved through the
     * publication id (companies never approved simply count zero).
     *
     * @return Collection<int, array{company: Company, total_views: int, recent_views: int}>
     */
    public function rows(): Collection
    {
        $companies = Company::query()
            ->where('user_id', auth()->id())
            ->with('media')
            ->latest()
            ->get();

        $publicationIds = $this->publicationIdsByCompany($companies->pluck('id'));
        $totals = $this->viewCountsPerPublication($publicationIds->values());
        $recents = $this->viewCountsPerPublication($publicationIds->values(), now()->subDays(30));

        return $companies->map(function (Company $company) use ($publicationIds, $totals, $recents): array {
            $publicationId = $publicationIds->get($company->id);

            return [
                'company' => $company,
                'total_views' => (int) ($totals[$publicationId] ?? 0),
                'recent_views' => (int) ($recents[$publicationId] ?? 0),
            ];
        });
    }

    public function render()
    {
        return $this->view()->title(__('companies.views_page_title').' | '.__('auth.user-dashboard').' | '.__('globals.viravach'));
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">
        <livewire:dashboard-elements.infobar/>

        <div class="card card-flush">
            <div class="card-header align-items-center py-5">
                <div class="card-title">
                    <h2>{{ __('companies.views_page_title') }}</h2>
                </div>
            </div>
            <div class="card-body pt-0">
                @php
                    $rows = $this->rows();
                    $totalOfAll = $rows->sum('total_views');
                @endphp

                @if ($rows->isEmpty())
                    <div class="text-center text-muted py-10">{{ __('companies.views_no_companies_found') }}</div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-200px">{{ __('companies.views_table_company') }}</th>
                                <th class="min-w-100px">{{ __('companies.views_table_total') }}</th>
                                <th class="min-w-100px">{{ __('companies.views_table_last_30_days') }}</th>
                                <th class="min-w-150px">{{ __('companies.views_table_share') }}</th>
                            </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-600">
                            @foreach ($rows as $row)
                                <tr wire:key="company-views-{{ $row['company']->id }}">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-40px bg-light me-4">
                                                @if ($row['company']->hasMedia('logo'))
                                                    <img src="{{ $row['company']->getFirstMediaUrl('logo', 'webp') }}" alt="{{ $row['company']->name }}" class="p-2">
                                                @else
                                                    <span class="symbol-label bg-light-primary text-primary fw-bold">
                                                        {{ \Illuminate\Support\Str::substr($row['company']->name, 0, 1) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <span class="text-gray-800 fw-bold">{{ $row['company']->name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ number_format($row['total_views']) }}</td>
                                    <td>{{ number_format($row['recent_views']) }}</td>
                                    <td>
                                        @php
                                            $share = $totalOfAll > 0 ? round($row['total_views'] * 100 / $totalOfAll) : 0;
                                        @endphp
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="progress h-6px w-100px bg-light-primary">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $share }}%"></div>
                                            </div>
                                            <span class="text-gray-500 fs-7">{{ $share }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
