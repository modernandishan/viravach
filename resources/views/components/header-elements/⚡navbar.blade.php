<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div class="d-flex align-items-stretch" id="kt_header_nav">
    <!--begin::Menu wrapper-->
    <div class="header-menu align-items-stretch" data-kt-drawer="true" data-kt-drawer-name="header-menu"
         data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true"
         data-kt-drawer-width="{default:'200px', '300px': '250px'}" data-kt-drawer-direction="start"
         data-kt-drawer-toggle="#kt_header_menu_mobile_toggle" data-kt-swapper="true" data-kt-swapper-mode="prepend"
         data-kt-swapper-parent="{default: '#kt_body', lg: '#kt_header_nav'}">
        <!--begin::Menu-->
        <div
            class="menu menu-rounded menu-column menu-lg-row menu-active-bg menu-title-gray-700 menu-state-primary menu-arrow-gray-500 fw-semibold my-5 my-lg-0 align-items-stretch px-2 px-lg-0"
            id="#kt_header_menu" data-kt-menu="true">

            <!--begin:Menu item - Home page (simple)-->
            <div
                data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'bottom-end' : 'bottom-start' }}"
                class="menu-item {{ request()->routeIs('home') ? 'here show menu-here-bg' : '' }} me-0 me-lg-2">
                <!--begin:Menu link-->
                <a href="{{route('home')}}" class="menu-link py-3">
                    <span class="menu-title">
                        {{ __('globals.viravach') }}
                    </span>
                </a>
                <!--end:Menu link-->
            </div>
            <!--end:Menu item - Home page (simple)-->

            <!--begin:Menu item (simple)-->
            <div
                data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'bottom-end' : 'bottom-start' }}"
                class="menu-item {{ request()->routeIs('pricing') ? 'here show menu-here-bg' : '' }} me-0 me-lg-2">
                <!--begin:Menu link-->
                <a href="{{route('pricing')}}" class="menu-link py-3">
                    <span class="menu-title">
                        {{ __('breadcrumbs.pricing') }}
                    </span>
                </a>
                <!--end:Menu link-->
            </div>
            <!--end:Menu item (simple)-->

            <!--begin:Menu item-->
            <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                 data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'bottom-end' : 'bottom-start' }}"
                 class="menu-item menu-lg-down-accordion me-0 me-lg-2">
                <!--begin:Menu link-->
                <span class="menu-link py-3">
                    <span class="menu-title">
                        {{ __('menu.export_directory') }}
                    </span>
                    <span class="menu-arrow d-lg-none"></span>
                </span>
                <!--end:Menu link-->
                <!--begin:Menu sub-->
                <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown p-0 w-100 w-lg-850px">
                    <!--begin:داشبورد ها menu-->
                    <div class="menu-state-bg menu-extended overflow-hidden overflow-lg-visible"
                         data-kt-menu-dismiss="true">
                        <!--begin:Row-->
                        <div class="row">
                            <!--begin:Col-->
                            <livewire:header-elements.menu-items.main-company-categories />
                            <!--end:Col-->
                            <!--begin:Col-->
                            <div class="menu-more bg-light col-lg-4 py-3 px-3 py-lg-6 px-lg-6 rounded-end">
                                <!--begin:Heading-->
                                <h4 class="fs-6 fs-lg-4 text-gray-800 fw-bold mt-3 mb-3 ms-4">دسته های پر بازدید</h4>
                                <!--end:Heading-->
                                <!--begin:Menu item-->
                                <div class="menu-item p-0 m-0">
                                    <!--begin:Menu link-->
                                    <a href="dashboards/logistics.html" class="menu-link py-2">
                                        <span class="menu-title">

                                        </span>
                                    </a>
                                    <!--end:Menu link-->
                                </div>
                                <!--end:Menu item-->
                            </div>
                            <!--end:Col-->
                        </div>
                        <!--end:Row-->
                    </div>
                    <!--end:داشبورد ها menu-->
                </div>
                <!--end:Menu sub-->
            </div>
            <!--end:Menu item-->
            <!--begin:Menu item-->
            {{--<div data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                 data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'bottom-end' : 'bottom-start' }}"
                 class="menu-item menu-lg-down-accordion me-0 me-lg-2">
                <!--begin:Menu link-->
                <span class="menu-link py-3">
													<span class="menu-title">صفحات</span>
													<span class="menu-arrow d-lg-none"></span>
												</span>
                <!--end:Menu link-->
                <!--begin:Menu sub-->
                <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown p-0">
                    <!--begin:صفحات menu-->
                    <div class="menu-active-bg px-4 px-lg-0">
                        <!--begin:Tabs nav-->
                        <div class="d-flex w-100 overflow-auto">
                            <ul class="nav nav-stretch nav-line-tabs fw-bold fs-6 p-0 p-lg-10 flex-nowrap flex-grow-1">
                                <!--begin:Nav item-->
                                <li class="nav-item mx-lg-1">
                                    <a class="nav-link py-3 py-lg-6 active text-active-primary" href="#"
                                       data-bs-toggle="tab" data-bs-target="#kt_app_header_menu_pages_pages">عمومی</a>
                                </li>
                                <!--end:Nav item-->
                                <!--begin:Nav item-->
                                <li class="nav-item mx-lg-1">
                                    <a class="nav-link py-3 py-lg-6 text-active-primary" href="#" data-bs-toggle="tab"
                                       data-bs-target="#kt_app_header_menu_pages_account">اکانت</a>
                                </li>
                                <!--end:Nav item-->
                                <!--begin:Nav item-->
                                <li class="nav-item mx-lg-1">
                                    <a class="nav-link py-3 py-lg-6 text-active-primary" href="#" data-bs-toggle="tab"
                                       data-bs-target="#kt_app_header_menu_pages_authentication">احراز هویت</a>
                                </li>
                                <!--end:Nav item-->
                                <!--begin:Nav item-->
                                <li class="nav-item mx-lg-1">
                                    <a class="nav-link py-3 py-lg-6 text-active-primary" href="#" data-bs-toggle="tab"
                                       data-bs-target="#kt_app_header_menu_pages_utilities">ابزارها</a>
                                </li>
                                <!--end:Nav item-->
                                <!--begin:Nav item-->
                                <li class="nav-item mx-lg-1">
                                    <a class="nav-link py-3 py-lg-6 text-active-primary" href="#" data-bs-toggle="tab"
                                       data-bs-target="#kt_app_header_menu_pages_widgets">ابزارک</a>
                                </li>
                                <!--end:Nav item-->
                            </ul>
                        </div>
                        <!--end:Tabs nav-->
                        <!--begin:Tab content-->
                        <div class="tab-content py-4 py-lg-8 px-lg-7">
                            <!--begin:Tab pane-->
                            <div class="tab-pane active w-lg-1000px" id="kt_app_header_menu_pages_pages">
                                <!--begin:Row-->
                                <div class="row">
                                    <!--begin:Col-->
                                    <div class="col-lg-8">
                                        <!--begin:Row-->
                                        <div class="row">
                                            <!--begin:Col-->
                                            <div class="col-lg-3 mb-6 mb-lg-0">
                                                <!--begin:Menu heading-->
                                                <h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">کاربر پروفایل</h4>
                                                <!--end:Menu heading-->
                                                <!--begin:Menu item-->
                                                <div class="menu-item p-0 m-0">
                                                    <!--begin:Menu link-->
                                                    <a href="pages/user-profile/overview.html" class="menu-link">
                                                        <span class="menu-title">بررسی اجمالی</span>
                                                    </a>
                                                    <!--end:Menu link-->
                                                </div>
                                                <!--end:Menu item-->
                                                <!--begin:Menu item-->
                                                <div class="menu-item p-0 m-0">
                                                    <!--begin:Menu link-->
                                                    <a href="pages/user-profile/projects.html" class="menu-link">
                                                        <span class="menu-title">پروژه ها</span>
                                                    </a>
                                                    <!--end:Menu link-->
                                                </div>
                                                <!--end:Menu item-->
                                                <!--begin:Menu item-->
                                                <div class="menu-item p-0 m-0">
                                                    <!--begin:Menu link-->
                                                    <a href="pages/user-profile/campaigns.html" class="menu-link">
                                                        <span class="menu-title">کمپین ها</span>
                                                    </a>
                                                    <!--end:Menu link-->
                                                </div>
                                                <!--end:Menu item-->
                                                <!--begin:Menu item-->
                                                <div class="menu-item p-0 m-0">
                                                    <!--begin:Menu link-->
                                                    <a href="pages/user-profile/documents.html" class="menu-link">
                                                        <span class="menu-title">اسناد</span>
                                                    </a>
                                                    <!--end:Menu link-->
                                                </div>
                                                <!--end:Menu item-->
                                                <!--begin:Menu item-->
                                                <div class="menu-item p-0 m-0">
                                                    <!--begin:Menu link-->
                                                    <a href="pages/user-profile/followers.html" class="menu-link">
                                                        <span class="menu-title">فالوورها</span>
                                                    </a>
                                                    <!--end:Menu link-->
                                                </div>
                                                <!--end:Menu item-->
                                                <!--begin:Menu item-->
                                                <div class="menu-item p-0 m-0">
                                                    <!--begin:Menu link-->
                                                    <a href="pages/user-profile/activity.html" class="menu-link">
                                                        <span class="menu-title">فعالیت</span>
                                                    </a>
                                                    <!--end:Menu link-->
                                                </div>
                                                <!--end:Menu item-->
                                            </div>
                                            <!--end:Col-->
                                            <!--begin:Col-->
                                            <div class="col-lg-3 mb-6 mb-lg-0">
                                                <!--begin:Menu section-->
                                                <div class="mb-6">
                                                    <!--begin:Menu heading-->
                                                    <h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4"></h4>
                                                    <!--end:Menu heading-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="pages/about.html" class="menu-link">
                                                            <span class="menu-title">درباره ی ما</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="pages/team.html" class="menu-link">
                                                            <span class="menu-title">تیم ما</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="pages/contact.html" class="menu-link">
                                                            <span class="menu-title">تماس با ما</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="pages/licenses.html" class="menu-link">
                                                            <span class="menu-title">مجوزها</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="pages/sitemap.html" class="menu-link">
                                                            <span class="menu-title">نقشه سایت</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                </div>
                                                <!--end:Menu section-->
                                                <!--begin:Menu section-->
                                                <div class="mb-0">
                                                    <!--begin:Menu heading-->
                                                    <h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">مشاغل</h4>
                                                    <!--end:Menu heading-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="pages/careers/list.html" class="menu-link">
                                                            <span class="menu-title">لیست مشاغل</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="pages/careers/apply.html" class="menu-link">
                                                            <span class="menu-title">مشاغل تایید</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                </div>
                                                <!--end:Menu section-->
                                            </div>
                                            <!--end:Col-->
                                            <!--begin:Col-->
                                            <div class="col-lg-3 mb-6 mb-lg-0">
                                                <!--begin:Menu section-->
                                                <div class="mb-6">
                                                    <!--begin:Menu heading-->
                                                    <h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">سوالات متداول</h4>
                                                    <!--end:Menu heading-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="pages/faq/classic.html" class="menu-link">
                                                            <span class="menu-title">سوالات متداول کلاسیک</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="pages/faq/extended.html" class="menu-link">
                                                            <span class="menu-title">سوالات متداول تمدید شده</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                </div>
                                                <!--end:Menu section-->
                                                <!--begin:Menu section-->
                                                <div class="mb-6">
                                                    <!--begin:Menu heading-->
                                                    <h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">مقالات</h4>
                                                    <!--end:Menu heading-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="pages/blog/home.html" class="menu-link">
                                                            <span class="menu-title">مقالات خانه</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="pages/blog/post.html" class="menu-link">
                                                            <span class="menu-title">صفحه تکی مقالات</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                </div>
                                                <!--end:Menu section-->
                                                <!--begin:Menu section-->
                                                <div class="mb-0">
                                                    <!--begin:Menu heading-->
                                                    <h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">قیمت گذاری</h4>
                                                    <!--end:Menu heading-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="pages/pricing.html" class="menu-link">
                                                            <span class="menu-title">قیمت گذاری ستونی</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="pages/pricing/table.html" class="menu-link">
                                                            <span class="menu-title">جدول قیمت گذاری</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                </div>
                                                <!--end:Menu section-->
                                            </div>
                                            <!--end:Col-->
                                            <!--begin:Col-->
                                            <div class="col-lg-3 mb-6 mb-lg-0">
                                                <!--begin:Menu section-->
                                                <div class="mb-0">
                                                    <!--begin:Menu heading-->
                                                    <h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">سوشیال</h4>
                                                    <!--end:Menu heading-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="pages/social/feeds.html" class="menu-link">
                                                            <span class="menu-title">تغذیه</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="pages/social/activity.html" class="menu-link">
                                                            <span class="menu-title">فعالیت</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="pages/social/followers.html" class="menu-link">
                                                            <span class="menu-title">فالوورها</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="pages/social/settings.html" class="menu-link">
                                                            <span class="menu-title">تنظیمات</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                </div>
                                                <!--end:Menu section-->
                                            </div>
                                            <!--end:Col-->
                                        </div>
                                        <!--end:Row-->
                                    </div>
                                    <!--end:Col-->
                                    <!--begin:Col-->
                                    <div class="col-lg-4">
                                        <img src="{{ asset('theme/1/media/stock/600x600/img-82.jpg') }}" class="rounded mw-100"
                                             alt=""/>
                                    </div>
                                    <!--end:Col-->
                                </div>
                                <!--end:Row-->
                            </div>
                            <!--end:Tab pane-->
                            <!--begin:Tab pane-->
                            <div class="tab-pane w-lg-600px" id="kt_app_header_menu_pages_account">
                                <!--begin:Row-->
                                <div class="row">
                                    <!--begin:Col-->
                                    <div class="col-lg-5 mb-6 mb-lg-0">
                                        <!--begin:Row-->
                                        <div class="row">
                                            <!--begin:Col-->
                                            <div class="col-lg-6">
                                                <!--begin:Menu item-->
                                                <div class="menu-item p-0 m-0">
                                                    <!--begin:Menu link-->
                                                    <a href="account/overview.html" class="menu-link">
                                                        <span class="menu-title">بررسی اجمالی</span>
                                                    </a>
                                                    <!--end:Menu link-->
                                                </div>
                                                <!--end:Menu item-->
                                                <!--begin:Menu item-->
                                                <div class="menu-item p-0 m-0">
                                                    <!--begin:Menu link-->
                                                    <a href="account/settings.html" class="menu-link">
                                                        <span class="menu-title">تنظیمات</span>
                                                    </a>
                                                    <!--end:Menu link-->
                                                </div>
                                                <!--end:Menu item-->
                                                <!--begin:Menu item-->
                                                <div class="menu-item p-0 m-0">
                                                    <!--begin:Menu link-->
                                                    <a href="account/security.html" class="menu-link">
                                                        <span class="menu-title">امنیت</span>
                                                    </a>
                                                    <!--end:Menu link-->
                                                </div>
                                                <!--end:Menu item-->
                                                <!--begin:Menu item-->
                                                <div class="menu-item p-0 m-0">
                                                    <!--begin:Menu link-->
                                                    <a href="account/activity.html" class="menu-link">
                                                        <span class="menu-title">فعالیت</span>
                                                    </a>
                                                    <!--end:Menu link-->
                                                </div>
                                                <!--end:Menu item-->
                                                <!--begin:Menu item-->
                                                <div class="menu-item p-0 m-0">
                                                    <!--begin:Menu link-->
                                                    <a href="account/billing.html" class="menu-link">
                                                        <span class="menu-title">صورتحساب</span>
                                                    </a>
                                                    <!--end:Menu link-->
                                                </div>
                                                <!--end:Menu item-->
                                            </div>
                                            <!--end:Col-->
                                            <!--begin:Col-->
                                            <div class="col-lg-6">
                                                <!--begin:Menu item-->
                                                <div class="menu-item p-0 m-0">
                                                    <!--begin:Menu link-->
                                                    <a href="account/statements.html" class="menu-link">
                                                        <span class="menu-title">بیانه ها</span>
                                                    </a>
                                                    <!--end:Menu link-->
                                                </div>
                                                <!--end:Menu item-->
                                                <!--begin:Menu item-->
                                                <div class="menu-item p-0 m-0">
                                                    <!--begin:Menu link-->
                                                    <a href="account/referrals.html" class="menu-link">
                                                        <span class="menu-title">مراجعات</span>
                                                    </a>
                                                    <!--end:Menu link-->
                                                </div>
                                                <!--end:Menu item-->
                                                <!--begin:Menu item-->
                                                <div class="menu-item p-0 m-0">
                                                    <!--begin:Menu link-->
                                                    <a href="account/api-keys.html" class="menu-link">
                                                        <span class="menu-title">کلید ای پی ای</span>
                                                    </a>
                                                    <!--end:Menu link-->
                                                </div>
                                                <!--end:Menu item-->
                                                <!--begin:Menu item-->
                                                <div class="menu-item p-0 m-0">
                                                    <!--begin:Menu link-->
                                                    <a href="account/logs.html" class="menu-link">
                                                        <span class="menu-title">گزارش</span>
                                                    </a>
                                                    <!--end:Menu link-->
                                                </div>
                                                <!--end:Menu item-->
                                            </div>
                                            <!--end:Col-->
                                        </div>
                                        <!--end:Row-->
                                    </div>
                                    <!--end:Col-->
                                    <!--begin:Col-->
                                    <div class="col-lg-7">
                                        <img src="{{ asset('theme/1/media/stock/900x600/46.jpg') }}" class="rounded mw-100" alt=""/>
                                    </div>
                                    <!--end:Col-->
                                </div>
                                <!--end:Row-->
                            </div>
                            <!--end:Tab pane-->
                            <!--begin:Tab pane-->
                            <div class="tab-pane w-lg-1000px" id="kt_app_header_menu_pages_authentication">
                                <!--begin:Row-->
                                <div class="row">
                                    <!--begin:Col-->
                                    <div class="col-lg-3 mb-6 mb-lg-0">
                                        <!--begin:Menu section-->
                                        <div class="mb-6">
                                            <!--begin:Menu heading-->
                                            <h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4"> قالب بندی</h4>
                                            <!--end:Menu heading-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/corporate/sign-in.html"
                                                   class="menu-link">
                                                    <span class="menu-title">ورود</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/corporate/sign-up.html"
                                                   class="menu-link">
                                                    <span class="menu-title">ثبت نام</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/corporate/two-factor.html"
                                                   class="menu-link">
                                                    <span class="menu-title">دو مرحله ای</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/corporate/reset-password.html"
                                                   class="menu-link">
                                                    <span class="menu-title">ریست کلمه عبور</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/corporate/new-password.html"
                                                   class="menu-link">
                                                    <span class="menu-title">کلمه عبور جدید</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                        </div>
                                        <!--end:Menu section-->
                                        <!--begin:Menu section-->
                                        <div class="mb-0">
                                            <!--begin:Menu heading-->
                                            <h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">قالب بندی</h4>
                                            <!--end:Menu heading-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/overlay/sign-in.html" class="menu-link">
                                                    <span class="menu-title">ورود</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/overlay/sign-up.html" class="menu-link">
                                                    <span class="menu-title">ثبت نام</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/overlay/two-factor.html"
                                                   class="menu-link">
                                                    <span class="menu-title">دو مرحله ای</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/overlay/reset-password.html"
                                                   class="menu-link">
                                                    <span class="menu-title">ریست کلمه عبور</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/overlay/new-password.html"
                                                   class="menu-link">
                                                    <span class="menu-title">کلمه عبور جدید</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                        </div>
                                        <!--end:Menu section-->
                                    </div>
                                    <!--end:Col-->
                                    <!--begin:Col-->
                                    <div class="col-lg-3 mb-6 mb-lg-0">
                                        <!--begin:Menu section-->
                                        <div class="mb-6">
                                            <!--begin:Menu heading-->
                                            <h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">قالب بندی خلاقانه</h4>
                                            <!--end:Menu heading-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/creative/sign-in.html"
                                                   class="menu-link">
                                                    <span class="menu-title">ورود</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/creative/sign-up.html"
                                                   class="menu-link">
                                                    <span class="menu-title">ثبت نام</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/creative/two-factor.html"
                                                   class="menu-link">
                                                    <span class="menu-title">دو مرحله ای</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/creative/reset-password.html"
                                                   class="menu-link">
                                                    <span class="menu-title">ریست کلمه عبور</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/creative/new-password.html"
                                                   class="menu-link">
                                                    <span class="menu-title">کلمه عبور جدید</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                        </div>
                                        <!--end:Menu section-->
                                        <!--begin:Menu section-->
                                        <div class="mb-6">
                                            <!--begin:Menu heading-->
                                            <h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">قالب بندی فانتزی</h4>
                                            <!--end:Menu heading-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/fancy/sign-in.html" class="menu-link">
                                                    <span class="menu-title">ورود</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/fancy/sign-up.html" class="menu-link">
                                                    <span class="menu-title">ثبت نام</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/fancy/two-factor.html"
                                                   class="menu-link">
                                                    <span class="menu-title">دو مرحله ای</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/fancy/reset-password.html"
                                                   class="menu-link">
                                                    <span class="menu-title">ریست کلمه عبور</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/layouts/fancy/new-password.html"
                                                   class="menu-link">
                                                    <span class="menu-title">کلمه عبور جدید</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                        </div>
                                        <!--end:Menu section-->
                                    </div>
                                    <!--end:Col-->
                                    <!--begin:Col-->
                                    <div class="col-lg-3 mb-6 mb-lg-0">
                                        <!--begin:Menu section-->
                                        <div class="mb-0">
                                            <!--begin:Menu heading-->
                                            <h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">عمومی</h4>
                                            <!--end:Menu heading-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/extended/multi-steps-sign-up.html"
                                                   class="menu-link">
                                                    <span class="menu-title">ثبت نام چند مرحله ای</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/general/welcome.html" class="menu-link">
                                                    <span class="menu-title">پیام خوش امد گویی</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/general/verify-email.html" class="menu-link">
                                                    <span class="menu-title">تایید ایمیل</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/general/coming-soon.html" class="menu-link">
                                                    <span class="menu-title">بزودی</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/general/password-confirmation.html"
                                                   class="menu-link">
                                                    <span class="menu-title">تایید کلمه عبور</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/general/account-deactivated.html"
                                                   class="menu-link">
                                                    <span class="menu-title">اکانت غیرفعال</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/general/error-404.html" class="menu-link">
                                                    <span class="menu-title">خطایی 404</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/general/error-500.html" class="menu-link">
                                                    <span class="menu-title">خطایی 500</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                        </div>
                                        <!--end:Menu section-->
                                    </div>
                                    <!--end:Col-->
                                    <!--begin:Col-->
                                    <div class="col-lg-3 mb-6 mb-lg-0">
                                        <!--begin:Menu section-->
                                        <div class="mb-0">
                                            <!--begin:Menu heading-->
                                            <h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">قالب های ایمیل</h4>
                                            <!--end:Menu heading-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/email/welcome-message.html" class="menu-link">
                                                    <span class="menu-title">پیام خوش امد گویی</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/email/reset-password.html" class="menu-link">
                                                    <span class="menu-title">ریست کلمه عبور</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/email/subscription-confirmed.html"
                                                   class="menu-link">
                                                    <span class="menu-title">اشتراک تایید شده</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/email/card-declined.html" class="menu-link">
                                                    <span class="menu-title">کارت اعتباری رد شده</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/email/promo-1.html" class="menu-link">
                                                    <span class="menu-title">حرفه ای  1</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/email/promo-2.html" class="menu-link">
                                                    <span class="menu-title">حرفه ای  2</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                            <!--begin:Menu item-->
                                            <div class="menu-item p-0 m-0">
                                                <!--begin:Menu link-->
                                                <a href="authentication/email/promo-3.html" class="menu-link">
                                                    <span class="menu-title">حرفه ای  3</span>
                                                </a>
                                                <!--end:Menu link-->
                                            </div>
                                            <!--end:Menu item-->
                                        </div>
                                        <!--end:Menu section-->
                                    </div>
                                    <!--end:Col-->
                                </div>
                                <!--end:Row-->
                            </div>
                            <!--end:Tab pane-->
                            <!--begin:Tab pane-->
                            <div class="tab-pane w-lg-1000px" id="kt_app_header_menu_pages_utilities">
                                <!--begin:Row-->
                                <div class="row">
                                    <!--begin:Col-->
                                    <div class="col-lg-7">
                                        <!--begin:Row-->
                                        <div class="row">
                                            <!--begin:Col-->
                                            <div class="col-lg-4 mb-6 mb-lg-0">
                                                <!--begin:Menu section-->
                                                <div class="mb-0">
                                                    <!--begin:Menu heading-->
                                                    <h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">عمومی مودال ها</h4>
                                                    <!--end:Menu heading-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/general/invite-friends.html"
                                                           class="menu-link">
                                                            <span class="menu-title">دعوت از دوستان</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/general/view-users.html"
                                                           class="menu-link">
                                                            <span class="menu-title">نمایش کاربران</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/general/select-users.html"
                                                           class="menu-link">
                                                            <span class="menu-title">انتخاب کاربران</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/general/upgrade-plan.html"
                                                           class="menu-link">
                                                            <span class="menu-title">ارتقا طرح</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/general/share-earn.html"
                                                           class="menu-link">
                                                            <span class="menu-title">به اشتراک بگذارید و کسب کنید</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/forms/new-target.html"
                                                           class="menu-link">
                                                            <span class="menu-title">هدف جدید</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/forms/new-card.html"
                                                           class="menu-link">
                                                            <span class="menu-title">کارت جدید</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/forms/new-address.html"
                                                           class="menu-link">
                                                            <span class="menu-title">آدرس های جدید</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/forms/create-api-key.html"
                                                           class="menu-link">
                                                            <span class="menu-title">ساختن ای پی آی</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/forms/bidding.html" class="menu-link">
                                                            <span class="menu-title">مناقصه</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                </div>
                                                <!--end:Menu section-->
                                            </div>
                                            <!--end:Col-->
                                            <!--begin:Col-->
                                            <div class="col-lg-4 mb-6 mb-lg-0">
                                                <!--begin:Menu section-->
                                                <div class="mb-6">
                                                    <!--begin:Menu heading-->
                                                    <h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">پیشرفته مودال ها</h4>
                                                    <!--end:Menu heading-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/wizards/create-app.html"
                                                           class="menu-link">
                                                            <span class="menu-title">ساختن اپ</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/wizards/create-campaign.html"
                                                           class="menu-link">
                                                            <span class="menu-title">ساختن کمپین</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/wizards/create-account.html"
                                                           class="menu-link">
                                                            <span class="menu-title">ساختن بیزینس </span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/wizards/create-project.html"
                                                           class="menu-link">
                                                            <span class="menu-title">ساختن پروژه</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/wizards/top-up-wallet.html"
                                                           class="menu-link">
                                                            <span class="menu-title">کیف پول</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/wizards/offer-a-deal.html"
                                                           class="menu-link">
                                                            <span class="menu-title">پیشنهاد معامله</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/wizards/two-factor-authentication.html"
                                                           class="menu-link">
                                                            <span class="menu-title">احراز هویت دومرحله ای</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                </div>
                                                <!--end:Menu section-->
                                                <!--begin:Menu section-->
                                                <div class="mb-0">
                                                    <!--begin:Menu heading-->
                                                    <h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">جستجو</h4>
                                                    <!--end:Menu heading-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/search/horizontal.html" class="menu-link">
                                                            <span class="menu-title">افقی</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/search/vertical.html" class="menu-link">
                                                            <span class="menu-title">عمودی</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/search/users.html" class="menu-link">
                                                            <span class="menu-title">کاربران</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/search/select-location.html"
                                                           class="menu-link">
                                                            <span class="menu-title">مکان را انتخاب کنید</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                </div>
                                                <!--end:Menu section-->
                                            </div>
                                            <!--end:Col-->
                                            <!--begin:Col-->
                                            <div class="col-lg-4 mb-6 mb-lg-0">
                                                <!--begin:Menu section-->
                                                <div class="mb-0">
                                                    <!--begin:Menu heading-->
                                                    <h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">ویزارد</h4>
                                                    <!--end:Menu heading-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/wizards/horizontal.html" class="menu-link">
                                                            <span class="menu-title">افقی</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/wizards/vertical.html" class="menu-link">
                                                            <span class="menu-title">عمودی</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/wizards/two-factor-authentication.html"
                                                           class="menu-link">
                                                            <span class="menu-title">احراز هویت دومرحله ای</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/wizards/create-app.html" class="menu-link">
                                                            <span class="menu-title">ساختن اپ</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/wizards/create-campaign.html"
                                                           class="menu-link">
                                                            <span class="menu-title">ساختن کمپین</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/wizards/create-account.html"
                                                           class="menu-link">
                                                            <span class="menu-title">ساختن اکانت</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/wizards/create-project.html"
                                                           class="menu-link">
                                                            <span class="menu-title">ساختن پروژه</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/modals/wizards/top-up-wallet.html"
                                                           class="menu-link">
                                                            <span class="menu-title">کیف پول</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                    <!--begin:Menu item-->
                                                    <div class="menu-item p-0 m-0">
                                                        <!--begin:Menu link-->
                                                        <a href="utilities/wizards/offer-a-deal.html" class="menu-link">
                                                            <span class="menu-title">پیشنهاد معامله</span>
                                                        </a>
                                                        <!--end:Menu link-->
                                                    </div>
                                                    <!--end:Menu item-->
                                                </div>
                                                <!--end:Menu section-->
                                            </div>
                                            <!--end:Col-->
                                        </div>
                                        <!--end:Row-->
                                    </div>
                                    <!--end:Col-->
                                    <!--begin:Col-->
                                    <div class="col-lg-5 pe-lg-5">
                                        <img src="{{ asset('theme/1/media/stock/600x600/img-84.jpg') }}" class="rounded mw-100"
                                             alt=""/>
                                    </div>
                                    <!--end:Col-->
                                </div>
                                <!--end:Row-->
                            </div>
                            <!--end:Tab pane-->
                            <!--begin:Tab pane-->
                            <div class="tab-pane w-lg-500px" id="kt_app_header_menu_pages_widgets">
                                <!--begin:Row-->
                                <div class="row">
                                    <!--begin:Col-->
                                    <div class="col-lg-4 mb-6 mb-lg-0">
                                        <!--begin:Menu item-->
                                        <div class="menu-item p-0 m-0">
                                            <!--begin:Menu link-->
                                            <a href="widgets/lists.html" class="menu-link">
                                                <span class="menu-title">لیست ها</span>
                                            </a>
                                            <!--end:Menu link-->
                                        </div>
                                        <!--end:Menu item-->
                                        <!--begin:Menu item-->
                                        <div class="menu-item p-0 m-0">
                                            <!--begin:Menu link-->
                                            <a href="widgets/statistics.html" class="menu-link">
                                                <span class="menu-title">امار</span>
                                            </a>
                                            <!--end:Menu link-->
                                        </div>
                                        <!--end:Menu item-->
                                        <!--begin:Menu item-->
                                        <div class="menu-item p-0 m-0">
                                            <!--begin:Menu link-->
                                            <a href="widgets/charts.html" class="menu-link">
                                                <span class="menu-title">نمودار ها</span>
                                            </a>
                                            <!--end:Menu link-->
                                        </div>
                                        <!--end:Menu item-->
                                        <!--begin:Menu item-->
                                        <div class="menu-item p-0 m-0">
                                            <!--begin:Menu link-->
                                            <a href="widgets/mixed.html" class="menu-link">
                                                <span class="menu-title">مخلوط</span>
                                            </a>
                                            <!--end:Menu link-->
                                        </div>
                                        <!--end:Menu item-->
                                        <!--begin:Menu item-->
                                        <div class="menu-item p-0 m-0">
                                            <!--begin:Menu link-->
                                            <a href="widgets/tables.html" class="menu-link">
                                                <span class="menu-title">جداول</span>
                                            </a>
                                            <!--end:Menu link-->
                                        </div>
                                        <!--end:Menu item-->
                                        <!--begin:Menu item-->
                                        <div class="menu-item p-0 m-0">
                                            <!--begin:Menu link-->
                                            <a href="widgets/feeds.html" class="menu-link">
                                                <span class="menu-title">تغذیه</span>
                                            </a>
                                            <!--end:Menu link-->
                                        </div>
                                        <!--end:Menu item-->
                                    </div>
                                    <!--end:Col-->
                                    <!--begin:Col-->
                                    <div class="col-lg-8">
                                        <img src="{{ asset('theme/1/media/stock/900x600/44.jpg') }}" class="rounded mw-100" alt=""/>
                                    </div>
                                    <!--end:Col-->
                                </div>
                                <!--end:Row-->
                            </div>
                            <!--end:Tab pane-->
                        </div>
                        <!--end:Tab content-->
                    </div>
                    <!--end:صفحات menu-->
                </div>
                <!--end:Menu sub-->
            </div>--}}
            <!--end:Menu item-->
            <!--begin:Menu item-->
            {{--<div data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                 data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'bottom-end' : 'bottom-start' }}"
                 class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2">
                <!--begin:Menu link-->
                <span class="menu-link py-3">
													<span class="menu-title">اپلیکیشن</span>
													<span class="menu-arrow d-lg-none"></span>
												</span>
                <!--end:Menu link-->
                <!--begin:Menu sub-->
                <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-250px">
                    <!--begin:Menu item-->
                    <div data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                         data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}"
                         class="menu-item menu-lg-down-accordion">
                        <!--begin:Menu link-->
                        <span class="menu-link py-3">
															<span class="menu-icon">
																<i class="ki-duotone ki-rocket fs-2">
																	<span class="path1"></span>
																	<span class="path2"></span>
																</i>
															</span>
															<span class="menu-title">پروژه ها</span>
															<span class="menu-arrow"></span>
														</span>
                        <!--end:Menu link-->
                        <!--begin:Menu sub-->
                        <div
                            class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-active-bg px-lg-2 py-lg-4 w-lg-225px">
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/projects/list.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">پروژه ها من</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/projects/project.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">مشاهده پروژه</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/projects/targets.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">اهداف</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/projects/budget.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">بودجه</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/projects/users.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">کاربران</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/projects/files.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">فایل ها</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/projects/activity.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">فعالیت</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/projects/settings.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">تنظیمات</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                         data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}"
                         class="menu-item menu-lg-down-accordion">
                        <!--begin:Menu link-->
                        <span class="menu-link py-3">
															<span class="menu-icon">
																<i class="ki-duotone ki-handcart fs-2"></i>
															</span>
															<span class="menu-title">فروشگاه</span>
															<span class="menu-arrow"></span>
														</span>
                        <!--end:Menu link-->
                        <!--begin:Menu sub-->
                        <div
                            class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-active-bg px-lg-2 py-lg-4 w-lg-225px">
                            <!--begin:Menu item-->
                            <div data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                                 data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}"
                                 class="menu-item menu-lg-down-accordion">
                                <!--begin:Menu link-->
                                <span class="menu-link py-3">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
																	<span class="menu-title">کاتالوگ</span>
																	<span class="menu-arrow"></span>
																</span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div
                                    class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-active-bg px-lg-2 py-lg-4 w-lg-225px">
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/ecommerce/catalog/products.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">محصولات</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/ecommerce/catalog/categories.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">دسته بندی ها</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/ecommerce/catalog/add-product.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">افزودن محصولات</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/ecommerce/catalog/edit-product.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title"> محصولات</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/ecommerce/catalog/add-category.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">افزودن دسته بندی</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/ecommerce/catalog/edit-category.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">دسته بندی</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                </div>
                                <!--end:Menu sub-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <!--begin:Menu link-->
                                <span class="menu-link py-3">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
																	<span class="menu-title">فروش</span>
																	<span class="menu-arrow"></span>
																</span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div class="menu-sub menu-sub-accordion">
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/ecommerce/sales/listing.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">لیست سفارشات</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/ecommerce/sales/details.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">جزییات سفارش</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/ecommerce/sales/add-order.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">افزودن سفارش</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/ecommerce/sales/edit-order.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title"> سفارش</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                </div>
                                <!--end:Menu sub-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <!--begin:Menu link-->
                                <span class="menu-link py-3">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
																	<span class="menu-title">مشتریان</span>
																	<span class="menu-arrow"></span>
																</span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div class="menu-sub menu-sub-accordion">
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/ecommerce/customers/listing.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">لیست مشتریان</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/ecommerce/customers/details.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">مشتریان جزییات</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                </div>
                                <!--end:Menu sub-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div data-kt-menu-trigger="click" class="menu-item menu-accordion menu-sub-indention">
                                <!--begin:Menu link-->
                                <span class="menu-link py-3">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
																	<span class="menu-title">گزارشات</span>
																	<span class="menu-arrow"></span>
																</span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div class="menu-sub menu-sub-accordion">
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/ecommerce/reports/view.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">محصولات نشان داد</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/ecommerce/reports/sales.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">فروش</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/ecommerce/reports/returns.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">برگشتی ها</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/ecommerce/reports/customer-orders.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">مشتری سفارشات</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/ecommerce/reports/shipping.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">حمل دریایی</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                </div>
                                <!--end:Menu sub-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/ecommerce/settings.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">تنظیمات</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                         data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}"
                         class="menu-item menu-lg-down-accordion">
                        <!--begin:Menu link-->
                        <span class="menu-link py-3">
															<span class="menu-icon">
																<i class="ki-duotone ki-chart fs-2">
																	<span class="path1"></span>
																	<span class="path2"></span>
																</i>
															</span>
															<span class="menu-title">مرکز پشتیبانی </span>
															<span class="menu-arrow"></span>
														</span>
                        <!--end:Menu link-->
                        <!--begin:Menu sub-->
                        <div
                            class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-active-bg px-lg-2 py-lg-4 w-lg-225px">
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/support-center/overview.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">بررسی اجمالی</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                                 data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}"
                                 class="menu-item menu-lg-down-accordion">
                                <!--begin:Menu link-->
                                <span class="menu-link py-3">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
																	<span class="menu-title">تیکت ها</span>
																	<span class="menu-arrow"></span>
																</span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div
                                    class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-active-bg px-lg-2 py-lg-4 w-lg-225px">
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/support-center/tickets/list.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">تیکت لیست</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/support-center/tickets/view.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">تیکت نمایش</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                </div>
                                <!--end:Menu sub-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                                 data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}"
                                 class="menu-item menu-lg-down-accordion">
                                <!--begin:Menu link-->
                                <span class="menu-link py-3">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
																	<span class="menu-title">آموزش ها</span>
																	<span class="menu-arrow"></span>
																</span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div
                                    class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-active-bg px-lg-2 py-lg-4 w-lg-225px">
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/support-center/tutorials/list.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">لیست آموزش ها</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/support-center/tutorials/post.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">پست های آموزشی</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                </div>
                                <!--end:Menu sub-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/support-center/faq.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">سوالات متداول</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/support-center/licenses.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">مجوزها</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/support-center/contact.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">تماس با ما</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                         data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}"
                         class="menu-item menu-lg-down-accordion">
                        <!--begin:Menu link-->
                        <span class="menu-link py-3">
															<span class="menu-icon">
																<i class="ki-duotone ki-shield-tick fs-2">
																	<span class="path1"></span>
																	<span class="path2"></span>
																</i>
															</span>
															<span class="menu-title">کاربر مدیریت</span>
															<span class="menu-arrow"></span>
														</span>
                        <!--end:Menu link-->
                        <!--begin:Menu sub-->
                        <div
                            class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-active-bg px-lg-2 py-lg-4 w-lg-225px">
                            <!--begin:Menu item-->
                            <div data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                                 data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}"
                                 class="menu-item menu-lg-down-accordion">
                                <!--begin:Menu link-->
                                <span class="menu-link py-3">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
																	<span class="menu-title">کاربران</span>
																	<span class="menu-arrow"></span>
																</span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div
                                    class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-active-bg px-lg-2 py-lg-4 w-lg-225px">
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/user-management/users/list.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">کاربران لیست</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/user-management/users/view.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">نمایش کاربر</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                </div>
                                <!--end:Menu sub-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                                 data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}"
                                 class="menu-item menu-lg-down-accordion">
                                <!--begin:Menu link-->
                                <span class="menu-link py-3">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
																	<span class="menu-title">سطح دسترسی</span>
																	<span class="menu-arrow"></span>
																</span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div
                                    class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-active-bg px-lg-2 py-lg-4 w-lg-225px">
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/user-management/roles/list.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">سطح دسترسی لیست</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/user-management/roles/view.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">نمایش سطح دسترسی</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                </div>
                                <!--end:Menu sub-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/user-management/permissions.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">مجوزها</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                         data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}"
                         class="menu-item menu-lg-down-accordion">
                        <!--begin:Menu link-->
                        <span class="menu-link py-3">
															<span class="menu-icon">
																<i class="ki-duotone ki-phone fs-2">
																	<span class="path1"></span>
																	<span class="path2"></span>
																</i>
															</span>
															<span class="menu-title">مخاطبین</span>
															<span class="menu-arrow"></span>
														</span>
                        <!--end:Menu link-->
                        <!--begin:Menu sub-->
                        <div
                            class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-active-bg px-lg-2 py-lg-4 w-lg-225px">
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/contacts/getting-started.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">شروع شدن</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/contacts/add-contact.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">افزودن مخاطب</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/contacts/edit-contact.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title"> تماس با ما</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/contacts/view-contact.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">نمایش تماس با ما</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                         data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}"
                         class="menu-item menu-lg-down-accordion">
                        <!--begin:Menu link-->
                        <span class="menu-link py-3">
															<span class="menu-icon">
																<i class="ki-duotone ki-basket fs-2">
																	<span class="path1"></span>
																	<span class="path2"></span>
																	<span class="path3"></span>
																	<span class="path4"></span>
																</i>
															</span>
															<span class="menu-title">اشتراک ها</span>
															<span class="menu-arrow"></span>
														</span>
                        <!--end:Menu link-->
                        <!--begin:Menu sub-->
                        <div
                            class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-active-bg px-lg-2 py-lg-4 w-lg-225px">
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/subscriptions/getting-started.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">شروع شدن</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/subscriptions/list.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">لیست اشتراک</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/subscriptions/add.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">افزودن اشتراک</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/subscriptions/view.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">نمایش اشتراک ها</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                         data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}"
                         class="menu-item menu-lg-down-accordion">
                        <!--begin:Menu link-->
                        <span class="menu-link py-3">
															<span class="menu-icon">
																<i class="ki-duotone ki-briefcase fs-2">
																	<span class="path1"></span>
																	<span class="path2"></span>
																</i>
															</span>
															<span class="menu-title">مشتریان</span>
															<span class="menu-arrow"></span>
														</span>
                        <!--end:Menu link-->
                        <!--begin:Menu sub-->
                        <div
                            class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-active-bg px-lg-2 py-lg-4 w-lg-225px">
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/customers/getting-started.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">شروع شدن</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/customers/list.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">لیست مشتریان</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/customers/view.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">جزییات مشتریان</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                         data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}"
                         class="menu-item menu-lg-down-accordion">
                        <!--begin:Menu link-->
                        <span class="menu-link py-3">
															<span class="menu-icon">
																<i class="ki-duotone ki-credit-cart fs-2">
																	<span class="path1"></span>
																	<span class="path2"></span>
																</i>
															</span>
															<span class="menu-title">فاکتور مدیریت</span>
															<span class="menu-arrow"></span>
														</span>
                        <!--end:Menu link-->
                        <!--begin:Menu sub-->
                        <div
                            class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-active-bg px-lg-2 py-lg-4 w-lg-225px">
                            <!--begin:Menu item-->
                            <div data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                                 data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}"
                                 class="menu-item menu-lg-down-accordion">
                                <!--begin:Menu link-->
                                <span class="menu-link py-3">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
																	<span class="menu-title">پروفایل</span>
																	<span class="menu-arrow"></span>
																</span>
                                <!--end:Menu link-->
                                <!--begin:Menu sub-->
                                <div
                                    class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-active-bg px-lg-2 py-lg-4 w-lg-225px">
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/invoices/view/invoice-1.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">فاکتور1</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/invoices/view/invoice-2.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">فاکتور2</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link py-3" href="apps/invoices/view/invoice-3.html">
																			<span class="menu-bullet">
																				<span class="bullet bullet-dot"></span>
																			</span>
                                            <span class="menu-title">فاکتور 3</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                </div>
                                <!--end:Menu sub-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/invoices/create.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">ساختن فاکتور</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                         data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}"
                         class="menu-item menu-lg-down-accordion">
                        <!--begin:Menu link-->
                        <span class="menu-link py-3">
															<span class="menu-icon">
																<i class="ki-duotone ki-file-added fs-2">
																	<span class="path1"></span>
																	<span class="path2"></span>
																</i>
															</span>
															<span class="menu-title">مدیر پرونده</span>
															<span class="menu-arrow"></span>
														</span>
                        <!--end:Menu link-->
                        <!--begin:Menu sub-->
                        <div
                            class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-active-bg px-lg-2 py-lg-4 w-lg-225px">
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/file-manager/folders.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">پوشه ها</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/file-manager/files.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">فایل ها</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/file-manager/blank.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">دایرکتوری خالی</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/file-manager/settings.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">تنظیمات</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                         data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}"
                         class="menu-item menu-lg-down-accordion">
                        <!--begin:Menu link-->
                        <span class="menu-link py-3">
															<span class="menu-icon">
																<i class="ki-duotone ki-sms fs-2">
																	<span class="path1"></span>
																	<span class="path2"></span>
																</i>
															</span>
															<span class="menu-title">صندوق پیام</span>
															<span class="menu-arrow"></span>
														</span>
                        <!--end:Menu link-->
                        <!--begin:Menu sub-->
                        <div
                            class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-active-bg px-lg-2 py-lg-4 w-lg-225px">
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/inbox/listing.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">پیام ها</span>
                                    <span class="menu-badge">
																		<span class="badge badge-light-success">3</span>
																	</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/inbox/compose.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">ارسال</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/inbox/reply.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">نمایش & پاسخ</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                         data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}"
                         class="menu-item menu-lg-down-accordion">
                        <!--begin:Menu link-->
                        <span class="menu-link py-3">
															<span class="menu-icon">
																<i class="ki-duotone ki-message-text-2 fs-2">
																	<span class="path1"></span>
																	<span class="path2"></span>
																	<span class="path3"></span>
																</i>
															</span>
															<span class="menu-title">چت</span>
															<span class="menu-arrow"></span>
														</span>
                        <!--end:Menu link-->
                        <!--begin:Menu sub-->
                        <div
                            class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown menu-active-bg px-lg-2 py-lg-4 w-lg-225px">
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/chat/private.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">خصوصی چت</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/chat/group.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">گروه چت</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link py-3" href="apps/chat/drawer.html">
																	<span class="menu-bullet">
																		<span class="bullet bullet-dot"></span>
																	</span>
                                    <span class="menu-title">کشو چت</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu link-->
                        <a class="menu-link py-3" href="apps/calendar.html">
															<span class="menu-icon">
																<i class="ki-duotone ki-calendar-8 fs-2">
																	<span class="path1"></span>
																	<span class="path2"></span>
																	<span class="path3"></span>
																	<span class="path4"></span>
																	<span class="path5"></span>
																	<span class="path6"></span>
																</i>
															</span>
                            <span class="menu-title">تقویم</span>
                        </a>
                        <!--end:Menu link-->
                    </div>
                    <!--end:Menu item-->
                </div>
                <!--end:Menu sub-->
            </div>--}}
            <!--end:Menu item-->
            <!--begin:Menu item-->
            {{--<div data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                 data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'bottom-end' : 'bottom-start' }}"
                 class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2">
                <!--begin:Menu link-->
                <span class="menu-link py-3">
													<span class="menu-title">کمک</span>
													<span class="menu-arrow d-lg-none"></span>
												</span>
                <!--end:Menu link-->
                <!--begin:Menu sub-->
                <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-200px">
                    <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu link-->
                        <a class="menu-link py-3"
                           href="https://preview.keenthemes.com/html/metronic/docs/base/utilities" target="_blank"
                           title="بیش از 200 کامپوننت داخلی، پلاگین و راه حل های آماده برای استفاده را بررسی کنید"
                           data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click"
                           data-bs-placement="right">
															<span class="menu-icon">
																<i class="ki-duotone ki-rocket fs-2">
																	<span class="path1"></span>
																	<span class="path2"></span>
																</i>
															</span>
                            <span class="menu-title">کامپوننت ها</span>
                        </a>
                        <!--end:Menu link-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu link-->
                        <a class="menu-link py-3" href="https://preview.keenthemes.com/html/metronic/docs"
                           target="_blank" title="اسناد کامل را بررسی کنید" data-bs-toggle="tooltip"
                           data-bs-trigger="hover" data-bs-dismiss="click" data-bs-placement="right">
															<span class="menu-icon">
																<i class="ki-duotone ki-abstract-26 fs-2">
																	<span class="path1"></span>
																	<span class="path2"></span>
																</i>
															</span>
                            <span class="menu-title">اسناد</span>
                        </a>
                        <!--end:Menu link-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu link-->
                        <a class="menu-link py-3"
                           href="https://preview.keenthemes.com/metronic8/demo2/layout-builder.html"
                           title="Build your layout and expیاt HTML for server side integration"
                           data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss="click"
                           data-bs-placement="right">
															<span class="menu-icon">
																<i class="ki-duotone ki-switch fs-2">
																	<span class="path1"></span>
																	<span class="path2"></span>
																</i>
															</span>
                            <span class="menu-title">قالب بندی سازنده</span>
                        </a>
                        <!--end:Menu link-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu link-->
                        <a class="menu-link py-3"
                           href="https://preview.keenthemes.com/html/metronic/docs/getting-started/changelog"
                           target="_blank">
															<span class="menu-icon">
																<i class="ki-duotone ki-code fs-2">
																	<span class="path1"></span>
																	<span class="path2"></span>
																	<span class="path3"></span>
																	<span class="path4"></span>
																</i>
															</span>
                            <span class="menu-title">تغییرات v8.2.6</span>
                        </a>
                        <!--end:Menu link-->
                    </div>
                    <!--end:Menu item-->
                </div>
                <!--end:Menu sub-->
            </div>--}}
            <!--end:Menu item-->
        </div>
        <!--end::Menu-->
    </div>
    <!--end::Menu wrapper-->
</div>
