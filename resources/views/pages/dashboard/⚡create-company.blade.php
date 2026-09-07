<?php

use App\Enums\CompanyReviewStatus;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanyCategory;
use App\Models\State;
use App\Services\CompanySubscriptionService;
use App\Support\CompanySocialPlatforms;
use App\Support\LocalizedDate;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts::landing')]
class extends Component
{
    use WithFileUploads;

    public int $step = 1;

    public string $name = '';

    public ?string $brief = null;

    /** @var array<int, int> */
    public array $categoryIds = [];

    /** @var array<int, array<string, mixed>> */
    public array $addresses = [];

    public $logo = null;

    public ?string $website = null;

    public ?string $email = null;

    /** @var array<int, string> */
    public array $phones = [];

    /** @var array<string, string> */
    public array $socialLinks = [];

    public function mount(): void
    {
        $this->addresses = [$this->emptyAddressRow(isPrimary: true)];
        $this->socialLinks = CompanySocialPlatforms::emptyState();
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
     * re-opens along the selection path. Eager loading the recursive
     * `ancestors` relationship keeps this at a single extra CTE query.
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

    /**
     * @return array<string, mixed>
     */
    protected function emptyAddressRow(bool $isPrimary = false): array
    {
        return [
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
     * @return array<string, mixed>
     */
    protected function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'name' => ['required', 'string', 'max:255'],
                'brief' => ['required', 'string', 'min:100', 'max:5000'],
            ],
            2 => [
                'categoryIds' => ['required', 'array', 'min:1', 'max:5'],
                'categoryIds.*' => ['integer', 'exists:company_categories,id'],
            ],
            3 => [
                'addresses' => ['required', 'array', 'min:1'],
                'addresses.*.state_id' => ['required', 'exists:states,id'],
                'addresses.*.city_id' => ['nullable', 'exists:cities,id'],
                'addresses.*.type' => ['required', 'in:office,warehouse,factory,showroom'],
                'addresses.*.address_line' => ['required', 'string', 'max:500'],
                'addresses.*.postal_code' => ['nullable', 'string', 'max:10'],
            ],
            4 => [
                'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            ],
            5 => [
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
            ],
            default => [],
        };
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
            'name' => __('companies.field_name'),
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

    public function nextStep(): void
    {
        $this->validate($this->rulesForStep($this->step));

        $this->step = min($this->step + 1, 5);
    }

    public function previousStep(): void
    {
        $this->step = max($this->step - 1, 1);
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
     * ONE COMPANY PER USER EVERY 48 HOURS: this rule is the public
     * dashboard's alone — Filament company creation is exempt entirely,
     * and super_admin/admin users are exempt here too.
     */
    public function companyCreationCooldownEndsAt(): ?CarbonInterface
    {
        if (auth()->user()->hasAnyRole(['super_admin', 'admin'])) {
            return null;
        }

        return Company::creationCooldownEndsAt(auth()->id());
    }

    public function createCompany(): void
    {
        if (($cooldownEndsAt = $this->companyCreationCooldownEndsAt()) !== null) {
            $this->addError('cooldown', __('companies.company_creation_cooldown', [
                'time' => LocalizedDate::format($cooldownEndsAt, LocalizedDate::FORMAT_DATETIME),
            ]));

            return;
        }

        $this->normalizeWebsite();

        $this->validate($this->rulesForStep(5));

        $company = Company::create([
            'user_id' => auth()->id(),
            // slug comes from the model's HasTranslatableSlug trait —
            // Str::slug() here stripped Persian names into empty slugs.
            'name' => ['fa' => $this->name],
            'brief' => $this->brief,
            'brief_locale' => app()->getLocale(),
            'website' => $this->website ?: null,
            'email' => $this->email ?: null,
            'phones' => $this->phones !== [] ? array_values($this->phones) : null,
            'social_links' => CompanySocialPlatforms::toStoredLinks($this->socialLinks),
            'review_status' => CompanyReviewStatus::PendingReview,
        ]);

        $company->categories()->sync($this->categoryIds);

        // Country is never picked directly; each row derives it from the
        // chosen state.
        $rows = $this->addressesWithSinglePrimary();
        $states = State::query()->findMany(collect($rows)->pluck('state_id'))->keyBy('id');

        foreach ($rows as $row) {
            $state = $states[(int) $row['state_id']];

            $company->addresses()->create([
                'country_id' => $state->country_id,
                'state_id' => $state->id,
                'city_id' => ($row['city_id'] ?? null) ? (int) $row['city_id'] : null,
                'type' => $row['type'],
                'address_line' => [app()->getLocale() => $row['address_line']],
                'postal_code' => ($row['postal_code'] ?? '') !== '' && $row['postal_code'] !== null ? $row['postal_code'] : null,
                'is_primary' => $row['is_primary'],
            ]);
        }

        if ($this->logo) {
            $company->addMedia($this->logo->getRealPath())
                ->usingFileName($this->logo->getClientOriginalName())
                ->toMediaCollection('logo', 's3');
        }

        app(CompanySubscriptionService::class)->assignFreePlanIfMissing($company);

        session()->flash('company-status', __('companies.created_successfully'));

        $this->redirect(route('my-companies'), navigate: false);
    }

    /**
     * The submitted rows with the is_primary flags normalized so exactly
     * one row is primary (the first flagged one, or the first row when
     * none is flagged).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function addressesWithSinglePrimary(): array
    {
        $rows = array_values($this->addresses);

        $primaryIndex = null;

        foreach ($rows as $index => $row) {
            if (! empty($row['is_primary'])) {
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
        return $this->view()->title(__('companies.create_page_title').' | '.__('auth.user-dashboard').' | '.__('globals.viravach'));
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">
        <livewire:dashboard-elements.infobar/>

        @include('partials.flash-alerts', ['errorKeys' => ['cooldown']])

        <div class="content flex-row-fluid" id="kt_content">
            <!--begin::Stepper-->
            <div class="stepper stepper-pills stepper-column d-flex flex-column flex-xl-row flex-row-fluid gap-10" id="kt_create_company_stepper">
                <!--begin::کناری-->
                <div class="card d-flex justify-content-center justify-content-xl-start flex-row-auto w-100 w-xl-300px w-xxl-400px">
                    <div class="card-body px-6 px-lg-10 px-xxl-15 py-20">
                        <div class="stepper-nav">
                            @foreach ([1 => 'wizard_step_basic_info', 2 => 'wizard_step_category', 3 => 'wizard_step_addresses', 4 => 'wizard_step_media', 5 => 'wizard_step_contact'] as $number => $labelKey)
                                <div class="stepper-item {{ $step === $number ? 'current' : ($step > $number ? 'completed' : '') }}">
                                    <div class="stepper-wrapper">
                                        <div class="stepper-icon w-40px h-40px">
                                            <i class="ki-duotone ki-check fs-2 stepper-check"></i>
                                            <span class="stepper-number">{{ $number }}</span>
                                        </div>
                                        <div class="stepper-label">
                                            <h3 class="stepper-title">{{ __('companies.'.$labelKey) }}</h3>
                                        </div>
                                    </div>
                                    @if ($number < 5)
                                        <div class="stepper-line h-40px"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <!--end::کناری-->
                <!--begin::Content-->
                <div class="card d-flex flex-row-fluid flex-center">
                    <form class="card-body py-20 w-100 mw-xl-700px px-9" wire:submit.prevent="{{ $step === 5 ? 'createCompany' : 'nextStep' }}">
                        @if ($step === 1)
                            <div class="w-100">
                                <div class="pb-10 pb-lg-15">
                                    <h2 class="fw-bold text-gray-900">{{ __('companies.wizard_step_basic_info') }}</h2>
                                </div>
                                <x-company-elements.basic-info-fields single-locale />
                            </div>
                        @elseif ($step === 2)
                            <div class="w-100">
                                <div class="pb-10 pb-lg-15">
                                    <h2 class="fw-bold text-gray-900">{{ __('companies.wizard_step_category') }}</h2>
                                    <div class="text-muted fw-semibold fs-6">{{ __('companies.field_category_hint') }}</div>
                                </div>
                                <div class="fv-row @error('categoryIds') is-invalid @enderror">
                                    <x-company-elements.category-tree-select :nodes="$this->categoryTree()" :expanded-ids="$this->expandedCategoryIds()" :selected-ids="$categoryIds" />
                                </div>
                                @error('categoryIds')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        @elseif ($step === 3)
                            <div class="w-100">
                                <div class="pb-10 pb-lg-15">
                                    <h2 class="fw-bold text-gray-900">{{ __('companies.wizard_step_addresses') }}</h2>
                                </div>
                                <x-company-elements.address-fields :addresses="$addresses" :states="$this->states()" :cities-by-state="$this->citiesByState()" />
                            </div>
                        @elseif ($step === 4)
                            <div class="w-100">
                                <div class="pb-10 pb-lg-15">
                                    <h2 class="fw-bold text-gray-900">{{ __('companies.wizard_step_media') }}</h2>
                                </div>
                                <x-company-elements.media-fields :logo="$logo" />
                            </div>
                        @elseif ($step === 5)
                            <div class="w-100">
                                <div class="pb-10 pb-lg-15">
                                    <h2 class="fw-bold text-gray-900">{{ __('companies.wizard_step_contact') }}</h2>
                                </div>
                                <x-company-elements.contact-fields />
                            </div>
                        @endif

                        <div class="d-flex flex-stack pt-10">
                            <div>
                                @if ($step > 1)
                                    <button type="button" class="btn btn-lg btn-light-primary me-3" wire:click="previousStep">
                                        {{ __('companies.button_back') }}
                                    </button>
                                @endif
                            </div>
                            <div>
                                @if ($step < 5)
                                    <button type="button" class="btn btn-lg btn-primary" wire:click="nextStep">
                                        {{ __('companies.button_next') }}
                                    </button>
                                @else
                                    <button type="button" class="btn btn-lg btn-primary" wire:click="createCompany" wire:loading.attr="disabled">
                                        {{ __('companies.button_submit') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
                <!--end::Content-->
            </div>
            <!--end::Stepper-->
        </div>
    </div>
</div>
