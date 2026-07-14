<?php

use App\Models\User;
use App\Services\Otp\Exceptions\OtpException;
use App\Services\Otp\Exceptions\OtpFailureReason;
use App\Services\Otp\OtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts::auth')]
class extends Component
{
    protected const PURPOSE = 'secure-login';

    public string $phone = '';

    public string $step = 'phone';

    /** @var array<int, string> */
    public array $code = [];

    public int $digits;

    public ?int $resendAvailableAt = null;

    public function mount(): void
    {
        $this->digits = (int) config('ippanel.otp.digits');
        $this->code = array_fill(0, $this->digits, '');
    }

    public function sendCode(): void
    {
        $this->validate([
            'phone' => ['required', 'regex:/^09\d{9}$/'],
        ]);

        if (! User::where('phone', $this->phone)->exists()) {
            throw ValidationException::withMessages([
                'phone' => __('auth.phone-not-found'),
            ]);
        }

        try {
            $this->requestOtp();
        } catch (OtpException $e) {
            throw ValidationException::withMessages([
                'phone' => $this->messageFor($e->reason, $e->retryAfter),
            ]);
        }

        $this->step = 'code';
        $this->code = array_fill(0, $this->digits, '');
    }

    public function resendCode(): void
    {
        try {
            $this->requestOtp();
        } catch (OtpException $e) {
            $this->addError('resend', $this->messageFor($e->reason, $e->retryAfter));

            return;
        }

        $this->resetErrorBag('resend');
        $this->code = array_fill(0, $this->digits, '');
    }

    public function backToPhone(): void
    {
        $this->step = 'phone';
        $this->resendAvailableAt = null;
        $this->code = array_fill(0, $this->digits, '');
        $this->resetErrorBag();
    }

    public function verifyCode(): void
    {
        $this->validate([
            'code' => ['required', 'array', 'size:'.$this->digits],
            'code.*' => ['required', 'regex:/^\d$/'],
        ]);

        try {
            app(OtpService::class)->verify($this->phone, implode('', $this->code), self::PURPOSE);
        } catch (OtpException $e) {
            $this->code = array_fill(0, $this->digits, '');

            throw ValidationException::withMessages([
                'code' => $this->messageFor($e->reason, $e->retryAfter),
            ]);
        }

        $user = User::where('phone', $this->phone)->firstOrFail();

        if ($user->phone_verified_at === null) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        Auth::login($user);
        session()->regenerate();

        $this->redirect(
            $user->hasAnyRole(['super_admin', 'shareholder'])
                ? route('filament.admin.pages.dashboard')
                : route('dashboard'),
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function resendCountdownParts(): array
    {
        $marker = '␟';
        $text = __('auth.resend-in', ['seconds' => $marker]);

        return str_contains($text, $marker) ? explode($marker, $text, 2) : [$text, ''];
    }

    /**
     * @throws OtpException
     */
    protected function requestOtp(): void
    {
        app(OtpService::class)->send($this->phone, self::PURPOSE);

        $this->resendAvailableAt = now()->addSeconds((int) config('ippanel.otp.resend_after'))->timestamp;
    }

    protected function messageFor(OtpFailureReason $reason, int $retryAfter = 0): string
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

    public function render()
    {
        return $this->view()->title(__('auth.two-factor-auth').' - '.__('auth.user-dashboard').' | '.__('globals.viravach'));
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
                <img alt="Logo" src="{{asset('theme/1/media/logos/ViraVach-logo-3.png')}}" />
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
                @if ($step === 'phone')
                    <!--begin::phone form-->
                    <form class="form w-100 mb-13" wire:submit="sendCode">
                        <!--begin::Icon-->
                        <div class="text-center mb-10">
                            <img alt="Logo" class="mh-125px" src="{{asset('theme/1/media/svg/misc/smartphone-2.svg')}}" />
                        </div>
                        <!--end::Icon-->
                        <!--begin::Heading-->
                        <div class="text-center mb-10">
                            <h1 class="text-gray-900 mb-3">
                                {{ __('auth.two-factor-auth') }}
                            </h1>
                            <div class="text-muted fw-semibold fs-5 mb-5">
                                {{ __('auth.enter-phone-for-code') }}
                            </div>
                            <!--begin::Mobile no-->
                            <div class="fv-row mb-8 text-start">
                                <input type="tel" placeholder="{{ __('auth.phone-number') }} (09123456789)" wire:model="phone"
                                       autocomplete="tel"
                                       class="form-control bg-transparent @error('phone') is-invalid @enderror"/>
                                @error('phone')
                                    <div class="fv-plugins-message-container invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <!--end::Mobile no-->
                        </div>
                        <!--end::Heading-->
                        <!--begin::ثبت-->
                        <div class="d-flex flex-center mb-1">
                            <button type="submit" class="btn btn-lg btn-primary fw-bold"
                                    wire:loading.attr="disabled" wire:target="sendCode">
                                <span class="indicator-label" wire:loading.remove wire:target="sendCode">
                                    {{ __('auth.send-code') }}
                                </span>
                                <span class="indicator-progress" wire:loading.flex wire:target="sendCode" style="display: none;">
                                    {{ __('auth.please-wait') }}
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                        <!--end::ثبت-->
                        <div class="d-flex flex-center">
                            <a href="{{ route('auth.sign-in') }}" class="btn btn-outline btn-outline-primary">
                                {{ __('auth.login_with_password') }}
                            </a>
                        </div>
                    </form>
                    <!--end::phone form-->
                @else
                    <!--begin::code form-->
                    <form
                        class="form w-100 mb-13"
                        wire:submit="verifyCode"
                        x-data="{
                            digits: {{ $digits }},
                            resendAt: @entangle('resendAvailableAt'),
                            remaining: 0,
                            init() {
                                this.tick();
                                setInterval(() => this.tick(), 1000);
                            },
                            tick() {
                                this.remaining = this.resendAt
                                    ? Math.max(0, Math.ceil((this.resendAt * 1000 - Date.now()) / 1000))
                                    : 0;
                            },
                            focusNext(index) {
                                if (index < this.digits - 1) {
                                    this.$refs['digit' + (index + 1)]?.focus();
                                }
                            },
                            focusPrev(index) {
                                if (index > 0) {
                                    this.$refs['digit' + (index - 1)]?.focus();
                                }
                            },
                        }"
                    >
                        <!--begin::Icon-->
                        <div class="text-center mb-10">
                            <img alt="Logo" class="mh-125px" src="{{asset('theme/1/media/svg/misc/smartphone-2.svg')}}" />
                        </div>
                        <!--end::Icon-->
                        <!--begin::Heading-->
                        <div class="text-center mb-10">
                            <h1 class="text-gray-900 mb-3">
                                {{ __('auth.two-factor-auth') }}
                            </h1>
                            <div class="text-muted fw-semibold fs-5 mb-2">
                                {{ __('auth.enter-confirmation-code') }}
                            </div>
                            <div class="text-muted fw-semibold fs-6 mb-5">
                                {{ $phone }}
                                <button type="button" wire:click="backToPhone" class="btn btn-sm btn-link p-0 ms-2 align-baseline">
                                    {{ __('auth.change-phone-number') }}
                                </button>
                            </div>
                        </div>
                        <!--end::Heading-->
                        <!--begin::Section-->
                        <div class="mb-10">
                            <!--begin::Tags-->
                            <div class="fw-bold text-gray-900 fs-6 mb-1 ms-1">
                                {{ __('auth.type-digit-code', ['digits' => $digits]) }}
                            </div>
                            <!--end::Tags-->
                            <!--begin::Input group-->
                            <div class="d-flex flex-wrap flex-stack" dir="ltr">
                                @for ($i = 0; $i < $digits; $i++)
                                    <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1"
                                           x-ref="digit{{ $i }}"
                                           wire:model="code.{{ $i }}"
                                           x-on:input="$event.target.value.length > 0 && focusNext({{ $i }})"
                                           x-on:keydown.backspace="$event.target.value === '' && focusPrev({{ $i }})"
                                           class="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2 @error('code.'.$i) is-invalid @enderror" />
                                @endfor
                            </div>
                            <!--begin::Input group-->
                            @error('code')
                                <div class="fv-plugins-message-container invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <!--end::Section-->
                        <!--begin::ثبت-->
                        <div class="d-flex flex-center">
                            <button type="submit" class="btn btn-lg btn-primary fw-bold"
                                    wire:loading.attr="disabled" wire:target="verifyCode">
                                <span class="indicator-label" wire:loading.remove wire:target="verifyCode">
                                    {{ __('auth.verify') }}
                                </span>
                                <span class="indicator-progress" wire:loading.flex wire:target="verifyCode" style="display: none;">
                                    {{ __('auth.please-wait') }}
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                        <!--end::ثبت-->
                        <!--begin::Notice-->
                        <div class="text-center fw-semibold fs-5 mt-8">
                            <span class="text-muted me-1">
                                {{ __('auth.not-received-code') }}
                            </span>
                            (
                            <template x-if="remaining > 0">
                                <span class="text-muted">{{ $this->resendCountdownParts()[0] }}<span x-text="remaining"></span>{{ $this->resendCountdownParts()[1] }}</span>
                            </template>
                            <template x-if="remaining === 0">
                                <button type="button" wire:click="resendCode" wire:loading.attr="disabled" wire:target="resendCode"
                                        class="link-primary fs-5 me-1 btn btn-link p-0 border-0 align-baseline">
                                    {{ __('auth.resend') }}
                                </button>
                            </template>
                            <span class="text-muted me-1"> | </span>
                            <a href="#" class="link-primary fs-5">
                                {{ __('auth.contact-us') }}
                            </a>
                            )
                        </div>
                        @error('resend')
                            <div class="fv-plugins-message-container invalid-feedback d-block text-center">{{ $message }}</div>
                        @enderror
                        <!--end::Notice-->
                    </form>
                    <!--end::code form-->
                @endif
            </div>
            <!--end::Wrapper-->
            <!--begin::Footer-->
            <div class="d-flex flex-stack px-lg-10">
                <!--begin::languages-->
                <livewire:header-elements.tools.language-switcher />
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
