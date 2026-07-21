<?php

use App\Enums\CompanyReviewStatus;
use App\Models\Company;
use App\Models\CompanyCategory;
use App\Models\State;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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

    /** @var array<string, string> */
    public array $description = [];

    /** @var array<int, int> */
    public array $categoryIds = [];

    public ?int $stateId = null;

    public $logo = null;

    /** @var array<int, mixed> */
    public array $gallery = [];

    public ?string $website = null;

    public ?string $email = null;

    public ?string $phones = null;

    public ?string $socialInstagram = null;

    public ?string $socialTelegram = null;

    public ?string $socialLinkedin = null;

    public ?string $socialWebsite = null;

    public function mount(Company $company): void
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $company->load(['categories', 'primaryAddress']);

        $this->record = $company;
        $this->name = $company->getTranslations('name');
        $this->description = $company->getTranslations('description');
        $this->categoryIds = $company->categories->pluck('id')->all();
        $this->stateId = $company->primaryAddress?->state_id;
        $this->website = $company->website;
        $this->email = $company->email;
        $this->phones = $company->phones ? implode(', ', $company->phones) : null;

        $socialLinks = $company->social_links ?? [];
        $this->socialInstagram = $socialLinks['instagram'] ?? null;
        $this->socialTelegram = $socialLinks['telegram'] ?? null;
        $this->socialLinkedin = $socialLinks['linkedin'] ?? null;
        $this->socialWebsite = $socialLinks['website'] ?? null;
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

    public function existingLogoUrl(): ?string
    {
        return $this->record->hasMedia('logo') ? $this->record->getFirstMediaUrl('logo', 'webp') : null;
    }

    /**
     * @return array<int, string>
     */
    public function existingGalleryUrls(): array
    {
        return $this->record->getMedia('gallery')->map(fn ($media) => $media->getUrl('webp'))->all();
    }

    public function updateCompany(): void
    {
        $this->validate([
            'name.'.config('app.fallback_locale') => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'categoryIds' => ['required', 'array', 'min:1'],
            'categoryIds.*' => ['integer', 'exists:company_categories,id'],
            'stateId' => ['required', 'exists:states,id'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phones' => ['nullable', 'string', 'max:255'],
            'socialInstagram' => ['nullable', 'url', 'max:255'],
            'socialTelegram' => ['nullable', 'url', 'max:255'],
            'socialLinkedin' => ['nullable', 'url', 'max:255'],
            'socialWebsite' => ['nullable', 'url', 'max:255'],
        ]);

        $this->record->fill([
            'name' => $this->name,
            'description' => collect($this->description)->filter()->map(
                fn (string $html) => Str::sanitizeHtml($html)
            )->all(),
            'website' => $this->website ?: null,
            'email' => $this->email ?: null,
            'phones' => $this->phones ? array_values(array_filter(array_map('trim', explode(',', $this->phones)))) : null,
            'social_links' => array_filter([
                'instagram' => $this->socialInstagram ?: null,
                'telegram' => $this->socialTelegram ?: null,
                'linkedin' => $this->socialLinkedin ?: null,
                'website' => $this->socialWebsite ?: null,
            ]) ?: null,
        ]);

        $reviewedFieldsChanged = $this->record->isDirty(Company::REVIEWED_ATTRIBUTES);

        $this->record->save();

        $categoryChanges = $this->record->categories()->sync($this->categoryIds);
        $categoriesChanged = collect($categoryChanges)->flatten()->isNotEmpty();

        $state = State::findOrFail($this->stateId);
        $primary = $this->record->primaryAddress;
        $stateChanged = $primary?->state_id !== $state->id;

        if ($primary) {
            $primary->update(['country_id' => $state->country_id, 'state_id' => $state->id]);
        } else {
            $this->record->addresses()->create([
                'country_id' => $state->country_id,
                'state_id' => $state->id,
                'type' => 'office',
                'address_line' => [app()->getLocale() => ''],
                'is_primary' => true,
            ]);
        }

        $mediaChanged = $this->logo !== null || $this->gallery !== [];

        if ($this->logo) {
            $this->record->clearMediaCollection('logo');
            $this->record->addMedia($this->logo->getRealPath())
                ->usingFileName($this->logo->getClientOriginalName())
                ->toMediaCollection('logo', 's3');
            $this->logo = null;
        }

        foreach ($this->gallery as $image) {
            $this->record->addMedia($image->getRealPath())
                ->usingFileName($image->getClientOriginalName())
                ->toMediaCollection('gallery', 's3');
        }
        $this->gallery = [];

        // Any reviewed change sends the draft back into the review queue.
        // The public publication snapshot is deliberately left untouched:
        // it keeps serving the last approved version.
        if ($reviewedFieldsChanged || $categoriesChanged || $stateChanged || $mediaChanged) {
            $this->record->update([
                'review_status' => CompanyReviewStatus::PendingReview,
                'rejection_reason' => null,
            ]);
        }

        session()->flash('company-status', __('companies.updated_successfully'));

        $this->redirect(route('my-companies'), navigate: false);
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

        <form wire:submit.prevent="updateCompany">
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
                    <div class="fv-row @error('categoryIds') is-invalid @enderror">
                        <x-company-elements.category-tree-select :nodes="$this->categoryTree()" :expanded-ids="$this->expandedCategoryIds()" />
                    </div>
                    @error('categoryIds')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="card mb-5 mb-xl-10">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>{{ __('companies.wizard_step_state') }}</h2>
                    </div>
                </div>
                <div class="card-body border-top p-9">
                    <x-company-elements.state-select :states="$this->states()" />
                </div>
            </div>

            <div class="card mb-5 mb-xl-10">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>{{ __('companies.wizard_step_media') }}</h2>
                    </div>
                </div>
                <div class="card-body border-top p-9">
                    <x-company-elements.media-fields :logo="$logo" :gallery="$gallery"
                                                       :existing-logo-url="$this->existingLogoUrl()"
                                                       :existing-gallery-urls="$this->existingGalleryUrls()" />
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
        </form>
    </div>
</div>
