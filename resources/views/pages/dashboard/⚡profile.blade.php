<?php

use App\Enums\Gender;
use App\Models\Country;
use App\Models\Profile;
use App\Models\State;
use App\Models\User;
use App\Services\Otp\Exceptions\OtpException;
use App\Services\Otp\Exceptions\OtpFailureReason;
use App\Services\Otp\OtpService;
use Ariaieboy\Jalali\CalendarUtils;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts::landing')]
class extends Component
{
    use WithFileUploads;

    public $avatar = null;

    public string $name = '';

    public string $family = '';

    public ?string $username = null;

    public ?string $nationalCode = null;

    public ?string $gender = null;

    public ?string $birthDate = null;

    public ?int $countryId = null;

    public ?int $stateId = null;

    public ?string $city = null;

    public ?string $postalCode = null;

    public ?string $address = null;

    public ?string $biography = null;

    public ?string $jobTitle = null;

    public string $skills = '';

    public string $socialInstagram = '';

    public string $socialTelegram = '';

    public string $socialLinkedin = '';

    public string $socialWebsite = '';

    /** Which security action is currently in its OTP "code" step: '', 'email', or 'password'. */
    public string $securityAction = '';

    public string $securityStep = 'form';

    /** @var array<int, string> */
    public array $securityCode = [];

    public ?int $securityResendAvailableAt = null;

    public string $newEmail = '';

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    public bool $deactivateStep = false;

    public bool $confirmDeactivation = false;

    /** @var array<int, string> */
    public array $deactivateCode = [];

    public ?int $deactivateResendAvailableAt = null;

    public int $otpDigits;

    public function mount(): void
    {
        $this->otpDigits = (int) config('ippanel.otp.digits');
        $this->securityCode = array_fill(0, $this->otpDigits, '');
        $this->deactivateCode = array_fill(0, $this->otpDigits, '');

        $user = auth()->user();
        $profile = $user->profile;
        $locale = app()->getLocale();

        $this->name = (string) $user->getTranslation('name', $locale, false);
        $this->family = (string) $user->getTranslation('family', $locale, false);

        $this->username = $profile?->username;
        $this->nationalCode = $profile?->national_code;
        $this->gender = $profile?->gender?->value;
        $this->birthDate = $profile?->birth_date ? $this->toJalaliDateString($profile->birth_date) : null;
        $this->countryId = $profile?->country_id;
        $this->stateId = $profile?->state_id;
        $this->city = $profile?->city;
        $this->postalCode = $profile?->postal_code;
        $this->address = $profile?->getTranslation('address', $locale, false);
        $this->biography = $profile?->getTranslation('biography', $locale, false);
        $this->jobTitle = $profile?->getTranslation('job_title', $locale, false);
        $this->skills = implode(', ', $profile?->skills ?? []);

        $socialLinks = $profile?->social_links ?? [];
        $this->socialInstagram = $socialLinks['instagram'] ?? '';
        $this->socialTelegram = $socialLinks['telegram'] ?? '';
        $this->socialLinkedin = $socialLinks['linkedin'] ?? '';
        $this->socialWebsite = $socialLinks['website'] ?? '';
    }

    public function updatedCountryId(): void
    {
        $this->stateId = null;
    }

    public function countries(): Collection
    {
        return Country::query()->active()->orderBy('id')->get();
    }

    public function statesForSelectedCountry(): Collection
    {
        if (! $this->countryId) {
            return collect();
        }

        return State::query()->where('country_id', $this->countryId)->active()->orderBy('id')->get();
    }

    /**
     * @return array<string, string>
     */
    public function genderOptions(): array
    {
        return collect(Gender::cases())
            ->mapWithKeys(fn (Gender $gender) => [$gender->value => __('profile.gender_'.$gender->value)])
            ->all();
    }

    public function avatarPreviewUrl(): string
    {
        if ($this->avatar) {
            return $this->avatar->temporaryUrl();
        }

        return auth()->user()->getFirstMediaUrl('avatar', 'webp') ?: asset('theme/1/media/avatars/avatar-placeholder.png');
    }

    public function saveProfile(): void
    {
        $user = auth()->user();

        $validated = $this->validate([
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'name' => ['required', 'string', 'max:255'],
            'family' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('profiles', 'username')->ignore($user->id, 'user_id')],
            'nationalCode' => ['nullable', 'digits:10', Rule::unique('profiles', 'national_code')->ignore($user->id, 'user_id')],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'birthDate' => ['nullable', 'regex:/^\d{4}\/\d{1,2}\/\d{1,2}$/'],
            'countryId' => ['nullable', 'exists:countries,id'],
            'stateId' => ['nullable', 'exists:states,id'],
            'city' => ['nullable', 'string', 'max:255'],
            'postalCode' => ['nullable', 'string', 'max:10'],
            'address' => ['nullable', 'string', 'max:2000'],
            'biography' => ['nullable', 'string', 'max:2000'],
            'jobTitle' => ['nullable', 'string', 'max:255'],
            'skills' => ['nullable', 'string'],
            'socialInstagram' => ['nullable', 'url', 'max:255'],
            'socialTelegram' => ['nullable', 'url', 'max:255'],
            'socialLinkedin' => ['nullable', 'url', 'max:255'],
            'socialWebsite' => ['nullable', 'url', 'max:255'],
        ]);

        $locale = app()->getLocale();

        $user->setTranslation('name', $locale, $validated['name']);
        $user->setTranslation('family', $locale, $validated['family']);
        $user->save();

        $skills = collect(explode(',', $this->skills))
            ->map(fn (string $skill) => trim($skill))
            ->filter()
            ->values()
            ->all();

        $socialLinks = array_filter([
            'instagram' => $this->socialInstagram ?: null,
            'telegram' => $this->socialTelegram ?: null,
            'linkedin' => $this->socialLinkedin ?: null,
            'website' => $this->socialWebsite ?: null,
        ]);

        $profile = Profile::firstOrNew(['user_id' => $user->id]);
        $profile->username = $this->username ?: null;
        $profile->national_code = $this->nationalCode ?: null;
        $profile->gender = $this->gender ? Gender::from($this->gender) : null;
        $profile->birth_date = $this->birthDate ? $this->jalaliDateStringToGregorian($this->birthDate) : null;
        $profile->country_id = $this->countryId ?: null;
        $profile->state_id = $this->stateId ?: null;
        $profile->city = $this->city ?: null;
        $profile->postal_code = $this->postalCode ?: null;
        $profile->skills = $skills ?: null;
        $profile->social_links = $socialLinks ?: null;
        $profile->setTranslation('address', $locale, $this->address ?? '');
        $profile->setTranslation('biography', $locale, $this->biography ?? '');
        $profile->setTranslation('job_title', $locale, $this->jobTitle ?? '');
        $profile->save();

        if ($this->avatar) {
            $user->addMedia($this->avatar->getRealPath())
                ->usingFileName($this->avatar->getClientOriginalName())
                ->toMediaCollection('avatar', 's3');

            $this->avatar = null;
        }

        session()->flash('profile-status', __('profile.profile_updated'));
    }

    public function requestEmailChange(): void
    {
        $this->validate([
            'newEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(auth()->id())],
        ]);

        $this->startSecurityOtp('email');
    }

    public function requestPasswordChange(): void
    {
        $this->validate([
            'newPassword' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->startSecurityOtp('password');
    }

    public function resendSecurityCode(): void
    {
        $this->startSecurityOtp($this->securityAction);
    }

    public function cancelSecurityAction(): void
    {
        $this->securityAction = '';
        $this->securityStep = 'form';
        $this->securityCode = array_fill(0, $this->otpDigits, '');
        $this->newEmail = '';
        $this->newPassword = '';
        $this->newPassword_confirmation = '';
        $this->resetErrorBag();
    }

    public function verifySecurityCode(): void
    {
        $this->validate([
            'securityCode' => ['required', 'array', 'size:'.$this->otpDigits],
            'securityCode.*' => ['required', 'regex:/^\d$/'],
        ]);

        $user = auth()->user();

        try {
            app(OtpService::class)->verify($user->phone, implode('', $this->securityCode), "profile-{$this->securityAction}");
        } catch (OtpException $e) {
            $this->securityCode = array_fill(0, $this->otpDigits, '');

            throw ValidationException::withMessages([
                'securityCode' => $this->otpErrorMessage($e->reason, $e->retryAfter),
            ]);
        }

        match ($this->securityAction) {
            'email' => $this->applyEmailChange($user),
            'password' => $this->applyPasswordChange($user),
        };

        $this->cancelSecurityAction();
    }

    protected function applyEmailChange(User $user): void
    {
        $user->forceFill([
            'email' => $this->newEmail,
            'email_verified_at' => null,
        ])->save();

        session()->flash('profile-status', __('profile.email_change_success'));
    }

    protected function applyPasswordChange(User $user): void
    {
        $user->forceFill([
            'password' => $this->newPassword,
        ])->save();

        session()->flash('profile-status', __('profile.password_change_success'));
    }

    protected function startSecurityOtp(string $action): void
    {
        $this->ensureOtpRequestIsNotRateLimited($action);

        try {
            app(OtpService::class)->send(auth()->user()->phone, "profile-{$action}");
        } catch (OtpException $e) {
            throw ValidationException::withMessages([
                'securityOtp' => $this->otpErrorMessage($e->reason, $e->retryAfter),
            ]);
        }

        $this->securityAction = $action;
        $this->securityStep = 'code';
        $this->securityCode = array_fill(0, $this->otpDigits, '');
        $this->securityResendAvailableAt = now()->addSeconds((int) config('ippanel.otp.resend_after'))->timestamp;
    }

    public function requestDeactivation(): void
    {
        $this->validate([
            'confirmDeactivation' => ['accepted'],
        ]);

        $this->ensureOtpRequestIsNotRateLimited('deactivate');

        try {
            app(OtpService::class)->send(auth()->user()->phone, 'profile-deactivate');
        } catch (OtpException $e) {
            throw ValidationException::withMessages([
                'deactivateOtp' => $this->otpErrorMessage($e->reason, $e->retryAfter),
            ]);
        }

        $this->deactivateStep = true;
        $this->deactivateCode = array_fill(0, $this->otpDigits, '');
        $this->deactivateResendAvailableAt = now()->addSeconds((int) config('ippanel.otp.resend_after'))->timestamp;
    }

    public function verifyDeactivationCode(): void
    {
        $this->validate([
            'deactivateCode' => ['required', 'array', 'size:'.$this->otpDigits],
            'deactivateCode.*' => ['required', 'regex:/^\d$/'],
        ]);

        $user = auth()->user();

        try {
            app(OtpService::class)->verify($user->phone, implode('', $this->deactivateCode), 'profile-deactivate');
        } catch (OtpException $e) {
            $this->deactivateCode = array_fill(0, $this->otpDigits, '');

            throw ValidationException::withMessages([
                'deactivateCode' => $this->otpErrorMessage($e->reason, $e->retryAfter),
            ]);
        }

        // Soft deactivation only: dashboard access and the session are left
        // untouched. See User::scopePubliclyVisible() for what this flag means.
        $user->forceFill(['deactivated_at' => now()])->save();

        $this->cancelDeactivation();

        session()->flash('profile-status', __('profile.account_deactivated_success'));
    }

    public function cancelDeactivation(): void
    {
        $this->deactivateStep = false;
        $this->confirmDeactivation = false;
        $this->deactivateCode = array_fill(0, $this->otpDigits, '');
        $this->resetErrorBag();
    }

    /**
     * Reactivation only restores public visibility and carries no security
     * risk (unlike email/password changes or deactivation itself), so it
     * doesn't need the OTP-confirmed flow those actions use.
     */
    public function reactivateAccount(): void
    {
        auth()->user()->forceFill(['deactivated_at' => null])->save();

        session()->flash('profile-status', __('profile.account_reactivated_success'));
    }

    protected function ensureOtpRequestIsNotRateLimited(string $action): void
    {
        $key = $this->otpRequestThrottleKey($action);

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'securityOtp' => __('profile.otp_request_throttled', ['seconds' => RateLimiter::availableIn($key)]),
                'deactivateOtp' => __('profile.otp_request_throttled', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        RateLimiter::hit($key, 600);
    }

    protected function otpRequestThrottleKey(string $action): string
    {
        return "otp-request:{$action}:".auth()->id();
    }

    protected function otpErrorMessage(OtpFailureReason $reason, int $retryAfter = 0): string
    {
        return match ($reason) {
            OtpFailureReason::Cooldown => __('auth.otp-cooldown', ['seconds' => $retryAfter]),
            OtpFailureReason::DeliveryFailed => __('auth.otp-delivery-failed'),
            OtpFailureReason::NotFound => __('auth.otp-not-requested'),
            OtpFailureReason::Expired => __('auth.otp-expired'),
            OtpFailureReason::TooManyAttempts => __('auth.otp-too-many-attempts'),
            OtpFailureReason::Invalid => __('auth.otp-invalid'),
        };
    }

    /**
     * birth_date is stored as a plain Gregorian date; jalaliDatepicker only
     * ever writes/reads Jalali "Y/m/d" strings in the input, so every value
     * has to be converted at the mount/save boundary.
     */
    protected function toJalaliDateString(Carbon $date): string
    {
        [$year, $month, $day] = CalendarUtils::toJalali($date->year, $date->month, $date->day);

        return sprintf('%04d/%02d/%02d', $year, $month, $day);
    }

    protected function jalaliDateStringToGregorian(string $jalali): string
    {
        [$year, $month, $day] = array_map('intval', explode('/', $jalali));
        [$gregorianYear, $gregorianMonth, $gregorianDay] = CalendarUtils::toGregorian($year, $month, $day);

        return sprintf('%04d-%02d-%02d', $gregorianYear, $gregorianMonth, $gregorianDay);
    }

    public function render()
    {
        return $this->view()->title(__('menu.my_profile').' - '.__('auth.user-dashboard').' | '.__('globals.viravach'));
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">
        <livewire:dashboard-elements.infobar/>

        {{-- securityOtp/deactivateOtp are form-level validation keys with no
             input of their own, so they toast alongside the flashed status. --}}
        @include('partials.flash-alerts', ['errorKeys' => ['securityOtp', 'deactivateOtp']])

        <div class="card mb-5 mb-xl-10">
            <div class="card-header border-0 cursor-pointer" role="button" data-bs-toggle="collapse"
                 data-bs-target="#kt_account_profile_details" aria-expanded="true"
                 aria-controls="kt_account_profile_details">
                <div class="card-title m-0">
                    <h3 class="fw-bold m-0">{{ __('profile.section_profile_details') }}</h3>
                </div>
            </div>
            <div id="kt_account_settings_profile_details" class="collapse show">
                <form class="form" wire:submit="saveProfile">
                    <div class="card-body border-top p-9">
                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">{{ __('profile.avatar') }}</label>
                            <div class="col-lg-8">
                                <div class="symbol symbol-100px symbol-lg-125px">
                                    <img src="{{ $this->avatarPreviewUrl() }}" alt="{{ __('profile.avatar') }}"
                                         class="rounded" />
                                </div>
                                <div class="mt-3">
                                    <input type="file" wire:model="avatar" accept="image/png,image/jpeg,image/webp"
                                           class="form-control form-control-solid @error('avatar') is-invalid @enderror" />
                                    @error('avatar')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text">{{ __('profile.avatar_hint') }}</div>
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label required fw-semibold fs-6">{{ __('profile.first_name') }} / {{ __('profile.last_name') }}</label>
                            <div class="col-lg-8">
                                <div class="row">
                                    <div class="col-lg-6 fv-row">
                                        <input type="text" wire:model="name"
                                               class="form-control form-control-lg form-control-solid mb-3 mb-lg-0 @error('name') is-invalid @enderror"
                                               placeholder="{{ __('profile.first_name') }}" />
                                        @error('name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-lg-6 fv-row">
                                        <input type="text" wire:model="family"
                                               class="form-control form-control-lg form-control-solid @error('family') is-invalid @enderror"
                                               placeholder="{{ __('profile.last_name') }}" />
                                        @error('family')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">{{ __('profile.phone_number') }}</label>
                            <div class="col-lg-8 fv-row d-flex align-items-center">
                                <input type="tel" value="{{ auth()->user()->phone }}" disabled
                                       class="form-control form-control-lg form-control-solid" />
                                <span class="badge {{ auth()->user()->phone_verified_at ? 'badge-light-success' : 'badge-light-warning' }} ms-3">
                                    {{ auth()->user()->phone_verified_at ? __('profile.phone_verified') : __('profile.phone_not_verified') }}
                                </span>
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">{{ __('profile.username') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="text" wire:model="username"
                                       class="form-control form-control-lg form-control-solid @error('username') is-invalid @enderror" />
                                @error('username')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">{{ __('profile.national_code') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="text" wire:model="nationalCode"
                                       class="form-control form-control-lg form-control-solid @error('nationalCode') is-invalid @enderror" />
                                @error('nationalCode')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">{{ __('profile.gender') }}</label>
                            <div class="col-lg-8 fv-row">
                                <select wire:model="gender" class="form-select form-select-solid form-select-lg @error('gender') is-invalid @enderror">
                                    <option value="">{{ __('profile.select_placeholder') }}</option>
                                    @foreach ($this->genderOptions() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">{{ __('profile.birth_date') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="text" wire:model="birthDate" data-jdp autocomplete="off" dir="ltr"
                                       placeholder="1370/01/01"
                                       class="form-control form-control-lg form-control-solid @error('birthDate') is-invalid @enderror" />
                                @error('birthDate')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">{{ __('profile.country') }}</label>
                            <div class="col-lg-8 fv-row">
                                <select wire:model.live="countryId" class="form-select form-select-solid form-select-lg @error('countryId') is-invalid @enderror">
                                    <option value="">{{ __('profile.select_placeholder') }}</option>
                                    @foreach ($this->countries() as $country)
                                        <option value="{{ $country->id }}">{{ $country->name }}</option>
                                    @endforeach
                                </select>
                                @error('countryId')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">{{ __('profile.state') }}</label>
                            <div class="col-lg-8 fv-row">
                                <select wire:model="stateId" class="form-select form-select-solid form-select-lg @error('stateId') is-invalid @enderror">
                                    <option value="">{{ __('profile.select_placeholder') }}</option>
                                    @foreach ($this->statesForSelectedCountry() as $state)
                                        <option value="{{ $state->id }}">{{ $state->name }}</option>
                                    @endforeach
                                </select>
                                @error('stateId')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">{{ __('profile.city') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="text" wire:model="city"
                                       class="form-control form-control-lg form-control-solid @error('city') is-invalid @enderror" />
                                @error('city')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">{{ __('profile.postal_code') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="text" wire:model="postalCode"
                                       class="form-control form-control-lg form-control-solid @error('postalCode') is-invalid @enderror" />
                                @error('postalCode')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">{{ __('profile.address') }}</label>
                            <div class="col-lg-8 fv-row">
                                <textarea wire:model="address" rows="2"
                                          class="form-control form-control-lg form-control-solid @error('address') is-invalid @enderror"></textarea>
                                @error('address')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">{{ __('profile.job_title') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="text" wire:model="jobTitle"
                                       class="form-control form-control-lg form-control-solid @error('jobTitle') is-invalid @enderror" />
                                @error('jobTitle')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">{{ __('profile.biography') }}</label>
                            <div class="col-lg-8 fv-row">
                                <textarea wire:model="biography" rows="3"
                                          class="form-control form-control-lg form-control-solid @error('biography') is-invalid @enderror"></textarea>
                                @error('biography')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">{{ __('profile.skills') }}</label>
                            <div class="col-lg-8 fv-row">
                                <input type="text" wire:model="skills"
                                       class="form-control form-control-lg form-control-solid @error('skills') is-invalid @enderror" />
                                <div class="form-text">{{ __('profile.skills_hint') }}</div>
                                @error('skills')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-0">
                            <label class="col-lg-4 col-form-label fw-semibold fs-6">{{ __('profile.social_links') }}</label>
                            <div class="col-lg-8">
                                <div class="row mb-3">
                                    <div class="col-lg-6 fv-row">
                                        <input type="url" wire:model="socialInstagram" placeholder="{{ __('profile.social_instagram') }}"
                                               class="form-control form-control-lg form-control-solid mb-3 @error('socialInstagram') is-invalid @enderror" />
                                        @error('socialInstagram')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-lg-6 fv-row">
                                        <input type="url" wire:model="socialTelegram" placeholder="{{ __('profile.social_telegram') }}"
                                               class="form-control form-control-lg form-control-solid mb-3 @error('socialTelegram') is-invalid @enderror" />
                                        @error('socialTelegram')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-lg-6 fv-row">
                                        <input type="url" wire:model="socialLinkedin" placeholder="{{ __('profile.social_linkedin') }}"
                                               class="form-control form-control-lg form-control-solid @error('socialLinkedin') is-invalid @enderror" />
                                        @error('socialLinkedin')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-lg-6 fv-row">
                                        <input type="url" wire:model="socialWebsite" placeholder="{{ __('profile.social_website') }}"
                                               class="form-control form-control-lg form-control-solid @error('socialWebsite') is-invalid @enderror" />
                                        @error('socialWebsite')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveProfile,avatar">
                            {{ __('profile.save_changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-5 mb-xl-10">
            <div class="card-header border-0 cursor-pointer" role="button" data-bs-toggle="collapse" data-bs-target="#kt_account_signin_method">
                <div class="card-title m-0">
                    <h3 class="fw-bold m-0">{{ __('profile.section_login_security') }}</h3>
                </div>
            </div>
            <div id="kt_account_settings_signin_method" class="collapse show">
                <div class="card-body border-top p-9">
                    @if ($securityStep === 'form')
                        <div class="d-flex flex-wrap align-items-center">
                            <div>
                                <div class="fs-6 fw-bold mb-1">{{ __('profile.current_email') }}</div>
                                <div class="fw-semibold text-gray-600">{{ auth()->user()->email }}</div>
                            </div>
                            <div class="ms-auto">
                                <button type="button" class="btn btn-light btn-active-light-primary"
                                        data-bs-toggle="modal" data-bs-target="#kt_modal_change_email">
                                    {{ __('profile.change_email') }}
                                </button>
                            </div>
                        </div>
                        <div class="separator separator-dashed my-6"></div>
                        <div class="d-flex flex-wrap align-items-center">
                            <div>
                                <div class="fs-6 fw-bold mb-1">{{ __('profile.change_password') }}</div>
                                <div class="fw-semibold text-gray-600">************</div>
                            </div>
                            <div class="ms-auto">
                                <button type="button" class="btn btn-light btn-active-light-primary"
                                        data-bs-toggle="modal" data-bs-target="#kt_modal_change_password">
                                    {{ __('profile.change_password') }}
                                </button>
                            </div>
                        </div>

                        <div class="collapse show mt-6" id="kt_modal_change_email">
                            <form wire:submit="requestEmailChange" class="border rounded p-6">
                                <label class="form-label fs-6 fw-bold mb-3">{{ __('profile.new_email') }}</label>
                                <input type="email" wire:model="newEmail"
                                       class="form-control form-control-lg form-control-solid @error('newEmail') is-invalid @enderror" />
                                @error('newEmail')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="d-flex mt-4">
                                    <button type="submit" class="btn btn-primary me-2 px-6">{{ __('profile.send_verification_code') }}</button>
                                </div>
                            </form>
                        </div>

                        <div class="collapse show mt-6" id="kt_modal_change_password">
                            <form wire:submit="requestPasswordChange" class="border rounded p-6">
                                <div class="row mb-4">
                                    <div class="col-lg-6">
                                        <label class="form-label fs-6 fw-bold mb-3">{{ __('profile.new_password') }}</label>
                                        <input type="password" wire:model="newPassword"
                                               class="form-control form-control-lg form-control-solid @error('newPassword') is-invalid @enderror" />
                                        @error('newPassword')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="form-label fs-6 fw-bold mb-3">{{ __('profile.new_password_confirmation') }}</label>
                                        <input type="password" wire:model="newPassword_confirmation"
                                               class="form-control form-control-lg form-control-solid" />
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <button type="submit" class="btn btn-primary me-2 px-6">{{ __('profile.send_verification_code') }}</button>
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-6 mb-6">
                            <div class="fw-semibold">
                                {{ __('profile.otp_sent_notice', ['phone' => auth()->user()->phone]) }}
                            </div>
                        </div>
                        <form wire:submit="verifySecurityCode">
                            <div class="d-flex flex-wrap mb-4" dir="ltr">
                                @for ($i = 0; $i < $otpDigits; $i++)
                                    <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1"
                                           wire:model="securityCode.{{ $i }}"
                                           class="form-control h-60px w-60px fs-2qx text-center mx-1 my-2 @error('securityCode.'.$i) is-invalid @enderror" />
                                @endfor
                            </div>
                            @error('securityCode')
                                <div class="invalid-feedback d-block mb-4">{{ $message }}</div>
                            @enderror
                            <div class="d-flex">
                                <button type="submit" class="btn btn-primary me-2 px-6">{{ __('profile.verify_and_apply') }}</button>
                                <button type="button" wire:click="resendSecurityCode" class="btn btn-light-primary me-2 px-6">{{ __('auth.resend') }}</button>
                                <button type="button" wire:click="cancelSecurityAction" class="btn btn-color-gray-500 btn-active-light-primary px-6">{{ __('profile.back') }}</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0 cursor-pointer" role="button" data-bs-toggle="collapse" data-bs-target="#kt_account_deactivate" aria-expanded="true" aria-controls="kt_account_deactivate">
                <div class="card-title m-0">
                    <h3 class="fw-bold m-0">{{ __('profile.section_deactivate_account') }}</h3>
                </div>
            </div>
            <div id="kt_account_settings_deactivate" class="collapse show">
                <div class="card-body border-top p-9">
                    @if (auth()->user()->deactivated_at)
                        <div class="notice d-flex flex-stack bg-light-warning rounded border-warning border border-dashed p-6">
                            <div class="fw-semibold">{{ __('profile.account_already_deactivated_notice') }}</div>
                            <button type="button" wire:click="reactivateAccount" class="btn btn-primary fw-semibold ms-4">
                                {{ __('profile.reactivate_account_button') }}
                            </button>
                        </div>
                    @elseif (! $deactivateStep)
                        <form wire:submit="requestDeactivation">
                            <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed mb-9 p-6">
                                <div class="d-flex flex-stack flex-grow-1">
                                    <div class="fw-semibold">
                                        <h4 class="text-gray-900 fw-bold">{{ __('profile.deactivate_notice_title') }}</h4>
                                        <div class="fs-6 text-gray-700">{{ __('profile.deactivate_notice_body') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-check form-check-solid fv-row">
                                <input wire:model="confirmDeactivation" class="form-check-input @error('confirmDeactivation') is-invalid @enderror"
                                       type="checkbox" id="deactivate" />
                                <label class="form-check-label fw-semibold ps-2 fs-6" for="deactivate">{{ __('profile.confirm_deactivation') }}</label>
                                @error('confirmDeactivation')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="d-flex justify-content-end mt-6">
                                <button type="submit" class="btn btn-danger fw-semibold">{{ __('profile.deactivate_account_button') }}</button>
                            </div>
                        </form>
                    @else
                        <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-6 mb-6">
                            <div class="fw-semibold">
                                {{ __('profile.otp_sent_notice', ['phone' => auth()->user()->phone]) }}
                            </div>
                        </div>
                        <form wire:submit="verifyDeactivationCode">
                            <div class="d-flex flex-wrap mb-4" dir="ltr">
                                @for ($i = 0; $i < $otpDigits; $i++)
                                    <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1"
                                           wire:model="deactivateCode.{{ $i }}"
                                           class="form-control h-60px w-60px fs-2qx text-center mx-1 my-2 @error('deactivateCode.'.$i) is-invalid @enderror" />
                                @endfor
                            </div>
                            @error('deactivateCode')
                                <div class="invalid-feedback d-block mb-4">{{ $message }}</div>
                            @enderror
                            <div class="d-flex">
                                <button type="submit" class="btn btn-danger me-2 px-6">{{ __('profile.verify_and_apply') }}</button>
                                <button type="button" wire:click="requestDeactivation" class="btn btn-light-primary me-2 px-6">{{ __('auth.resend') }}</button>
                                <button type="button" wire:click="cancelDeactivation" class="btn btn-color-gray-500 btn-active-light-primary px-6">{{ __('profile.back') }}</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
