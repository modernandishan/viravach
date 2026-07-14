<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new
#[Layout('layouts::auth')]
class extends Component {
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255')]
    public string $family = '';

    #[Validate('required|email|max:255|unique:users,email')]
    public string $email = '';

    #[Validate('required|regex:/^09\d{9}$/|unique:users,phone')]
    public string $phone = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    #[Validate('accepted')]
    public bool $toc = false;

    public function register(): void
    {
        $this->ensureIsNotRateLimited();

        $validated = $this->validate();

        $user = User::create([
            'name' => $validated['name'],
            'family' => $validated['family'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
        ]);

        // $user->assignRole('user');

        event(new Registered($user));
        Auth::login($user);

        RateLimiter::clear($this->throttleKey());

        $this->redirect(route('dashboard'), navigate: true);
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => RateLimiter::availableIn($this->throttleKey()),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate('sign-up:' . request()->ip());
    }

    public function render()
    {
        return $this->view()->title(__('auth.sign-up') . ' - ' . __('auth.user-dashboard') . ' | ' . __('globals.viravach'));
    }
};
?>

<div class="d-flex flex-column flex-column-fluid flex-lg-row">
    <!--begin::کناری-->
    <div class="d-flex flex-center w-lg-50 pt-15 pt-lg-0 px-10">
        <!--begin::کناری-->
        <div class="d-flex flex-center flex-lg-start flex-column">
            <!--begin::Logo-->
            <a href="{{route('home')}}" class="mb-7">
                <img alt="Logo" src="{{asset('theme/1/media/logos/ViraVach-logo-3.png')}}"/>
            </a>
            <!--end::Logo-->
            <!--begin::Title-->
            <h2 class="text-white fw-normal m-0">
                {{ __('auth.comprehensive-tool-for-large-businesses') }}
            </h2>
            <!--end::Title-->
        </div>
        <!--begin::کناری-->
    </div>
    <!--begin::کناری-->
    <!--begin::Body-->
    <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12 p-lg-20">
        <!--begin::کارت-->
        <div class="bg-body d-flex flex-column align-items-stretch flex-center rounded-4 w-md-600px p-20">
            <!--begin::Wrapper-->
            <div class="d-flex flex-center flex-column flex-column-fluid px-lg-10 pb-15 pb-lg-20">
                <!--begin::form-->
                <!--begin::form-->
                <form class="form w-100" novalidate="novalidate" id="kt_sign_up_form" wire:submit="register">
                    <!--begin::Heading-->
                    <div class="text-center mb-11">
                        <h1 class="text-gray-900 fw-bolder mb-3">{{ __('auth.sign-up') }}</h1>
                    </div>
                    <!--end::Heading-->

                    <!--begin::separator-->
                    <div class="separator separator-content my-14">
                        <span class="w-300px text-gray-500 fw-semibold fs-7">{{__('auth.with-email-and-phone')}}</span>
                    </div>
                    <!--end::separator-->

                    <!--begin::Name & Family-->
                    <div class="fv-row mb-8">
                        <input type="text" placeholder="{{ __('auth.name') }}" wire:model="name" autocomplete="name"
                               class="form-control bg-transparent @error('name') is-invalid @enderror"/>
                        @error('name')
                        <div
                            class="fv-plugins-message-container invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="fv-row mb-8">
                        <input type="text" placeholder="{{ __('auth.family') }}" wire:model="family"
                               autocomplete="family-name"
                               class="form-control bg-transparent @error('family') is-invalid @enderror"/>
                        @error('family')
                        <div
                            class="fv-plugins-message-container invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <!--end::Name & Family-->

                    <!--begin::Email & Phone-->
                    <div class="fv-row mb-8">
                        <input type="email" placeholder="{{ __('auth.email') }}" wire:model="email" autocomplete="email"
                               class="form-control bg-transparent @error('email') is-invalid @enderror"/>
                        @error('email')
                        <div
                            class="fv-plugins-message-container invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="fv-row mb-8">
                        <input type="tel" placeholder="{{ __('auth.phone-number') }}" wire:model="phone"
                               autocomplete="tel"
                               class="form-control bg-transparent @error('phone') is-invalid @enderror"/>
                        @error('phone')
                        <div
                            class="fv-plugins-message-container invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <!--end::Email & Phone-->

                    <!--begin::Password-->
                    <div class="fv-row mb-8" data-kt-password-meter="true">
                        <div class="mb-1">
                            <div class="position-relative mb-3">
                                <input class="form-control bg-transparent @error('password') is-invalid @enderror"
                                       type="password" placeholder="{{ __('auth.pass') }}"
                                       wire:model="password" autocomplete="new-password"/>
                                <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2"
                                      data-kt-password-meter-control="visibility">
                    <i class="ki-duotone ki-eye-slash fs-2"></i>
                    <i class="ki-duotone ki-eye fs-2 d-none"></i>
                </span>
                            </div>
                            @error('password')
                            <div
                                class="fv-plugins-message-container invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <!--end::Password-->

                    <!--begin::Confirm Password-->
                    <div class="fv-row mb-8">
                        <input placeholder="{{ __('auth.confirm-password') }}" wire:model="password_confirmation"
                               type="password" autocomplete="new-password"
                               class="form-control bg-transparent @error('password_confirmation') is-invalid @enderror"/>
                        @error('password_confirmation')
                        <div
                            class="fv-plugins-message-container invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <!--end::Confirm Password-->

                    <!--begin::Accept-->
                    <div class="fv-row mb-8">
                        <label class="form-check form-check-inline">
                            <input class="form-check-input @error('toc') is-invalid @enderror" type="checkbox"
                                   wire:model="toc" value="1"/>
                            <span class="form-check-label fw-semibold text-gray-700 fs-base ms-1">
                {{ __('auth.terms-and-conditions') }}
                <a href="{{route('terms-and-conditions')}}" class="ms-1 link-primary">{{ __('auth.accepted') }}</a>
            </span>
                        </label>
                        @error('toc')
                        <div
                            class="fv-plugins-message-container invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <!--end::Accept-->

                    <!--begin::Submit button-->
                    <div class="d-grid mb-10">
                        <button type="submit" id="kt_sign_up_submit" class="btn btn-primary"
                                wire:loading.attr="disabled" wire:target="register">
                            <span class="indicator-label" wire:loading.remove
                                  wire:target="register">{{ __('auth.sign-up') }}</span>
                            <span class="indicator-progress" wire:loading.flex wire:target="register"
                                  style="display: none;">
                {{ __('auth.please-wait') }}
                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
            </span>
                        </button>
                    </div>
                    <!--end::Submit button-->

                    <!--begin::Sign in link-->
                    <div class="text-gray-500 text-center fw-semibold fs-6">
                        {{ __('auth.already-registered') }}
                        <a href="{{ route('auth.sign-in') }}"
                           class="link-primary fw-semibold">{{ __('auth.sign-in') }}</a>
                    </div>
                    <!--end::Sign in link-->
                </form>
                <!--end::form-->
                <!--end::form-->
            </div>
            <!--end::Wrapper-->
            <!--begin::Footer-->
            <div class="d-flex flex-stack px-lg-10">
                <!--begin::languages-->
                <livewire:header-elements.tools.language-switcher/>
                <!--end::languages-->
                <!--begin::Links-->
                <div class="d-flex fw-semibold text-primary fs-base gap-5">
                    <a href="pages/team.html" target="_blank">تیم ها</a>
                    <a href="pages/pricing/column.html" target="_blank">برنامه ریزی ها</a>
                    <a href="pages/contact.html" target="_blank">تماس با ما</a>
                </div>
                <!--end::Links-->
            </div>
            <!--end::Footer-->
        </div>
        <!--end::cart-->
    </div>
    <!--end::Body-->
</div>
