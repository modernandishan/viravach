<?php

use App\Enums\CompanyReviewStatus;
use App\Models\Company;
use App\Models\CompanyCategory;
use App\Models\State;
use App\Services\CompanySubscriptionService;
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

    public int $step = 1;

    public string $name = '';

    public string $description = '';

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
     * @return array<string, mixed>
     */
    protected function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
            ],
            2 => [
                'categoryIds' => ['required', 'array', 'min:1'],
                'categoryIds.*' => ['integer', 'exists:company_categories,id'],
            ],
            3 => [
                'stateId' => ['required', 'exists:states,id'],
            ],
            4 => [
                'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
                'gallery' => ['nullable', 'array'],
                'gallery.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            ],
            5 => [
                'website' => ['nullable', 'url', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'phones' => ['nullable', 'string', 'max:255'],
                'socialInstagram' => ['nullable', 'url', 'max:255'],
                'socialTelegram' => ['nullable', 'url', 'max:255'],
                'socialLinkedin' => ['nullable', 'url', 'max:255'],
                'socialWebsite' => ['nullable', 'url', 'max:255'],
            ],
            default => [],
        };
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

    public function createCompany(): void
    {
        $this->validate($this->rulesForStep(5));

        $company = Company::create([
            'user_id' => auth()->id(),
            'slug' => Str::slug($this->name).'-'.Str::lower(Str::random(6)),
            'name' => ['fa' => $this->name],
            'description' => $this->description !== '' ? ['fa' => Str::sanitizeHtml($this->description)] : [],
            'website' => $this->website ?: null,
            'email' => $this->email ?: null,
            'phones' => $this->phones ? array_values(array_filter(array_map('trim', explode(',', $this->phones)))) : null,
            'social_links' => array_filter([
                'instagram' => $this->socialInstagram ?: null,
                'telegram' => $this->socialTelegram ?: null,
                'linkedin' => $this->socialLinkedin ?: null,
                'website' => $this->socialWebsite ?: null,
            ]) ?: null,
            'review_status' => CompanyReviewStatus::PendingReview,
        ]);

        $company->categories()->sync($this->categoryIds);

        $state = State::findOrFail($this->stateId);

        // The wizard's "state" step only collects a state (country is
        // derived from it); a full postal address is out of scope here and
        // can be filled in later from the edit page, but address_line is a
        // required json column so it needs a value for the current locale.
        $company->addresses()->create([
            'country_id' => $state->country_id,
            'state_id' => $state->id,
            'type' => 'office',
            'address_line' => [app()->getLocale() => ''],
            'is_primary' => true,
        ]);

        if ($this->logo) {
            $company->addMedia($this->logo->getRealPath())
                ->usingFileName($this->logo->getClientOriginalName())
                ->toMediaCollection('logo', 's3');
        }

        foreach ($this->gallery as $image) {
            $company->addMedia($image->getRealPath())
                ->usingFileName($image->getClientOriginalName())
                ->toMediaCollection('gallery', 's3');
        }

        app(CompanySubscriptionService::class)->assignFreePlanIfMissing($company);

        session()->flash('company-status', __('companies.created_successfully'));

        $this->redirect(route('my-companies'), navigate: false);
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

        <div class="content flex-row-fluid" id="kt_content">
            <!--begin::Stepper-->
            <div class="stepper stepper-pills stepper-column d-flex flex-column flex-xl-row flex-row-fluid gap-10" id="kt_create_company_stepper">
                <!--begin::کناری-->
                <div class="card d-flex justify-content-center justify-content-xl-start flex-row-auto w-100 w-xl-300px w-xxl-400px">
                    <div class="card-body px-6 px-lg-10 px-xxl-15 py-20">
                        <div class="stepper-nav">
                            @foreach ([1 => 'wizard_step_basic_info', 2 => 'wizard_step_category', 3 => 'wizard_step_state', 4 => 'wizard_step_media', 5 => 'wizard_step_contact'] as $number => $labelKey)
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
                                    <x-company-elements.category-tree-select :nodes="$this->categoryTree()" :expanded-ids="$this->expandedCategoryIds()" />
                                </div>
                                @error('categoryIds')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        @elseif ($step === 3)
                            <div class="w-100">
                                <div class="pb-10 pb-lg-15">
                                    <h2 class="fw-bold text-gray-900">{{ __('companies.wizard_step_state') }}</h2>
                                </div>
                                <x-company-elements.state-select :states="$this->states()" />
                            </div>
                        @elseif ($step === 4)
                            <div class="w-100">
                                <div class="pb-10 pb-lg-15">
                                    <h2 class="fw-bold text-gray-900">{{ __('companies.wizard_step_media') }}</h2>
                                </div>
                                <x-company-elements.media-fields :logo="$logo" :gallery="$gallery" />
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
