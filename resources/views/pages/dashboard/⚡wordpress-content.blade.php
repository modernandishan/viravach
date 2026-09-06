<?php

use App\Enums\WordPressConnectionStatus;
use App\Enums\WordPressPostStatus;
use App\Models\Company;
use App\Support\LocalizedDate;
use App\Support\WordPressContentQuota;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts::landing')]
class extends Component {
    /**
     * Same optional {company?} + in-page-selector convention as ⚡settings
     * and ⚡subscriptions: this page is per-company but the dashboard tab
     * that opens it is global.
     */
    #[Url]
    public ?int $selectedCompanyId = null;

    public function mount(?Company $company = null): void
    {
        if ($company) {
            abort_unless($company->user_id === Auth::id(), 403);
            $this->selectedCompanyId = $company->id;
        }

        if (! $this->selectedCompanyId) {
            $this->selectedCompanyId = $this->myCompanies()->first()?->id;
        }
    }

    /** @return Collection<int, Company> */
    #[Computed]
    public function myCompanies(): Collection
    {
        return Company::where('user_id', Auth::id())->orderBy('id')->get();
    }

    public function selectedCompany(): ?Company
    {
        return $this->myCompanies()->firstWhere('id', $this->selectedCompanyId);
    }

    public function quota(): ?WordPressContentQuota
    {
        $company = $this->selectedCompany();

        return $company !== null ? WordPressContentQuota::for($company) : null;
    }

    /** @return Collection<int, \App\Models\WordPressContentPost> */
    public function posts(): Collection
    {
        $company = $this->selectedCompany();

        if ($company === null) {
            return new Collection;
        }

        return $company->wordPressContentPosts()->latest()->limit(50)->get();
    }

    public function hasProcessingPost(): bool
    {
        return $this->posts()->contains(fn ($post) => $post->status->isProcessing());
    }

    /**
     * When the company's most recent generation attempt happened, of any
     * status — the same moment the scheduler's 3-day cadence is measured
     * against, so the page and the scheduler can never disagree.
     */
    public function lastAttemptAt(): ?Carbon
    {
        $company = $this->selectedCompany();

        if ($company === null) {
            return null;
        }

        return $company->wordPressContentPosts()->latest()->value('created_at');
    }

    /**
     * When the next scheduled attempt lands: the latest attempt plus the
     * scheduler's 3-day cadence, or null when that window has already
     * passed (the article goes out with the next nightly run).
     */
    public function nextGenerationAt(): ?Carbon
    {
        $lastAttempt = $this->lastAttemptAt();

        if ($lastAttempt === null) {
            return null;
        }

        return $lastAttempt->copy()->addDays(3);
    }

    public function render()
    {
        return $this->view()->title(
            __('wordpress_content.page_title').' | '.__('auth.user-dashboard').' | '.__('globals.viravach')
        );
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">
        <livewire:dashboard-elements.infobar/>

        @if ($this->myCompanies()->isEmpty())
            <div class="card">
                <div class="card-body text-center py-15">
                    <p class="fs-4 text-gray-700">{{ __('subscriptions.no_companies_notice') }}</p>
                    <a href="{{ route('create.company') }}" class="btn btn-primary">
                        {{ __('subscriptions.create_company_cta') }}
                    </a>
                </div>
            </div>
        @else
            {{-- Company selector --}}
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
                </div>
            </div>

            @php
                $company = $this->selectedCompany();
                $quota = $this->quota();
            @endphp

            {{-- Observer card: generation is fully automatic. The owner sees
                 the allowance and when the next attempt lands; there is no
                 manual trigger on this page. --}}
            <div class="card mb-5 mb-xl-10">
                <div class="card-header">
                    <div class="card-title">
                        <h3>{{ __('wordpress_content.schedule_section_title') }}</h3>
                    </div>
                </div>
                <div class="card-body">
                    @if ($company?->wp_connection_status !== WordPressConnectionStatus::Connected)
                        <div class="alert alert-warning d-flex align-items-center p-5">
                            <div class="d-flex flex-column">
                                <span>{{ __('wordpress_content.guard_not_connected') }}</span>
                                <a href="{{ route('settings', $company) }}" class="fw-bold mt-2">
                                    {{ __('wordpress_content.guard_go_to_settings') }}
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
                            <span class="badge badge-light-primary fs-7">
                                {{ __('wordpress_content.quota_used', ['used' => $quota->used(), 'limit' => $quota->limit()]) }}
                            </span>
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <i class="ki-duotone ki-calendar-tick fs-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            @if ($this->hasProcessingPost())
                                <span class="fw-semibold text-gray-800">{{ __('wordpress_content.schedule_in_progress') }}</span>
                            @elseif (! $quota->canGenerate())
                                <span class="fw-semibold text-gray-800">
                                    {{ __('wordpress_content.schedule_quota_reached', [
                                        'date' => LocalizedDate::format($quota->resetsAt(), LocalizedDate::FORMAT_DATE),
                                    ]) }}
                                </span>
                            @elseif (($next = $this->nextGenerationAt()) !== null && $next->isFuture())
                                <span class="fw-semibold text-gray-800">
                                    {{ __('wordpress_content.schedule_next_at', [
                                        'date' => LocalizedDate::format($next, LocalizedDate::FORMAT_DATE),
                                    ]) }}
                                </span>
                            @else
                                <span class="fw-semibold text-gray-800">{{ __('wordpress_content.schedule_tonight') }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mb-5 mb-xl-10" wire:poll.10s="$refresh">
                <div class="card-header">
                    <div class="card-title">
                        <h3>{{ __('wordpress_content.history_title') }}</h3>
                    </div>
                </div>
                <div class="card-body">
                    @forelse ($this->posts() as $post)
                        <div class="d-flex align-items-start {{ $loop->last ? '' : 'mb-5 pb-5 border-bottom' }}">
                            <div class="d-flex flex-column flex-grow-1">
                                @if ($post->wp_post_url)
                                    <a href="{{ $post->wp_post_url }}" target="_blank" rel="noopener noreferrer"
                                       class="fw-bold text-gray-900 text-hover-primary">
                                        {{ $post->title ?: $post->topic }}
                                    </a>
                                @else
                                    <span class="fw-bold text-gray-900">{{ $post->title ?: $post->topic }}</span>
                                @endif
                                <div class="d-flex flex-wrap align-items-center gap-3 mt-1">
                                    <span class="badge badge-light-{{ $post->status->getColor() }}">
                                        {{ $post->status->getLabel() }}
                                    </span>
                                    <span class="fs-7 text-muted">{{ $post->mode->getLabel() }}</span>
                                    <span class="fs-7 text-muted">{{ strtoupper($post->locale) }}</span>
                                    <span class="fs-7 text-muted">
                                        {{ LocalizedDate::format($post->created_at, LocalizedDate::FORMAT_DATETIME) }}
                                    </span>
                                </div>
                                @if ($post->status === WordPressPostStatus::Failed && $post->failure_reason)
                                    <span class="fs-7 text-danger mt-1">{{ __($post->failure_reason) }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="fs-6 text-gray-600">{{ __('wordpress_content.history_empty') }}</div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</div>
