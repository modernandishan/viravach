<?php

use App\Enums\ContentGenerationMode;
use App\Enums\WordPressConnectionStatus;
use App\Enums\WordPressPostStatus;
use App\Models\Company;
use App\Services\WordPress\WordPressContentGenerationService;
use App\Support\LocalizedDate;
use App\Support\WordPressContentQuota;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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

    /** Defaults to fa per the product requirement: one generation, one language. */
    public string $locale = 'fa';

    public string $mode = ContentGenerationMode::Industry->value;

    public ?string $generateFailureReason = null;

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

    public function updatedSelectedCompanyId(): void
    {
        $this->generateFailureReason = null;
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

    /** @return array<string, string> */
    public function locales(): array
    {
        return array_map(
            fn (array $locale): string => $locale['name'],
            (array) config('laravellocalization.supportedLocales'),
        );
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

    public function generate(WordPressContentGenerationService $service): void
    {
        $company = $this->selectedCompany();
        abort_unless($company !== null, 404);

        $this->generateFailureReason = null;

        $this->validate([
            'locale' => ['required', Rule::in(array_keys($this->locales()))],
            'mode' => ['required', Rule::enum(ContentGenerationMode::class)],
        ]);

        $result = $service->request($company, $this->locale, ContentGenerationMode::from($this->mode));

        if (! $result->successful) {
            $this->generateFailureReason = $result->reason?->value;

            return;
        }

        session()->flash('wordpress-content-status', __('wordpress_content.generation_queued'));
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

        @if (session('wordpress-content-status'))
            <div class="alert alert-success">{{ session('wordpress-content-status') }}</div>
        @endif

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

            <div class="card mb-5 mb-xl-10">
                <div class="card-header">
                    <div class="card-title">
                        <h3>{{ __('wordpress_content.generate_section_title') }}</h3>
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
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-6">
                            <span class="badge badge-light-primary fs-7">
                                {{ __('wordpress_content.quota_used', ['used' => $quota->used(), 'limit' => $quota->limit()]) }}
                            </span>
                            @if (! $quota->canGenerate())
                                <span class="fs-7 text-muted">
                                    {{ __('wordpress_content.quota_resets_at', ['date' => LocalizedDate::format($quota->resetsAt(), LocalizedDate::FORMAT_DATE)]) }}
                                </span>
                            @endif
                        </div>

                        <form wire:submit="generate">
                            <div class="row">
                                <div class="col-md-6 fv-row mb-7">
                                    <label class="form-label">{{ __('wordpress_content.language_label') }}</label>
                                    <select wire:model="locale" class="form-select form-select-solid @error('locale') is-invalid @enderror">
                                        @foreach ($this->locales() as $code => $name)
                                            <option value="{{ $code }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    @error('locale')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 fv-row mb-7">
                                    <label class="form-label">{{ __('wordpress_content.mode_label') }}</label>
                                    <div class="d-flex flex-column gap-2">
                                        @foreach (ContentGenerationMode::cases() as $modeCase)
                                            <label class="form-check form-check-custom form-check-solid">
                                                <input class="form-check-input" type="radio"
                                                       wire:model="mode" value="{{ $modeCase->value }}" />
                                                <span class="form-check-label">{{ $modeCase->getLabel() }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('mode')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            @if ($generateFailureReason)
                                <div class="alert alert-danger">
                                    {{ __('wordpress_content.guard_'.$generateFailureReason) }}
                                </div>
                            @endif

                            <button type="submit" class="btn btn-primary"
                                    wire:loading.attr="disabled" wire:target="generate"
                                    @disabled(! $quota->canGenerate() || $this->hasProcessingPost())>
                                <span class="indicator-label" wire:loading.remove wire:target="generate">
                                    {{ __('wordpress_content.generate_button') }}
                                </span>
                                <span class="indicator-progress" wire:loading.flex wire:target="generate" style="display: none;">
                                    {{ __('wordpress_content.generating') }}
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </form>
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
