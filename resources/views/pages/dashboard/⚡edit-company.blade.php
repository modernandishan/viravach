<?php

use App\Enums\CompanyReviewStatus;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\CompanyCategory;
use App\Models\State;
use App\Support\CompanySocialPlatforms;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
        $this->description = $company->getTranslations('description');
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

    public function updateCompany(): void
    {
        $this->normalizeWebsite();

        $this->validate([
            'name.'.config('app.fallback_locale') => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
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
            'description' => collect($this->description)->filter()->map(
                fn (string $html) => Str::sanitizeHtml($html)
            )->all(),
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
        </form>
    </div>
</div>
