<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="card mb-5 mb-xl-10">
    <div class="card-body pt-9 pb-0">
        <!--begin::Details-->
        <div class="d-flex flex-wrap flex-sm-nowrap">
            <!--begin: Pic-->
            <div class="me-7 mb-4">
                <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
                    <img src="{{ auth()->user()->getFirstMediaUrl('avatar', 'webp') ?: asset('theme/1/media/avatars/avatar-placeholder.png') }}" alt="image">
                    <div class="position-absolute translate-middle bottom-0 start-100 mb-6 bg-success rounded-circle border border-4 border-body h-20px w-20px"></div>
                </div>
            </div>
            <!--end::Pic-->
            <!--begin::Info-->
            <div class="flex-grow-1">
                <!--begin::Title-->
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                    <!--begin::user-->
                    <div class="d-flex flex-column">
                        <!--begin::نام-->
                        <div class="d-flex align-items-center mb-2">
                            <a href="#" class="text-gray-900 text-hover-primary fs-2 fw-bold me-1">
                                {{ auth()->user()->name . ' ' . auth()->user()->family }}
                            </a>
                            <a href="#">
                                <i class="ki-duotone ki-verify fs-1 text-primary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </a>
                        </div>
                        <!--end::نام-->
                        <!--begin::Info-->
                        <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                            <a href="#" class="d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2">
                                <i class="ki-duotone ki-profile-circle fs-4 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                                {{ auth()->user()->getRoleNames()->map(fn($role) => __("roles.{$role}"))->join('، ') }}
                            </a>
                            <a href="#" class="d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2">
                                <i class="ki-duotone ki-geolocation fs-4 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                {{ auth()->user()->profile?->country?->name ?? __('menu.not_registered') }}
                            </a>
                            <span href="#" class="d-flex align-items-center text-gray-500 text-hover-primary mb-2">
                                        <i class="ki-duotone ki-sms fs-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        {{ auth()->user()->email }}
                                    </span>
                        </div>
                        <!--end::Info-->
                    </div>
                    <!--end::user-->
                    <!--begin::Actions-->
                    <div class="d-flex my-4">
                        <a href="{{ route('profile') }}" class="btn btn-sm btn-primary me-3">
                            {{ __('menu.settings') }}
                        </a>
                    </div>
                    <!--end::Actions-->
                </div>
                <!--end::Title-->
                <!--begin::Stats-->
                <div class="d-flex flex-wrap flex-stack">
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-column flex-grow-1 pe-8">
                        <!--begin::Stats-->
                        <div class="d-flex flex-wrap">
                            <!--begin::Stat-->
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                <!--begin::شماره کارت-->
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-arrow-up fs-3 text-success me-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <div class="fs-2 fw-bold counted" data-kt-countup="true" data-kt-countup-value="4500" data-kt-countup-prefix="$" data-kt-initialized="1">$4,500</div>
                                </div>
                                <!--end::شماره کارت-->
                                <!--begin::Tags-->
                                <div class="fw-semibold fs-6 text-gray-500">درآمد</div>
                                <!--end::Tags-->
                            </div>
                            <!--end::Stat-->
                            <!--begin::Stat-->
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                <!--begin::شماره کارت-->
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-arrow-down fs-3 text-danger me-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <div class="fs-2 fw-bold counted" data-kt-countup="true" data-kt-countup-value="80" data-kt-initialized="1">80</div>
                                </div>
                                <!--end::شماره کارت-->
                                <!--begin::Tags-->
                                <div class="fw-semibold fs-6 text-gray-500">پروژه ها</div>
                                <!--end::Tags-->
                            </div>
                            <!--end::Stat-->
                            <!--begin::Stat-->
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                <!--begin::شماره کارت-->
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-arrow-up fs-3 text-success me-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <div class="fs-2 fw-bold counted" data-kt-countup="true" data-kt-countup-value="60" data-kt-countup-prefix="%" data-kt-initialized="1">%60</div>
                                </div>
                                <!--end::شماره کارت-->
                                <!--begin::Tags-->
                                <div class="fw-semibold fs-6 text-gray-500">نرخ موفقیت</div>
                                <!--end::Tags-->
                            </div>
                            <!--end::Stat-->
                        </div>
                        <!--end::Stats-->
                    </div>
                    <!--end::Wrapper-->
                    <!--begin::پردازش-->
                    <livewire:dashboard-elements.profile-progress />
                    <!--end::پردازش-->
                </div>
                <!--end::Stats-->
            </div>
            <!--end::Info-->
        </div>
        <!--end::Details-->
        <!--begin::Navs-->
        <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
            <!--begin::Nav item-->
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    {{ __('menu.user_dashboard') }}
                </a>
            </li>
            <!--end::Nav item-->
            <!--begin::Nav item-->
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('my-companies') ? 'active' : '' }}" href="{{ route('my-companies') }}">
                    {{ __('menu.my_companies') }}
                </a>
            </li>
            <!--end::Nav item-->
            <!--begin::Nav item-->
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('subscriptions') ? 'active' : '' }}" href="{{ route('subscriptions') }}">
                    {{ __('menu.subscriptions_and_plans') }}
                </a>
            </li>
            <!--end::Nav item-->
            <!--begin::Nav item-->
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('payments') ? 'active' : '' }}" href="{{ route('payments') }}">
                    {{ __('menu.payment_history') }}
                </a>
            </li>
            <!--end::Nav item-->
            <!--begin::Nav item-->
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('profile') ? 'active' : '' }}" href="{{ route('profile') }}">
                    {{ __('menu.my_profile') }}
                </a>
            </li>
            <!--end::Nav item-->
            <!--begin::Nav item-->
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('company-views') ? 'active' : '' }}" href="{{ route('company-views') }}">
                    {{ __('menu.company_visit_statistics') }}
                </a>
            </li>
            <!--end::Nav item-->
            <!--begin::Nav item-->
            <li class="nav-item mt-2">
                <a class="nav-link text-active-primary ms-0 me-10 py-5 {{ request()->routeIs('chat') ? 'active' : '' }}" href="{{ route('chat') }}">
                    {{ __('menu.chat') }}
                </a>
            </li>
            <!--end::Nav item-->
        </ul>
        <!--begin::Navs-->
    </div>
</div>
