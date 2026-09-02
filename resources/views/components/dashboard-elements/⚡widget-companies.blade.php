<?php

use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Widget 3 — the user's companies with their review status and whether they
 * are live on the public site.
 *
 * Cost: one indexed query, with `publication` eager loaded so the "is live"
 * check does not fire once per row (the N+1 this widget would otherwise
 * have).
 *
 * Deliberately NOT cached. review_status changes from several dashboard
 * paths (create, edit, the AI finalize job) and from the admin panel's
 * approve/reject actions — only one of which (approve → publish) is a
 * chokepoint. A cached badge showing "approved" after a rejection is exactly
 * the staleness this phase set out to avoid, and one eager-loaded query is
 * cheap enough not to trade correctness for.
 */
new class extends Component
{
    private const LIMIT = 5;

    /**
     * @return Collection<int, Company>
     */
    #[Computed]
    public function companies(): Collection
    {
        return Company::query()
            ->where('user_id', auth()->id())
            ->with('publication:id,company_id,published_at')
            ->latest()
            ->limit(self::LIMIT)
            ->get();
    }

    /**
     * Live means an approved snapshot whose published_at has arrived — the
     * same rule the public site applies, so this can never claim a company
     * is visible when it is not.
     */
    public function isLive(Company $company): bool
    {
        $publishedAt = $company->publication?->published_at;

        return $publishedAt !== null && $publishedAt->lessThanOrEqualTo(now());
    }
};
?>

<div class="card card-flush">
    <div class="card-header align-items-center py-5">
        <div class="card-title">
            <h2 class="fs-4">{{ __('dashboard.companies_quick_list_title') }}</h2>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('my-companies') }}" class="btn btn-sm btn-light">
                {{ __('dashboard.view_all_companies') }}
            </a>
        </div>
    </div>
    <div class="card-body pt-0">
        @foreach ($this->companies as $company)
            <div class="d-flex flex-stack py-3 {{ ! $loop->last ? 'border-bottom border-gray-300 border-dashed' : '' }}">
                <span class="fw-semibold text-gray-800 text-truncate me-3">{{ $company->name }}</span>
                <span class="d-flex align-items-center gap-2 flex-shrink-0">
                    @if ($this->isLive($company))
                        <span class="badge badge-light-success">{{ __('dashboard.company_is_live') }}</span>
                    @endif
                    {{-- Label comes from the enum, which now resolves through
                         __() in every locale (see CompanyReviewStatus). --}}
                    <span class="badge badge-light-{{ $company->review_status->getColor() }}">
                        {{ $company->review_status->getLabel() }}
                    </span>
                </span>
            </div>
        @endforeach
    </div>
</div>
