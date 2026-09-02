<?php

use Livewire\Component;

new class extends Component {
    public function signOut(): void
    {
        Auth::logout();

        //session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('home'));
    }
};
?>

<div class="d-flex align-items-center me-lg-n2 ms-1 ms-lg-3" id="kt_header_user_menu_toggle">
    <!--begin::Menu wrapper-->
    <div class="btn btn-icon btn-active-light-primary btn-custom w-30px h-30px w-md-40px h-md-40px"
         data-kt-menu-trigger="click" data-kt-menu-attach="parent"
         data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'bottom-end' : 'bottom-start' }}">
        <img class="h-30px w-30px rounded" src="{{asset('theme/1/media/avatars/user-avatar.png')}}" alt="User avatar"/>
    </div>
    <!--begin::user account menu-->
    @auth
        <div
            class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px"
            data-kt-menu="true">
            <!--begin::Menu item-->
            <div class="menu-item px-3">
                <div class="menu-content d-flex align-items-center px-3">
                    <!--begin::Avatar-->
                    <div class="symbol symbol-50px me-5">
                        <img src="{{asset('theme/1/media/avatars/user-avatar.png')}}" alt="User avatar"/>
                    </div>
                    <!--end::Avatar-->
                    <!--begin::user info-->
                    <div class="d-flex flex-column">
                        <div class="fw-bold d-flex align-items-center fs-5">
                            {{ auth()->user()->name }}
                            <span class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">
                            {{ auth()->user()->getRoleNames()->map(fn($role) => __("roles.{$role}"))->join('، ') }}
                            </span>
                        </div>
                        <a href="#" class="fw-semibold text-muted text-hover-primary fs-7">
                            {{ auth()->user()->email }}
                        </a>
                    </div>
                    <!--end::user info-->
                </div>
            </div>

            <!--end::Menu item-->
            <!--begin::Menu separator-->
            <div class="separator my-2"></div>
            <!--end::Menu separator-->
            <!--begin::Menu item-->
            @hasanyrole(['super_admin', 'shareholder'])
            {{-- The panel's own named route, never a hardcoded path. It is
                 registered with ->domain(config('domains.admin')) and
                 ->path('') in AdminPanelProvider, so route() resolves to the
                 admin host's root and follows any future host or path change
                 on its own. The previous href="/admin" was wrong twice over:
                 it stayed on whatever host rendered the menu (app.viravach.com
                 has no /admin route at all), and the panel has no /admin path
                 prefix to begin with. Matches how ⚡sign-in and ⚡secure-login
                 already redirect admins after login. --}}
            <div class="menu-item px-5">
                <a href="{{ route('filament.admin.pages.dashboard') }}" class="menu-link px-5">
                    {{ __('menu.admin_dashboard') }}
                </a>
            </div>
            @endhasanyrole

            <div class="menu-item px-5">
                <a href="{{route('dashboard')}}" class="menu-link px-5">
                    {{ __('menu.user_dashboard') }}
                </a>
            </div>
            <!--end::Menu item-->
            <!--begin::Menu item-->
            <div class="menu-item px-5">
                <a href="{{route('profile')}}" class="menu-link px-5">
                    {{ __('menu.my_profile') }}
                </a>
            </div>
            <!--end::Menu item-->
            {{-- DISABLED: the "more options" (گزینه‌های بیشتر) nested dropdown.
                 Leftover Metronic demo markup. Its links point at
                 account/referrals.html, account/billing.html and
                 account/statements.html — none of which exist under public/,
                 and none of which match any route — so every entry was a dead
                 link, with hardcoded Persian labels bypassing __(). The
                 notifications switch inside it is disabled along with the rest:
                 it is a bare checkbox with no wire:model and no enclosing form,
                 so it never persisted anything either.

                 Commented out with a Blade comment, not an HTML one, so none of
                 it reaches the browser. Kept in place rather than deleted so it
                 can be rebuilt as real functionality later. --}}
            {{--
            <!--begin::Menu item-->
            <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                 data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}"
                 data-kt-menu-offset="-15px, 0">
                <a href="#" class="menu-link px-5">
                    <span class="menu-title">
                        {{ __('menu.more_options') }}
                    </span>
                    <span class="menu-arrow"></span>
                </a>
                <!--begin::Menu sub-->
                <div class="menu-sub menu-sub-dropdown w-175px py-4">
                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <a href="account/referrals.html" class="menu-link px-5">مراجعات</a>
                    </div>
                    <!--end::Menu item-->
                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <a href="account/billing.html" class="menu-link px-5">صورتحساب</a>
                    </div>
                    <!--end::Menu item-->
                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <a href="account/statements.html" class="menu-link px-5">درگاه ها</a>
                    </div>
                    <!--end::Menu item-->
                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <a href="account/statements.html" class="menu-link d-flex flex-stack px-5">بیانه ها
                            <span class="ms-2 lh-0" data-bs-toggle="tooltip" title="اظهارات خود را نمایش دهید">
															<i class="ki-duotone ki-information-5 fs-5">
																<span class="path1"></span>
																<span class="path2"></span>
																<span class="path3"></span>
															</i>
														</span></a>
                    </div>
                    <!--end::Menu item-->
                    <!--begin::Menu separator-->
                    <div class="separator my-2"></div>
                    <!--end::Menu separator-->
                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <div class="menu-content px-3">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input w-30px h-20px" type="checkbox" value="1"
                                       checked="checked" name="notifications"/>
                                <span class="form-check-label text-muted fs-7">
                                    {{ __('menu.notifications') }}
                                </span>
                            </label>
                        </div>
                    </div>
                    <!--end::Menu item-->
                </div>
                <!--end::Menu sub-->
            </div>
            <!--end::Menu item-->
            --}}

            <!--begin::Menu separator-->
            <div class="separator my-2"></div>
            <!--end::Menu separator-->
            <!--begin::Menu item-->
            <div class="menu-item px-5 my-1">
                <a href="{{ route('settings') }}" class="menu-link px-5">
                    {{ __('menu.account_settings') }}
                </a>
            </div>
            <!--end::Menu item-->
            <!--begin::Menu item-->
            <div class="menu-item px-5">
                <span
                   class="menu-link px-5"
                   wire:click="signOut">
                    {{ __('menu.logout') }}
                </span>
            </div>
            <!--end::Menu item-->
        </div>

    @else
        <div
            class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px"
            data-kt-menu="true">

            <!--begin::Menu item-->
            <div class="menu-item px-5">
                <a href="{{route('auth.sign-in')}}" class="menu-link px-5">
                    {{ __('auth.sign-in') }}
                </a>
            </div>
            <!--end::Menu item-->

            <!--begin::Menu item-->
            <div class="menu-item px-5">
                <a href="{{ route('auth.sign-up') }}" class="menu-link px-5">
                    {{ __('auth.sign-up') }}
                </a>
            </div>
            <!--end::Menu item-->
        </div>
    @endauth

    <!--end::user account menu-->
    <!--end::Menu wrapper-->
</div>
