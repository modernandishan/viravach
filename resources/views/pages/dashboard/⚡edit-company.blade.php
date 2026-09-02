<?php

use App\Enums\CompanyContentStatus;
use App\Events\Ai\ContentGenerationProgressed;
use App\Enums\CompanyReviewStatus;
use App\Jobs\Ai\GenerateSourceContent;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\CompanyCategory;
use App\Models\CompanyContent;
use App\Models\State;
use App\Services\Ai\ContentGenerationService;
use App\Settings\ContentSettings;
use App\Support\CompanySocialPlatforms;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravelcm\Subscriptions\Models\Subscription;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts::landing')]
class extends Component
{
    use WithFileUploads;

    public Company $record;

    /** @var array<string, string> */
    public array $name = [];

    public ?string $brief = null;

    /** @var array<int, int> */
    public array $categoryIds = [];

    /** @var array<int, array<string, mixed>> */
    public array $addresses = [];

    /**
     * JSON snapshot of the normalized address rows at mount, for detecting
     * whether the review status needs to reset on save.
     */
    public string $originalAddressesSnapshot = '';

    public $logo = null;

    public ?string $website = null;

    public ?string $email = null;

    /** @var array<int, string> */
    public array $phones = [];

    /** @var array<string, string> */
    public array $socialLinks = [];

    public function mount(Company $company): void
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $company->load(['categories', 'addresses']);

        $this->record = $company;
        $this->name = $company->getTranslations('name');
        $this->brief = $company->brief;
        $this->categoryIds = $company->categories->pluck('id')->all();
        // Stored values always carry the https:// scheme; the input shows
        // only the rest, since the form's input-group re-adds the prefix.
        $this->website = $company->website !== null ? Str::after($company->website, 'https://') : null;
        $this->email = $company->email;
        $this->phones = $company->phones ?? [];
        $this->socialLinks = CompanySocialPlatforms::toFormState($company->social_links);

        $this->addresses = $company->addresses->map(fn (CompanyAddress $address): array => [
            'id' => $address->id,
            'state_id' => $address->state_id,
            'city_id' => $address->city_id,
            'type' => $address->type,
            'address_line' => $address->getTranslation('address_line', app()->getLocale(), false),
            'postal_code' => $address->postal_code,
            'is_primary' => $address->is_primary,
        ])->values()->all();

        if ($this->addresses === []) {
            $this->addresses = [$this->emptyAddressRow(isPrimary: true)];
        }

        $this->originalAddressesSnapshot = json_encode($this->normalizedAddresses());
    }

    public function categoryTree(): Collection
    {
        return CompanyCategory::query()
            ->tree()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->toTree();
    }

    /**
     * Ancestor ids of the currently selected categories, so the picker
     * opens pre-expanded along the saved selection path. Eager loading the
     * recursive `ancestors` relationship keeps this at a single extra CTE
     * query.
     *
     * @return array<int, int>
     */
    public function expandedCategoryIds(): array
    {
        if ($this->categoryIds === []) {
            return [];
        }

        return CompanyCategory::query()
            ->with('ancestors')
            ->whereIn('id', $this->categoryIds)
            ->get()
            ->flatMap(fn (CompanyCategory $category) => $category->ancestors->pluck('id'))
            ->unique()
            ->values()
            ->all();
    }

    public function states(): Collection
    {
        return State::query()->active()->orderBy('id')->get();
    }

    /**
     * Cities of the states currently picked across address rows, keyed by
     * state_id, for the per-row city selects.
     */
    public function citiesByState(): Collection
    {
        $stateIds = collect($this->addresses)->pluck('state_id')->filter()->unique();

        if ($stateIds->isEmpty()) {
            return collect();
        }

        return City::query()
            ->active()
            ->whereIn('state_id', $stateIds)
            ->orderBy('id')
            ->get()
            ->groupBy('state_id');
    }

    public function existingLogoUrl(): ?string
    {
        return $this->record->hasMedia('logo') ? $this->record->getFirstMediaUrl('logo', 'webp') : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyAddressRow(bool $isPrimary = false): array
    {
        return [
            'id' => null,
            'state_id' => null,
            'city_id' => null,
            'type' => 'office',
            'address_line' => '',
            'postal_code' => null,
            'is_primary' => $isPrimary,
        ];
    }

    public function addAddressRow(): void
    {
        $this->addresses[] = $this->emptyAddressRow();
    }

    public function removeAddressRow(int $index): void
    {
        if (count($this->addresses) <= 1 || ! array_key_exists($index, $this->addresses)) {
            return;
        }

        $wasPrimary = (bool) ($this->addresses[$index]['is_primary'] ?? false);

        unset($this->addresses[$index]);
        $this->addresses = array_values($this->addresses);

        if ($wasPrimary) {
            $this->addresses[0]['is_primary'] = true;
        }
    }

    public function setPrimaryAddress(int $index): void
    {
        foreach ($this->addresses as $i => $row) {
            $this->addresses[$i]['is_primary'] = $i === $index;
        }
    }

    public function updatedAddresses(mixed $value, ?string $key = null): void
    {
        // Changing a row's state invalidates its city selection.
        if ($key !== null && str_ends_with($key, '.state_id')) {
            $index = (int) explode('.', $key)[0];
            $this->addresses[$index]['state_id'] = ($value !== '' && $value !== null) ? (int) $value : null;
            $this->addresses[$index]['city_id'] = null;
        }
    }

    /**
     * The address rows with consistent value types, so a snapshot taken at
     * mount compares cleanly against rows round-tripped through the browser
     * (where selects submit strings).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function normalizedAddresses(): array
    {
        return array_values(array_map(fn (array $row): array => [
            'id' => ($row['id'] ?? null) !== null && $row['id'] !== '' ? (int) $row['id'] : null,
            'state_id' => ($row['state_id'] ?? null) !== null && $row['state_id'] !== '' ? (int) $row['state_id'] : null,
            'city_id' => ($row['city_id'] ?? null) !== null && $row['city_id'] !== '' ? (int) $row['city_id'] : null,
            'type' => (string) ($row['type'] ?? 'office'),
            'address_line' => (string) ($row['address_line'] ?? ''),
            'postal_code' => ($row['postal_code'] ?? null) !== null && $row['postal_code'] !== '' ? (string) $row['postal_code'] : null,
            'is_primary' => (bool) ($row['is_primary'] ?? false),
        ], $this->addresses));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'categoryIds.required' => __('companies.validation_category_required'),
            'categoryIds.min' => __('companies.validation_category_required'),
            'categoryIds.max' => __('companies.validation_category_max'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'name.*' => __('companies.field_name'),
            'brief' => __('companies.field_description'),
            'categoryIds' => __('companies.field_category'),
            'categoryIds.*' => __('companies.field_category'),
            'addresses' => __('companies.wizard_step_addresses'),
            'addresses.*.state_id' => __('companies.field_state'),
            'addresses.*.city_id' => __('companies.field_city'),
            'addresses.*.type' => __('companies.field_address_type'),
            'addresses.*.address_line' => __('companies.field_address_line'),
            'addresses.*.postal_code' => __('companies.field_postal_code'),
            'logo' => __('companies.field_logo'),
            'website' => __('companies.field_website'),
            'email' => __('companies.field_email'),
            'phones' => __('companies.field_phones'),
            'phones.*' => __('companies.field_phones'),
            'socialLinks.telegram' => __('companies.field_social_telegram'),
            'socialLinks.whatsapp' => __('companies.field_social_whatsapp'),
            'socialLinks.instagram' => __('companies.field_social_instagram'),
            'socialLinks.youtube' => __('companies.field_social_youtube'),
            'socialLinks.x' => __('companies.field_social_x'),
            'socialLinks.website1' => __('companies.field_social_website_1'),
            'socialLinks.website2' => __('companies.field_social_website_2'),
            'socialLinks.website3' => __('companies.field_social_website_3'),
        ];
    }

    /**
     * The website input holds only the domain part: any leading scheme or
     * protocol-relative slashes the user pasted is stripped before the
     * https:// prefix is prepended, so the stored value is always a full
     * URL (null when empty).
     */
    protected function normalizeWebsite(): void
    {
        $website = preg_replace('#^(https?://|//)#i', '', trim((string) ($this->website ?? ''))) ?? '';

        $this->website = $website !== '' ? 'https://'.$website : null;
    }

    /**
     * The AI content generation card state. Every branch is derived from
     * the CompanyContent row + the plan feature, never cached between
     * requests, so the UI always reflects the pipeline's real state.
     */
    public function contentIsProcessing(): bool
    {
        return $this->record->contentRecord?->status->isProcessing() ?? false;
    }

    public function contentStatus(): ?CompanyContentStatus
    {
        return $this->record->contentRecord?->status;
    }

    /**
     * The once-per-company generation lock — separate from and on top of
     * the monthly plan quota below. Once true, the user has no path to
     * trigger another generation; only the admin's
     * allow_content_regeneration action can lift it.
     */
    public function contentAlreadyGenerated(): bool
    {
        return app(ContentGenerationService::class)->alreadyGenerated($this->record);
    }

    public function contentStepLabel(int $step): string
    {
        return __('companies.ai_step_'.max(1, min(5, $step)));
    }

    private function contentSubscription(): ?Subscription
    {
        return $this->record->activeSubscription();
    }

    /**
     * Feature slugs are prefixed with their plan slug in the seeder
     * (see PlanSeeder::seedFeatures), so lookups must be too.
     */
    private function contentFeatureSlug(): ?string
    {
        $plan = $this->contentSubscription()?->plan;

        return $plan !== null ? $plan->slug.'-ai-content-generations' : null;
    }

    /**
     * Remaining generations this month; null means unlimited. Zero when the
     * plan has no usable feature or the quota is burnt.
     */
    public function contentQuotaRemaining(): ?int
    {
        $subscription = $this->contentSubscription();
        $slug = $this->contentFeatureSlug();

        if ($subscription === null || $slug === null || ! $subscription->canUseFeature($slug)) {
            return 0;
        }

        $remaining = $subscription->getFeatureRemainings($slug);

        return is_numeric($remaining) ? (int) $remaining : null;
    }

    public function requestContentGeneration(): void
    {
        // Per-company rate limit, independent of the plan quota: even with
        // quota left, a company cannot hammer the pipeline.
        $rateLimitKey = 'ai-content:'.$this->record->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            // The window is 6 hours, so the visitor has to be told how long —
            // "try again later" with no number reads as a dead button.
            $this->notifyContent(__('companies.content_rate_limited', [
                'minutes' => (int) ceil(RateLimiter::availableIn($rateLimitKey) / 60),
            ]));

            return;
        }

        // Server-side gate for the once-per-company lock: the UI never
        // renders a button that reaches here once this is true, but a
        // stale page (or a direct wire:click) must still be refused.
        if ($this->contentAlreadyGenerated()) {
            $this->notifyContent(__('companies.ai_regeneration_not_allowed'));

            return;
        }

        $subscription = $this->contentSubscription();
        $featureSlug = $this->contentFeatureSlug();

        if ($subscription === null || $featureSlug === null || ! $subscription->canUseFeature($featureSlug)) {
            $this->notifyContent(__('companies.content_quota_exhausted'));

            return;
        }

        RateLimiter::hit($rateLimitKey, 6 * 3600);

        if (app(ContentGenerationService::class)->request($this->record)) {
            // Quota is burnt ONLY here: rejections (disabled, already
            // running, unchanged input) must never consume a generation.
            $subscription->recordFeatureUsage($featureSlug);

            $this->notifyContent(__('companies.content_generation_queued'));

            return;
        }

        $this->notifyContent(app(ContentSettings::class)->enabled
            ? ($this->contentIsProcessing()
                ? __('companies.content_already_running')
                : __('companies.content_unchanged'))
            : __('companies.content_disabled'));
    }

    /**
     * Feedback for the AI-content actions, rendered on THIS page.
     *
     * It used to be session()->flash('company-status'), which is invisible
     * here: a wire:click re-renders inside the same request, so flashed data
     * — written for the *next* request — never appears. Only
     * ⚡my-companies reads that key, and only line 538's save reaches it,
     * because a redirect follows there. Every rejection from
     * requestContentGeneration() was therefore silent: the button looked
     * dead, and the message later surfaced out of context on another page.
     * A public property renders immediately in the same round-trip.
     */
    public ?string $contentMessage = null;

    private function notifyContent(string $message): void
    {
        $this->contentMessage = $message;
    }

    /**
     * Echo listeners — same wiring as the chat pages (chat.blade.php):
     * registered here because the channel name embeds the company id.
     * ContentGenerationProgressed carries status/step; terminal statuses
     * re-enable the form automatically because the disabled fieldset keys
     * off isProcessing(), and the ready state renders the generated text
     * straight from the reloaded contentRecord — no manual refresh.
     */
    public function getListeners(): array
    {
        return [
            'echo-private:'.ContentGenerationProgressed::channelNameFor($this->record->id).',ContentGenerationProgressed' => 'onContentProgress',
        ];
    }

    public function onContentProgress(array $payload = []): void
    {
        $this->refreshContentState();
    }

    /**
     * Fallback for a dropped socket: the card polls only while the run is
     * live, so the poll stops on its own once the status is terminal.
     */
    public function refreshContentState(): void
    {
        $this->record->loadMissing('contentRecord');
    }

    public function updateCompany(): void
    {
        // Server-side lock: the disabled form is a courtesy, this is the
        // actual gate. 409 while a generation run owns the row.
        if ($this->contentIsProcessing()) {
            abort(409, __('companies.content_locked'));
        }

        $this->normalizeWebsite();

        $this->validate([
            'name.'.config('app.fallback_locale') => ['required', 'string', 'max:255'],
            'brief' => ['required', 'string', 'min:100', 'max:5000'],
            'categoryIds' => ['required', 'array', 'min:1', 'max:5'],
            'categoryIds.*' => ['integer', 'exists:company_categories,id'],
            'addresses' => ['required', 'array', 'min:1'],
            'addresses.*.id' => ['nullable', 'integer', Rule::exists('company_addresses', 'id')->where('company_id', $this->record->id)],
            'addresses.*.state_id' => ['required', 'exists:states,id'],
            'addresses.*.city_id' => ['nullable', 'exists:cities,id'],
            'addresses.*.type' => ['required', 'in:office,warehouse,factory,showroom'],
            'addresses.*.address_line' => ['required', 'string', 'max:500'],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:10'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phones' => ['nullable', 'array'],
            'phones.*' => ['string', 'max:32'],
            'socialLinks.telegram' => ['nullable', 'string', 'max:255'],
            'socialLinks.whatsapp' => ['nullable', 'string', 'max:255'],
            'socialLinks.instagram' => ['nullable', 'string', 'max:255'],
            'socialLinks.youtube' => ['nullable', 'string', 'max:255'],
            'socialLinks.x' => ['nullable', 'string', 'max:255'],
            'socialLinks.website1' => ['nullable', 'url', 'max:255'],
            'socialLinks.website2' => ['nullable', 'url', 'max:255'],
            'socialLinks.website3' => ['nullable', 'url', 'max:255'],
        ]);

        $this->record->fill([
            'name' => $this->name,
            'brief' => $this->brief,
            'brief_locale' => app()->getLocale(),
            'website' => $this->website ?: null,
            'email' => $this->email ?: null,
            'phones' => $this->phones !== [] ? array_values($this->phones) : null,
            'social_links' => CompanySocialPlatforms::toStoredLinks($this->socialLinks),
        ]);

        $reviewedFieldsChanged = $this->record->isDirty(Company::REVIEWED_ATTRIBUTES);

        $this->record->save();

        $categoryChanges = $this->record->categories()->sync($this->categoryIds);
        $categoriesChanged = collect($categoryChanges)->flatten()->isNotEmpty();

        $rows = $this->addressRowsWithSinglePrimary();

        $addressesChanged = json_encode($rows) !== $this->originalAddressesSnapshot;

        $keptIds = collect($rows)->pluck('id')->filter()->all();
        $this->record->addresses()->whereNotIn('id', $keptIds)->delete();

        $states = State::query()->findMany(collect($rows)->pluck('state_id'))->keyBy('id');

        foreach ($rows as $row) {
            $state = $states[$row['state_id']];

            $this->record->addresses()->updateOrCreate(
                ['id' => $row['id']],
                [
                    'country_id' => $state->country_id,
                    'state_id' => $state->id,
                    'city_id' => $row['city_id'],
                    'type' => $row['type'],
                    'address_line' => [app()->getLocale() => $row['address_line']],
                    'postal_code' => $row['postal_code'],
                    'is_primary' => $row['is_primary'],
                ],
            );
        }

        $mediaChanged = $this->logo !== null;

        if ($this->logo) {
            $this->record->clearMediaCollection('logo');
            $this->record->addMedia($this->logo->getRealPath())
                ->usingFileName($this->logo->getClientOriginalName())
                ->toMediaCollection('logo', 's3');
            $this->logo = null;
        }

        // Any reviewed change sends the draft back into the review queue.
        // The public publication snapshot is deliberately left untouched:
        // it keeps serving the last approved version.
        if ($reviewedFieldsChanged || $categoriesChanged || $addressesChanged || $mediaChanged) {
            $this->record->update([
                'review_status' => CompanyReviewStatus::PendingReview,
            ]);
        }

        session()->flash('company-status', __('companies.updated_successfully'));

        $this->redirect(route('my-companies'), navigate: false);
    }

    /**
     * The normalized rows with is_primary flags fixed so exactly one row is
     * primary (the first flagged one, or the first row when none is
     * flagged).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function addressRowsWithSinglePrimary(): array
    {
        $rows = $this->normalizedAddresses();

        $primaryIndex = null;

        foreach ($rows as $index => $row) {
            if ($row['is_primary']) {
                $primaryIndex = $index;
                break;
            }
        }

        $primaryIndex ??= 0;

        foreach ($rows as $index => $row) {
            $rows[$index]['is_primary'] = $index === $primaryIndex;
        }

        return $rows;
    }

    public function render()
    {
        return $this->view()->title(__('companies.edit_page_title').' | '.__('auth.user-dashboard').' | '.__('globals.viravach'));
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">
        <livewire:dashboard-elements.infobar/>

        @php
            $content = $this->record->contentRecord;
            $contentStatus = $this->contentStatus();
            $contentQuota = $this->contentQuotaRemaining();
        @endphp

        {{-- AI content generation: trigger, lock, and live progress.
             wire:poll is a FALLBACK for a dropped Echo socket and renders
             only while the run is live — it disappears on terminal status. --}}
        <div class="card mb-5 mb-xl-10" @if ($contentStatus?->isProcessing()) wire:poll.10s="refreshContentState" @endif>
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <h2>{{ __('companies.content_card_title') }}</h2>
                </div>
            </div>
            <div class="card-body border-top p-9">
                {{-- Result of the last AI-content action. Rendered from the
                     component property, not session()->flash(), so it is
                     visible in the same Livewire round-trip as the click. --}}
                @if ($contentMessage)
                    <div class="alert alert-primary d-flex align-items-center mb-6">
                        <i class="ki-duotone ki-information-5 fs-2 me-3">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                        <span>{{ $contentMessage }}</span>
                    </div>
                @endif

                @if ($contentStatus?->isProcessing())
                    <div class="d-flex flex-column gap-3">
                        <div class="fw-semibold text-gray-700">
                            {{ __('companies.content_step_of', ['step' => $content->step, 'label' => $this->contentStepLabel($content->step)]) }}
                        </div>
                        <div class="progress h-8px bg-light w-100">
                            <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                                 role="progressbar"
                                 style="width: {{ max(10, (int) ($content->step / 5 * 100)) }}%"></div>
                        </div>
                        <div class="text-muted fs-7">{{ __('companies.content_processing_hint') }}</div>
                    </div>
                @elseif ($this->contentAlreadyGenerated())
                    <div class="d-flex flex-column gap-3">
                        <div class="text-gray-700">
                            {{ __('companies.content_ready_at', ['date' => \App\Support\LocalizedDate::format($content->updated_at, \App\Support\LocalizedDate::FORMAT_DATETIME)]) }}
                        </div>
                        <div class="text-muted">{{ __('companies.ai_already_generated') }}</div>
                    </div>
                @elseif ($contentStatus === \App\Enums\CompanyContentStatus::Failed)
                    <div class="d-flex flex-column gap-3">
                        <div class="text-danger fw-bold">{{ __('companies.content_failed_title') }}</div>
                        {{-- A translated, user-facing explanation — never
                             $content->failure_reason, which holds the raw
                             internal exception text ("The AI gateway request
                             failed after 3 attempt(s): HTTP 400."). That is
                             untranslated English, leaks infrastructure detail
                             to business owners, and tells them nothing they can
                             act on. The raw reason stays in the database and is
                             shown to administrators in the Filament panel and
                             the logs, where it is actually useful. --}}
                        <div class="text-gray-700">{{ __('companies.content_failed_body') }}</div>
                        <div>
                            <button type="button" wire:click="requestContentGeneration" class="btn btn-light-primary">
                                {{ __('companies.content_retry') }}
                            </button>
                        </div>
                    </div>
                @elseif ($contentQuota === 0)
                    <div class="d-flex flex-column gap-3">
                        <div class="text-gray-700">{{ __('companies.content_quota_exhausted') }}</div>
                        <div>
                            <a href="{{ route('subscriptions') }}" class="btn btn-light-primary">
                                {{ __('companies.content_upgrade_plans') }}
                            </a>
                        </div>
                    </div>
                @else
                    <div class="text-gray-700 mb-3">{{ __('companies.content_generate_hint') }}</div>
                    <button type="button" wire:click="requestContentGeneration" class="btn btn-primary">
                        {{ __('companies.content_generate') }}
                    </button>
                @endif
            </div>
        </div>

        @if (! empty($this->record->content))
            <livewire:company-content.content-editor :company="$this->record" />
        @endif

        <form wire:submit.prevent="updateCompany">
        <fieldset {{ $contentStatus?->isProcessing() ? 'disabled' : '' }}>
            <div class="card mb-5 mb-xl-10">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>{{ __('companies.wizard_step_basic_info') }}</h2>
                    </div>
                </div>
                <div class="card-body border-top p-9">
                    <x-company-elements.basic-info-fields />
                </div>
            </div>

            <div class="card mb-5 mb-xl-10">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>{{ __('companies.wizard_step_category') }}</h2>
                    </div>
                </div>
                <div class="card-body border-top p-9">
                    <div class="text-muted fw-semibold fs-6 mb-5">{{ __('companies.field_category_hint') }}</div>
                    <div class="fv-row @error('categoryIds') is-invalid @enderror">
                        <x-company-elements.category-tree-select :nodes="$this->categoryTree()" :expanded-ids="$this->expandedCategoryIds()" :selected-ids="$categoryIds" />
                    </div>
                    @error('categoryIds')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="card mb-5 mb-xl-10">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>{{ __('companies.wizard_step_addresses') }}</h2>
                    </div>
                </div>
                <div class="card-body border-top p-9">
                    <x-company-elements.address-fields :addresses="$addresses" :states="$this->states()" :cities-by-state="$this->citiesByState()" />
                </div>
            </div>

            <div class="card mb-5 mb-xl-10">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>{{ __('companies.wizard_step_media') }}</h2>
                    </div>
                </div>
                <div class="card-body border-top p-9">
                    <x-company-elements.media-fields :logo="$logo" :existing-logo-url="$this->existingLogoUrl()" />
                </div>
            </div>

            <div class="card mb-5 mb-xl-10">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>{{ __('companies.wizard_step_contact') }}</h2>
                    </div>
                </div>
                <div class="card-body border-top p-9">
                    <x-company-elements.contact-fields />
                </div>
            </div>

            <div class="d-flex justify-content-end mb-10">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                    {{ __('companies.button_update') }}
                </button>
            </div>
        </fieldset>
        </form>
    </div>
</div>
