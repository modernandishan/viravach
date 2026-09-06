<?php

use App\Enums\CompanyReviewStatus;
use App\Enums\ContentGenerationMode;
use App\Enums\SeoPlugin;
use App\Enums\WordPressConnectionStatus;
use App\Models\Company;
use App\Services\WordPress\WordPressConnectionService;
use App\Support\LocalizedDate;
use App\Support\PlanFeature;
use App\Support\VideoMetadata;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

new
#[Layout('layouts::landing')]
class extends Component {
    use WithFileUploads;
    /**
     * Every setting on this page lives on a Company, but the dashboard's
     * "Settings" button is global, so the company is chosen the same way
     * ⚡subscriptions chooses one: an optional {company?} route segment,
     * falling back to the user's first company, with an in-page selector
     * for the rest.
     */
    #[Url]
    public ?int $selectedCompanyId = null;

    /** The website WITHOUT its scheme — the input group renders https:// itself. */
    public ?string $website = null;

    public ?string $seoPlugin = null;

    public ?string $wpUsername = null;

    public ?string $wpApplicationPassword = null;

    public ?string $contentGenerationMode = null;

    /** The language generated articles are written in; defaults to fa. */
    public string $contentLanguage = 'fa';

    /**
     * The pending intro-video upload (plan-gated). Kept separate from
     * save(): a 256MB temp upload must not be re-transferred by the main
     * settings form's other saves.
     */
    public $introVideo = null;

    /**
     * Feedback from the connection test performed in *this* request. The
     * persisted status/posts on the Company survive a reload; these three
     * only decorate the answer the user just asked for.
     */
    public ?string $connectionFailureReason = null;

    public ?string $connectionSiteName = null;

    public ?string $connectionUserName = null;

    /** Set when the form holds edits the connection test would not have used. */
    public bool $connectionNeedsSave = false;

    public function mount(?Company $company = null): void
    {
        if ($company) {
            abort_unless($company->user_id === auth()->id(), 403);
            $this->selectedCompanyId = $company->id;
        }

        if (! $this->selectedCompanyId) {
            $this->selectedCompanyId = $this->myCompanies()->first()?->id;
        }

        $this->fillFromSelectedCompany();
    }

    /**
     * Switching companies in the selector must reload the form, otherwise the
     * previous company's values would be shown — and saved — against the new
     * one. A stale error bag from the previous company is dropped too.
     */
    public function updatedSelectedCompanyId(): void
    {
        $this->resetErrorBag();
        $this->fillFromSelectedCompany();
    }

    /** @return Collection<int, Company> */
    #[Computed]
    public function myCompanies(): Collection
    {
        return Company::where('user_id', auth()->id())->orderBy('id')->get();
    }

    /**
     * Resolved against the user's own companies, so a hand-edited
     * ?selectedCompanyId= belonging to somebody else simply resolves to null
     * rather than exposing their settings.
     */
    public function selectedCompany(): ?Company
    {
        return $this->myCompanies()->firstWhere('id', $this->selectedCompanyId);
    }

    protected function fillFromSelectedCompany(): void
    {
        $company = $this->selectedCompany();

        // Stored values always carry the https:// scheme; the input shows
        // only the rest, since the form's input-group re-adds the prefix.
        $this->website = $company?->website !== null
            ? Str::after((string) $company->website, 'https://')
            : null;
        $this->seoPlugin = $company?->seo_plugin?->value;
        $this->wpUsername = $company?->wp_username;
        $this->wpApplicationPassword = $company?->wp_application_password;
        $this->contentGenerationMode = $company?->content_generation_mode?->value;
        $this->contentLanguage = $company?->content_language ?? 'fa';

        $this->resetConnectionFeedback();
    }

    protected function resetConnectionFeedback(): void
    {
        $this->connectionFailureReason = null;
        $this->connectionSiteName = null;
        $this->connectionUserName = null;
        $this->connectionNeedsSave = false;
    }

    /**
     * Verifies the saved credentials against the company's own WordPress site.
     *
     * The service reads the persisted Company, so a form holding unsaved edits
     * would get an answer about the *previous* credentials while the user is
     * looking at the new ones — a pass or a fail that means nothing either way.
     * Rather than test the wrong values, ask for a save first.
     */
    public function testConnection(WordPressConnectionService $service): void
    {
        $company = $this->selectedCompany();
        abort_unless($company !== null, 404);

        $this->resetConnectionFeedback();

        if ($this->hasUnsavedConnectionChanges($company)) {
            $this->connectionNeedsSave = true;

            return;
        }

        $result = $service->testConnection($company);

        if ($result->successful) {
            $this->connectionSiteName = $result->siteName;
            $this->connectionUserName = $result->userName;
        } else {
            $this->connectionFailureReason = $result->reason?->value;
        }

        // The service wrote the status, timestamp and post list; drop the
        // cached collection so the card below re-reads them from the database.
        unset($this->myCompanies);
    }

    public function save(): void
    {
        $company = $this->selectedCompany();
        abort_unless($company !== null, 404);

        $this->normalizeWebsite();

        $this->validate([
            'website' => ['nullable', 'url', 'max:255'],
            'seoPlugin' => ['required', 'string', Rule::enum(SeoPlugin::class)],
            'wpUsername' => ['nullable', 'string', 'max:255'],
            'wpApplicationPassword' => ['nullable', 'string', 'max:255'],
            'contentGenerationMode' => ['required', 'string', Rule::enum(ContentGenerationMode::class)],
            'contentLanguage' => [
                'required',
                'string',
                Rule::in(array_keys((array) config('laravellocalization.supportedLocales'))),
            ],
        ]);

        $company->fill([
            'website' => $this->website ?: null,
            'seo_plugin' => $this->seoPlugin,
            'wp_username' => $this->wpUsername ?: null,
            'wp_application_password' => $this->wpApplicationPassword ?: null,
            'content_generation_mode' => $this->contentGenerationMode,
            'content_language' => $this->contentLanguage,
        ]);

        // The website is a reviewed attribute, so editing it here sends the
        // draft back into the review queue exactly as ⚡edit-company does. The
        // others are operator settings, never public content, so on their own
        // they leave the review status alone.
        $reviewedFieldsChanged = $company->isDirty(Company::REVIEWED_ATTRIBUTES);

        $company->save();

        if ($reviewedFieldsChanged) {
            $company->update(['review_status' => CompanyReviewStatus::PendingReview]);
        }

        // normalizeWebsite() put the scheme back on the property; strip it
        // again so the input keeps showing only the domain part.
        $this->website = $company->website !== null
            ? Str::after($company->website, 'https://')
            : null;

        unset($this->myCompanies);

        session()->flash('settings-status', __('settings.saved_successfully'));
    }

    /**
     * Whether the three fields the connection test depends on still hold what
     * was actually saved. The website is compared in its stored form, since
     * the input carries only the domain part.
     */
    protected function hasUnsavedConnectionChanges(Company $company): bool
    {
        return $this->withScheme($this->website) !== $company->website
            || ($this->wpUsername ?: null) !== $company->wp_username
            || ($this->wpApplicationPassword ?: null) !== $company->wp_application_password;
    }

    /**
     * The website input holds only the domain part: any leading scheme or
     * protocol-relative slashes the user pasted is stripped before the
     * https:// prefix is prepended, so the stored value is always a full
     * URL (null when empty).
     */
    protected function normalizeWebsite(): void
    {
        $this->website = $this->withScheme($this->website);
    }

    protected function withScheme(?string $website): ?string
    {
        $website = preg_replace('#^(https?://|//)#i', '', trim((string) ($website ?? ''))) ?? '';

        return $website !== '' ? 'https://'.$website : null;
    }

    /**
     * Whether the selected company's plan grants the intro-video feature.
     * Read live from the plan feature (PlanFeature), so a plan change takes
     * effect immediately — same convention as the WordPress quotas.
     */
    public function canUploadIntroVideo(): bool
    {
        $company = $this->selectedCompany();

        return $company !== null
            && PlanFeature::value($company, 'intro-video') === 'true';
    }

    /** The currently stored intro video, if any. */
    public function currentIntroVideo(): ?Media
    {
        return $this->selectedCompany()?->getFirstMedia('intro_video');
    }

    /**
     * Stores (or replaces) the company's single intro video. The full
     * chain re-runs here even though the UI gates the card: eligibility →
     * file type/size → duration (getID3 reads only container metadata, so
     * a 256MB file costs no more than a small one).
     */
    public function saveIntroVideo(): void
    {
        $company = $this->selectedCompany();
        abort_unless($company !== null, 404);

        if (! $this->canUploadIntroVideo()) {
            throw ValidationException::withMessages([
                'introVideo' => __('settings.intro_video_ineligible'),
            ]);
        }

        $this->validate([
            'introVideo' => ['required', 'file', 'mimes:mp4,webm,mov,avi,mkv', 'max:262144'],
        ]);

        $duration = app(VideoMetadata::class)->durationSeconds($this->introVideo->getRealPath());

        if ($duration === null || $duration > VideoMetadata::MAX_SECONDS) {
            throw ValidationException::withMessages([
                'introVideo' => __('settings.intro_video_validation_duration'),
            ]);
        }

        $company->clearMediaCollection('intro_video');

        $company->addMedia($this->introVideo->getRealPath())
            ->usingFileName($this->introVideo->getClientOriginalName())
            ->toMediaCollection('intro_video', 's3');

        $this->introVideo = null;

        session()->flash('settings-status', __('settings.intro_video_saved'));
    }

    /** Replaces nothing silently: removal is its own explicit action. */
    public function removeIntroVideo(): void
    {
        $company = $this->selectedCompany();
        abort_unless($company !== null, 404);

        $company->clearMediaCollection('intro_video');

        $this->introVideo = null;

        session()->flash('settings-status', __('settings.intro_video_removed'));
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'website' => __('companies.field_website'),
            'seoPlugin' => __('settings.seo_plugin_label'),
            'wpUsername' => __('settings.wp_username_label'),
            'wpApplicationPassword' => __('settings.app_password_label'),
            'contentGenerationMode' => __('settings.content_mode_label'),
            'contentLanguage' => __('settings.content_language_label'),
        ];
    }

    public function render()
    {
        return $this->view()->title(
            __('settings.page_title').' | '.__('auth.user-dashboard').' | '.__('globals.viravach')
        );
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">
        <livewire:dashboard-elements.infobar/>

        @if (session('settings-status'))
            <div class="alert alert-success">{{ session('settings-status') }}</div>
        @endif

        @if ($this->myCompanies()->isEmpty())
            {{-- Same empty state as ⚡subscriptions: both pages are per-company,
                 so "create a company first" is the identical dead end. --}}
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
                    <div>
                        <div class="text-muted fs-7">{{ __('settings.selector_hint_label') }}</div>
                        <div class="fw-semibold fs-7 text-gray-600">{{ __('settings.selector_hint') }}</div>
                    </div>
                </div>
            </div>

            <form wire:submit="save">
                {{-- Site & SEO --}}
                <div class="card mb-5 mb-xl-10">
                    <div class="card-header">
                        <div class="card-title">
                            <h3>{{ __('settings.site_section_title') }}</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="fv-row mb-7">
                            <label class="form-label">{{ __('companies.field_website') }}</label>
                            <div class="input-group input-group-lg input-group-solid" dir="ltr">
                                <span class="input-group-text">https://</span>
                                <input type="text" wire:model="website"
                                       class="form-control form-control-solid @error('website') is-invalid @enderror" />
                            </div>
                            <div class="form-text">{{ __('companies.field_website_hint') }}</div>
                            @error('website')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="fv-row mb-2">
                            <label class="form-label required">{{ __('settings.seo_plugin_label') }}</label>
                            <div class="form-text mt-0 mb-4">{{ __('settings.seo_plugin_hint') }}</div>
                            <div class="d-flex flex-wrap gap-8">
                                @foreach (SeoPlugin::cases() as $plugin)
                                    <label class="form-check form-check-custom form-check-solid align-items-start">
                                        <input class="form-check-input mt-1" type="radio"
                                               wire:model="seoPlugin" value="{{ $plugin->value }}"
                                               id="settings-seo-plugin-{{ $plugin->value }}" />
                                        <span class="form-check-label d-flex flex-column ms-3">
                                            <span class="fw-bold text-gray-900">{{ $plugin->getLabel() }}</span>
                                            <span class="text-muted fs-7">{{ __('settings.seo_plugin_'.$plugin->value.'_hint') }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('seoPlugin')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- WordPress connection --}}
                <div class="card mb-5 mb-xl-10">
                    <div class="card-header">
                        <div class="card-title">
                            <h3>{{ __('settings.wordpress_section_title') }}</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-6 mb-10 mb-lg-0">
                                <div class="fv-row mb-7">
                                    <label class="form-label">{{ __('settings.wp_username_label') }}</label>
                                    <input type="text" dir="ltr" wire:model="wpUsername"
                                           placeholder="{{ __('settings.wp_username_placeholder') }}"
                                           class="form-control form-control-lg form-control-solid @error('wpUsername') is-invalid @enderror" />
                                    <div class="form-text">{{ __('settings.wp_username_hint') }}</div>
                                    @error('wpUsername')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="fv-row">
                                    <label class="form-label">{{ __('settings.app_password_label') }}</label>
                                    <input type="text" dir="ltr" wire:model="wpApplicationPassword"
                                           placeholder="{{ __('settings.app_password_placeholder') }}"
                                           class="form-control form-control-lg form-control-solid @error('wpApplicationPassword') is-invalid @enderror" />
                                    <div class="form-text">{{ __('settings.app_password_hint') }}</div>
                                    @error('wpApplicationPassword')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6 mt-8">
                                    <i class="ki-duotone ki-shield-tick fs-2tx text-warning me-4">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <div class="d-flex flex-stack flex-grow-1">
                                        <div class="fw-semibold">
                                            <h4 class="text-gray-900 fw-bold">{{ __('settings.app_password_security_title') }}</h4>
                                            <div class="fs-6 text-gray-700">{{ __('settings.app_password_security_body') }}</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Connection test: proves the saved URL + username + application
                                     password really reach the owner's own WordPress site. --}}
                                @php
                                    $wpCompany = $this->selectedCompany();
                                    $wpStatus = $wpCompany?->wp_connection_status ?? WordPressConnectionStatus::NotTested;
                                    $wpPosts = $wpCompany?->wp_last_posts ?? [];
                                @endphp

                                <div class="separator separator-dashed my-8"></div>

                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-5">
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-gray-900 fs-6">{{ __('settings.wp_test_title') }}</span>
                                        <span class="fs-7 text-gray-600">{{ __('settings.wp_test_hint') }}</span>
                                    </div>
                                    <button type="button" class="btn btn-light-primary btn-sm"
                                            wire:click="testConnection"
                                            wire:loading.attr="disabled" wire:target="testConnection">
                                        <span class="indicator-label" wire:loading.remove wire:target="testConnection">
                                            {{ __('settings.wp_test_button') }}
                                        </span>
                                        <span class="indicator-progress" wire:loading.flex wire:target="testConnection"
                                              style="display: none;">
                                            {{ __('settings.wp_testing') }}
                                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    </button>
                                </div>

                                <div class="d-flex flex-wrap align-items-center gap-2 mb-5">
                                    <span class="badge badge-light-{{ $wpStatus === WordPressConnectionStatus::Connected ? 'success' : ($wpStatus === WordPressConnectionStatus::Failed ? 'danger' : 'secondary') }}">
                                        {{ $wpStatus->getLabel() }}
                                    </span>
                                    @if ($wpCompany?->wp_last_checked_at)
                                        <span class="fs-7 text-muted">
                                            {{ __('settings.wp_last_checked', ['date' => LocalizedDate::format($wpCompany->wp_last_checked_at, LocalizedDate::FORMAT_DATETIME)]) }}
                                        </span>
                                    @endif
                                </div>

                                @if ($connectionNeedsSave)
                                    <div class="alert alert-warning d-flex align-items-center p-5 mb-0">
                                        <i class="ki-duotone ki-information-5 fs-2tx text-warning me-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                        <div class="d-flex flex-column">
                                            <h5 class="mb-1">{{ __('settings.wp_test_unsaved_changes_title') }}</h5>
                                            <span>{{ __('settings.wp_test_unsaved_changes') }}</span>
                                        </div>
                                    </div>
                                @elseif ($connectionFailureReason)
                                    <div class="alert alert-danger d-flex align-items-center p-5 mb-0">
                                        <i class="ki-duotone ki-information-5 fs-2tx text-danger me-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                        <div class="d-flex flex-column">
                                            <h5 class="mb-1">{{ __('settings.wp_test_failed_title') }}</h5>
                                            <span>{{ __('settings.wp_test_failed_'.$connectionFailureReason) }}</span>
                                        </div>
                                    </div>
                                @elseif ($wpStatus === WordPressConnectionStatus::Connected)
                                    <div class="bg-light-success rounded border-success border border-dashed p-6">
                                        <div class="d-flex flex-column mb-5">
                                            <span class="fw-bold text-gray-900 fs-6">{{ __('settings.wp_test_success_title') }}</span>
                                            <span class="fs-7 text-gray-700">
                                                {{ __('settings.wp_connected_as', [
                                                    'user' => $connectionUserName ?: $wpCompany->wp_username,
                                                    'site' => $connectionSiteName ?: $wpCompany->website,
                                                ]) }}
                                            </span>
                                        </div>

                                        <div class="fw-bold text-gray-800 fs-7 text-uppercase mb-3">
                                            {{ __('settings.wp_recent_posts_title') }}
                                        </div>

                                        @forelse ($wpPosts as $post)
                                            <div class="d-flex align-items-start {{ $loop->last ? '' : 'mb-4' }}">
                                                <i class="ki-duotone ki-document fs-4 text-success me-3 mt-1">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                                <div class="d-flex flex-column">
                                                    <a href="{{ $post['link'] }}" target="_blank" rel="noopener noreferrer"
                                                       class="fw-semibold text-gray-900 text-hover-primary fs-7">
                                                        {{ $post['title'] !== '' ? $post['title'] : __('settings.wp_post_untitled') }}
                                                    </a>
                                                    @if (! empty($post['date']))
                                                        <span class="fs-8 text-muted">
                                                            {{ LocalizedDate::format(\Illuminate\Support\Carbon::parse($post['date']), LocalizedDate::FORMAT_DATE) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            <div class="fs-7 text-gray-600">{{ __('settings.wp_no_posts') }}</div>
                                        @endforelse
                                    </div>

                                    @if ($wpCompany?->wp_seo_meta_writable === false)
                                        <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4 mt-4">
                                            <span class="fs-7 text-gray-700">{{ __('settings.wp_seo_meta_not_writable') }}</span>
                                        </div>
                                    @endif
                                @endif
                            </div>

                            {{-- Step-by-step generation guide --}}
                            <div class="col-lg-6">
                                <div class="bg-light rounded p-8">
                                    <h4 class="fw-bold text-gray-900 mb-2">{{ __('settings.app_password_steps_title') }}</h4>
                                    <div class="fs-7 text-gray-600 mb-6">{{ __('settings.app_password_steps_intro') }}</div>

                                    <div class="mb-6">
                                        <div class="fw-bold text-gray-800 fs-7 text-uppercase mb-3">
                                            {{ __('settings.app_password_requirements_title') }}
                                        </div>
                                        @foreach (['wordpress_version', 'admin_access', 'https'] as $requirement)
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="ki-duotone ki-check-circle fs-4 text-success me-3">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                                <span class="fs-7 text-gray-700">
                                                    {{ __('settings.app_password_requirement_'.$requirement) }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="separator separator-dashed my-6"></div>

                                    @foreach (range(1, 5) as $step)
                                        <div class="d-flex align-items-start {{ $step === 5 ? '' : 'mb-5' }}">
                                            <span class="badge badge-circle badge-primary fw-bold me-4 flex-shrink-0">{{ $step }}</span>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold text-gray-900 fs-6">
                                                    {{ __('settings.app_password_step_'.$step.'_title') }}
                                                </span>
                                                <span class="fs-7 text-gray-600">
                                                    {{ __('settings.app_password_step_'.$step.'_body') }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Intro video (plan-gated) --}}
                <div class="card mb-5 mb-xl-10">
                    <div class="card-header">
                        <div class="card-title">
                            <h3>{{ __('settings.intro_video_section_title') }}</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        @if (! $this->canUploadIntroVideo())
                            {{-- Visible rather than hidden: the owner should
                                 learn the feature exists and what unlocks it. --}}
                            <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-6">
                                <i class="ki-duotone ki-video fs-2tx text-primary me-4">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <div class="d-flex flex-column flex-grow-1">
                                    <div class="fw-semibold text-gray-800 mb-3">{{ __('settings.intro_video_upsell') }}</div>
                                    <div>
                                        <a href="{{ route('subscriptions') }}" class="btn btn-sm btn-primary">
                                            {{ __('settings.intro_video_upsell_cta') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @else
                            @php $currentVideo = $this->currentIntroVideo(); @endphp

                            @if ($currentVideo)
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-6">
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="ki-duotone ki-video fs-2 text-success">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <span class="fw-semibold text-gray-900">{{ $currentVideo->file_name }}</span>
                                    </div>
                                    <button type="button" class="btn btn-light-danger btn-sm"
                                            wire:click="removeIntroVideo"
                                            wire:loading.attr="disabled" wire:target="removeIntroVideo">
                                        {{ __('settings.intro_video_remove') }}
                                    </button>
                                </div>
                            @endif

                            <form wire:submit="saveIntroVideo">
                                <div class="fv-row mb-6">
                                    <label class="form-label">{{ __('settings.intro_video_label') }}</label>
                                    <div class="form-text mt-0 mb-4">{{ __('settings.intro_video_hint') }}</div>
                                    <input type="file" wire:model="introVideo"
                                           accept="video/mp4,video/webm,video/quicktime,video/x-msvideo,video/x-matroska"
                                           class="form-control form-control-solid @error('introVideo') is-invalid @enderror" />
                                    @error('introVideo')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-primary"
                                        wire:loading.attr="disabled" wire:target="saveIntroVideo">
                                    <span wire:loading.remove wire:target="saveIntroVideo">{{ __('settings.intro_video_save_button') }}</span>
                                    <span wire:loading wire:target="saveIntroVideo">{{ __('settings.intro_video_saving') }}</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Content generation --}}
                <div class="card mb-5 mb-xl-10">
                    <div class="card-header">
                        <div class="card-title">
                            <h3>{{ __('settings.content_section_title') }}</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="fv-row mb-7">
                            <label class="form-label required">{{ __('settings.content_language_label') }}</label>
                            <div class="form-text mt-0 mb-4">{{ __('settings.content_language_hint') }}</div>
                            <select wire:model="contentLanguage"
                                    class="form-select form-select-lg form-select-solid @error('contentLanguage') is-invalid @enderror">
                                @foreach ((array) config('laravellocalization.supportedLocales') as $code => $locale)
                                    <option value="{{ $code }}">{{ $locale['name'] }}</option>
                                @endforeach
                            </select>
                            @error('contentLanguage')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="fv-row">
                            <label class="form-label required">{{ __('settings.content_mode_label') }}</label>
                            <div class="form-text mt-0 mb-4">{{ __('settings.content_mode_hint') }}</div>
                            <div class="d-flex flex-wrap gap-8">
                                @foreach (ContentGenerationMode::cases() as $mode)
                                    <label class="form-check form-check-custom form-check-solid align-items-start">
                                        <input class="form-check-input mt-1" type="radio"
                                               wire:model="contentGenerationMode" value="{{ $mode->value }}"
                                               id="settings-content-mode-{{ $mode->value }}" />
                                        <span class="form-check-label d-flex flex-column ms-3">
                                            <span class="fw-bold text-gray-900">{{ $mode->getLabel() }}</span>
                                            <span class="text-muted fs-7">{{ __('settings.content_mode_'.$mode->value.'_hint') }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('contentGenerationMode')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end py-6">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save">{{ __('settings.save_button') }}</span>
                            <span wire:loading wire:target="save">{{ __('settings.saving') }}</span>
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
