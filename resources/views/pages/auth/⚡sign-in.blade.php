<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts::auth')]
class extends Component {

    public string $login = '';
    public string $password = '';

    public function authenticate(): void
    {
        $this->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited();

        $field = filter_var($this->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (! Auth::attempt([$field => $this->login, 'password' => $this->password])) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        $this->redirect(
            Auth::user()->hasAnyRole(['super_admin', 'shareholder'])
                ? route('filament.admin.pages.dashboard')
                : route('dashboard'),
        );
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        throw ValidationException::withMessages([
            'login' => __('auth.throttle', [
                'seconds' => RateLimiter::availableIn($this->throttleKey()),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->login) . '|' . request()->ip());
    }

    public function render()
    {
        return $this->view()->title(__('auth.sign-in') . ' - ' . __('auth.user-dashboard') . ' | ' . __('globals.viravach'));
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
    <div
        class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12 p-lg-20">
        <!--begin::کارت-->
        <div class="bg-body d-flex flex-column align-items-stretch flex-center rounded-4 w-md-600px p-20">
            <!--begin::Wrapper-->
            <div class="d-flex flex-center flex-column flex-column-fluid px-lg-10 pb-15 pb-lg-20">
                <!--begin::form-->
                <form class="form w-100" novalidate="novalidate" wire:submit="authenticate"> {{-- id="kt_sign_in_form" --}}
                    <!--begin::Heading-->
                    <div class="text-center mb-11">
                        <!--begin::Title-->
                        <h1 class="text-gray-900 fw-bolder mb-3">
                            {{ __('auth.sign-in') }}
                        </h1>
                        <!--end::Title-->
                        <!--begin::Subtitle-->
                        {{--<div class="text-gray-500 fw-semibold fs-6">ورود با شبکه های اجتماعی</div>--}}
                        <!--end::Subtitle=-->
                    </div>
                    <!--begin::Heading-->
                    <!--begin::separator-->
                    <div class="separator separator-content my-14">
                        <span class="w-300px text-gray-500 fw-semibold fs-7">
                            {{ __('auth.email-or-phone') }}
                        </span>
                    </div>
                    <!--end::separator-->
                    <!--begin::Input group=-->
                    <div class="fv-row mb-8">
                        <!--begin::email or phone-->
                        <input type="text" placeholder="info@gmail.com | 09123456789" wire:model="login"
                               autocomplete="off" class="form-control bg-transparent @error('login') is-invalid @enderror"/>
                        <!--end::email or phone-->
                        @error('login')
                            <div class="fv-plugins-message-container invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <!--end::Input group=-->
                    <div class="fv-row mb-3">
                        <!--begin::password-->
                        <input type="password" placeholder="{{ __('auth.pass') }}" wire:model="password"
                               autocomplete="off" class="form-control bg-transparent @error('password') is-invalid @enderror"/>
                        <!--end::password-->
                        @error('password')
                            <div class="fv-plugins-message-container invalid-feedback d-block">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <!--end::Input group=-->
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                        <!--begin::Link-->
                        <a href="{{route('auth.reset-password')}}" class="link-primary">
                            {{ __('auth.forget-password') }}
                        </a>
                        <!--end::Link-->
                    </div>
                    <!--end::Wrapper-->
                    <!--begin::sign up button-->
                    <div class="d-grid mb-1">
                        <button type="submit" class="btn btn-primary"
                                wire:loading.attr="disabled" wire:target="authenticate"> {{-- id="kt_sign_in_submit" --}}
                            <!--begin::Indicatیا label-->
                            <span class="indicator-label" wire:loading.remove wire:target="authenticate">
                                {{ __('auth.sign-in') }}
                            </span>
                            <!--end::Indicatیا label-->
                            <!--begin::Indicatیا progress-->
                            <span class="indicator-progress" wire:loading.flex wire:target="authenticate"
                                  style="display: none;">
                                {{ __('auth.please-wait') }}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            <!--end::Indicatیا progress-->
                        </button>
                    </div>
                    <div class="d-grid mb-10">
                        <a href="{{ route('auth.secure-login') }}" class="btn btn-outline btn-outline-primary">
                            {{ __('auth.two-factor-auth') }}
                        </a>
                    </div>
                    <!--end::sign up button-->
                    <!--begin::sign up-->
                    <div class="text-gray-500 text-center fw-semibold fs-6">
                        {{ __('auth.not-a-member-yet') }}
                        <a href="{{route('auth.sign-up')}}" class="link-primary">
                            {{ __('auth.sign-up') }}
                        </a>
                    </div>
                    <!--end::sign up-->
                </form>
                <!--end::form-->
            </div>
            <!--end::Wrapper-->
            <!--begin::Footer-->
            <div class="d-flex flex-stack px-lg-10">
                <!--begin::languages-->
                <livewire:header-elements.tools.language-switcher />
                {{--<div class="me-0">
                    <!--begin::Toggle-->
                    <button class="btn btn-flex btn-link btn-color-gray-700 btn-active-color-primary rotate fs-base"
                            data-kt-menu-trigger="click"
                            data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'bottom-end' : 'bottom-start' }}"
                            data-kt-menu-offset="0px, 0px">
                        <img data-kt-element="current-lang-flag" class="w-20px h-20px rounded me-3"
                             src="{{asset('theme/1/media/flags/united-states.svg')}}" alt=""/>
                        <span data-kt-element="current-lang-name" class="me-1">انگلیسی</span>
                        <i class="ki-duotone ki-down fs-5 text-muted rotate-180 m-0"></i>
                    </button>
                    <!--end::Toggle-->
                    <!--begin::Menu-->
                    <div
                        class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-4 fs-7"
                        data-kt-menu="true" id="kt_auth_lang_menu">
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link d-flex px-5" data-kt-lang="انگلیسی">
											<span class="symbol symbol-20px me-4">
												<img data-kt-element="lang-flag" class="rounded-1"
                                                     src="{{asset('theme/1/media/flags/united-states.svg')}}" alt=""/>
											</span>
                                <span data-kt-element="lang-name">انگلیسی</span>
                            </a>
                        </div>
                        <!--end::Menu item-->
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link d-flex px-5" data-kt-lang="اسپانیایی">
											<span class="symbol symbol-20px me-4">
												<img data-kt-element="lang-flag" class="rounded-1"
                                                     src="{{asset('theme/1/media/flags/spain.svg')}}" alt=""/>
											</span>
                                <span data-kt-element="lang-name">اسپانیایی</span>
                            </a>
                        </div>
                        <!--end::Menu item-->
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link d-flex px-5" data-kt-lang="آلمانی">
											<span class="symbol symbol-20px me-4">
												<img data-kt-element="lang-flag" class="rounded-1"
                                                     src="{{asset('theme/1/media/flags/germany.svg')}}" alt=""/>
											</span>
                                <span data-kt-element="lang-name">آلمانی</span>
                            </a>
                        </div>
                        <!--end::Menu item-->
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link d-flex px-5" data-kt-lang="ژاپنی">
											<span class="symbol symbol-20px me-4">
												<img data-kt-element="lang-flag" class="rounded-1"
                                                     src="{{asset('theme/1/media/flags/japan.svg')}}" alt=""/>
											</span>
                                <span data-kt-element="lang-name">ژاپنی</span>
                            </a>
                        </div>
                        <!--end::Menu item-->
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link d-flex px-5" data-kt-lang="فرانسه">
											<span class="symbol symbol-20px me-4">
												<img data-kt-element="lang-flag" class="rounded-1"
                                                     src="{{asset('theme/1/media/flags/france.svg')}}" alt=""/>
											</span>
                                <span data-kt-element="lang-name">فرانسه</span>
                            </a>
                        </div>
                        <!--end::Menu item-->
                    </div>
                    <!--end::Menu-->
                </div>--}}
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
        <!--end::کارت-->
    </div>
    <!--end::Body-->
</div>
