<?php

use Livewire\Component;

new class extends Component {
    //
};
?>
<div class="topbar d-flex align-items-stretch flex-shrink-0">
    <!--begin::search-->
    <livewire:header-elements.tools.search />
    <!--end::search-->
    <!--begin::activities-->
    <livewire:header-elements.tools.activities />
    <!--end::activities-->
    <!--begin::notifications-->
    <livewire:header-elements.tools.notifications />
    <!--end::notifications-->
    <!--begin::chat-->
    <livewire:header-elements.tools.chat />
    <!--end::chat-->
    <!--begin::Quick links-->
    <livewire:header-elements.tools.quick-links />
    <!--end::Quick links-->
    <!--begin::Theme mode-->
    <livewire:header-elements.tools.theme-mode />
    <!--end::Theme mode-->
    <!--begin::Language switcher-->
    <livewire:header-elements.tools.language-switcher />
    <!--end::Language switcher-->
    <!--begin::user-->
    <div class="d-flex align-items-center me-lg-n2 ms-1 ms-lg-3" id="kt_header_user_menu_toggle">
        <!--begin::Menu wrapper-->
        <div class="btn btn-icon btn-active-light-primary btn-custom w-30px h-30px w-md-40px h-md-40px" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'bottom-end' : 'bottom-start' }}">
            <img class="h-30px w-30px rounded" src="theme/1/media/avatars/300-2.jpg" alt="" />
        </div>
        <!--begin::کاربر account menu-->
        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
            <!--begin::Menu item-->
            <div class="menu-item px-3">
                <div class="menu-content d-flex align-items-center px-3">
                    <!--begin::Avatar-->
                    <div class="symbol symbol-50px me-5">
                        <img alt="Logo" src="theme/1/media/avatars/300-2.jpg" />
                    </div>
                    <!--end::Avatar-->
                    <!--begin::کاربرname-->
                    <div class="d-flex flex-column">
                        <div class="fw-bold d-flex align-items-center fs-5">جلالی
                            <span class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">حرفه ای</span></div>
                        <a href="#" class="fw-semibold text-muted text-hover-primary fs-7">max@kt.com</a>
                    </div>
                    <!--end::کاربرname-->
                </div>
            </div>
            <!--end::Menu item-->
            <!--begin::Menu separator-->
            <div class="separator my-2"></div>
            <!--end::Menu separator-->
            <!--begin::Menu item-->
            <div class="menu-item px-5">
                <a href="account/overview.html" class="menu-link px-5">پروفایل من</a>
            </div>
            <!--end::Menu item-->
            <!--begin::Menu item-->
            <div class="menu-item px-5">
                <a href="apps/projects/list.html" class="menu-link px-5">
                    <span class="menu-text">پروژه ها من</span>
                    <span class="menu-badge">
														<span class="badge badge-light-danger badge-circle fw-bold fs-7">3</span>
													</span>
                </a>
            </div>
            <!--end::Menu item-->
            <!--begin::Menu item-->
            <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}" data-kt-menu-offset="-15px, 0">
                <a href="#" class="menu-link px-5">
                    <span class="menu-title">اشتراک من</span>
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
                                <input class="form-check-input w-30px h-20px" type="checkbox" value="1" checked="checked" name="notifications" />
                                <span class="form-check-label text-muted fs-7">اعلان ها</span>
                            </label>
                        </div>
                    </div>
                    <!--end::Menu item-->
                </div>
                <!--end::Menu sub-->
            </div>
            <!--end::Menu item-->
            <!--begin::Menu item-->
            <div class="menu-item px-5">
                <a href="account/statements.html" class="menu-link px-5">من بیانه ها</a>
            </div>
            <!--end::Menu item-->
            <!--begin::Menu separator-->
            <div class="separator my-2"></div>
            <!--end::Menu separator-->
            <!--begin::Menu item-->
            <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}" data-kt-menu-offset="-15px, 0">
                <a href="#" class="menu-link px-5">
													<span class="menu-title position-relative">زبان
													<span class="fs-8 rounded bg-light px-3 py-2 position-absolute translate-middle-y top-50 end-0">انگلیسی
													<img class="w-15px h-15px rounded-1 ms-2" src="theme/1/media/flags/united-states.svg" alt="" /></span></span>
                </a>
                <!--begin::Menu sub-->
                <div class="menu-sub menu-sub-dropdown w-175px py-4">
                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <a href="account/settings.html" class="menu-link d-flex px-5 active">
														<span class="symbol symbol-20px me-4">
															<img class="rounded-1" src="theme/1/media/flags/united-states.svg" alt="" />
														</span>انگلیسی</a>
                    </div>
                    <!--end::Menu item-->
                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <a href="account/settings.html" class="menu-link d-flex px-5">
														<span class="symbol symbol-20px me-4">
															<img class="rounded-1" src="theme/1/media/flags/spain.svg" alt="" />
														</span>اسپانیایی</a>
                    </div>
                    <!--end::Menu item-->
                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <a href="account/settings.html" class="menu-link d-flex px-5">
														<span class="symbol symbol-20px me-4">
															<img class="rounded-1" src="theme/1/media/flags/germany.svg" alt="" />
														</span>آلمانی</a>
                    </div>
                    <!--end::Menu item-->
                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <a href="account/settings.html" class="menu-link d-flex px-5">
														<span class="symbol symbol-20px me-4">
															<img class="rounded-1" src="theme/1/media/flags/japan.svg" alt="" />
														</span>ژاپنی</a>
                    </div>
                    <!--end::Menu item-->
                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <a href="account/settings.html" class="menu-link d-flex px-5">
														<span class="symbol symbol-20px me-4">
															<img class="rounded-1" src="theme/1/media/flags/france.svg" alt="" />
														</span>فرانسه</a>
                    </div>
                    <!--end::Menu item-->
                </div>
                <!--end::Menu sub-->
            </div>
            <!--end::Menu item-->
            <!--begin::Menu item-->
            <div class="menu-item px-5 my-1">
                <a href="account/settings.html" class="menu-link px-5">اکانت تنظیمات</a>
            </div>
            <!--end::Menu item-->
            <!--begin::Menu item-->
            <div class="menu-item px-5">
                <a href="authentication/layouts/corporate/sign-in.html" class="menu-link px-5">خروج</a>
            </div>
            <!--end::Menu item-->
        </div>
        <!--end::کاربر account menu-->
        <!--end::Menu wrapper-->
    </div>
    <!--end::user -->

    <!--begin::side mobile toggle-->
    <!--end::side mobile toggle-->
</div>
