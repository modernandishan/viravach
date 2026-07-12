<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="toolbar py-5 pb-lg-15" id="kt_toolbar">
    <!--begin::Container-->
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <!--begin::Page title-->
        <div class="page-title d-flex flex-column me-3">
            <!--begin::Title-->
            <h1 class="d-flex text-white fw-bold my-1 fs-3">داشبورد</h1>
            <!--end::Title-->
            <!--begin::Breadcrumb-->
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-1">
                <!--begin::item-->
                <li class="breadcrumb-item text-white opacity-75">
                    <a href="index.html" class="text-white text-hover-primary">خانه</a>
                </li>
                <!--end::item-->
                <!--begin::item-->
                <li class="breadcrumb-item">
                    <span class="bullet bg-white opacity-75 w-5px h-2px"></span>
                </li>
                <!--end::item-->
                <!--begin::item-->
                <li class="breadcrumb-item text-white opacity-75">داشبورد ها</li>
                <!--end::item-->
                <!--begin::item-->
                <li class="breadcrumb-item">
                    <span class="bullet bg-white opacity-75 w-5px h-2px"></span>
                </li>
                <!--end::item-->
                <!--begin::item-->
                <li class="breadcrumb-item text-white opacity-75">پیش فرض</li>
                <!--end::item-->
            </ul>
            <!--end::Breadcrumb-->
        </div>
        <!--end::Page title-->
        <!--begin::Actions-->
        <div class="d-flex align-items-center py-3 py-md-1">
            <!--begin::Wrapper-->
            <div class="me-4">
                <!--begin::Menu-->
                <a href="#" class="btn btn-custom btn-active-white btn-flex btn-color-white btn-active-color-white" data-kt-menu-trigger="click" data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'bottom-end' : 'bottom-start' }}">
                    <i class="ki-duotone ki-filter fs-5 me-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>فیلتر</a>
                <!--begin::Menu 1-->
                <div class="menu menu-sub menu-sub-dropdown w-250px w-md-300px" data-kt-menu="true" id="kt_menu_6606389eb7168">
                    <!--begin::Header-->
                    <div class="px-7 py-5">
                        <div class="fs-5 text-gray-900 fw-bold">فیلتر تنظیمات</div>
                    </div>
                    <!--end::Header-->
                    <!--begin::Menu separator-->
                    <div class="separator border-gray-200"></div>
                    <!--end::Menu separator-->
                    <!--begin::form-->
                    <div class="px-7 py-5">
                        <!--begin::Input group-->
                        <div class="mb-10">
                            <!--begin::Tags-->
                            <label class="form-label fw-semibold">وضعیت:</label>
                            <!--end::Tags-->
                            <!--begin::Input-->
                            <div>
                                <select class="form-select form-select-solid" multiple="multiple" data-kt-select2="true" data-close-on-select="false" data-placeholder="انتخاب گزینه" data-dropdown-parent="#kt_menu_6606389eb7168" data-allow-clear="true">
                                    <option></option>
                                    <option value="1">تایید شده</option>
                                    <option value="2">در انتظار</option>
                                    <option value="2">در حال پردازش</option>
                                    <option value="2">رد شد</option>
                                </select>
                            </div>
                            <!--end::Input-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="mb-10">
                            <!--begin::Tags-->
                            <label class="form-label fw-semibold">نوع عضویت:</label>
                            <!--end::Tags-->
                            <!--begin::تنظیمات-->
                            <div class="d-flex">
                                <!--begin::تنظیمات-->
                                <label class="form-check form-check-sm form-check-custom form-check-solid me-5">
                                    <input class="form-check-input" type="checkbox" value="1" />
                                    <span class="form-check-label">نویسنده</span>
                                </label>
                                <!--end::تنظیمات-->
                                <!--begin::تنظیمات-->
                                <label class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="2" checked="checked" />
                                    <span class="form-check-label">مشتری</span>
                                </label>
                                <!--end::تنظیمات-->
                            </div>
                            <!--end::تنظیمات-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="mb-10">
                            <!--begin::Tags-->
                            <label class="form-label fw-semibold">اعلان ها:</label>
                            <!--end::Tags-->
                            <!--begin::Switch-->
                            <div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="" name="notifications" checked="checked" />
                                <label class="form-check-label">فعال</label>
                            </div>
                            <!--end::Switch-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Actions-->
                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-sm btn-light btn-active-light-primary me-2" data-kt-menu-dismiss="true">ریست</button>
                            <button type="submit" class="btn btn-sm btn-primary" data-kt-menu-dismiss="true">تایید</button>
                        </div>
                        <!--end::Actions-->
                    </div>
                    <!--end::form-->
                </div>
                <!--end::Menu 1-->
                <!--end::Menu-->
            </div>
            <!--end::Wrapper-->
            <!--begin::Button-->
            <a href="#" data-bs-theme="light" class="btn bg-body btn-active-color-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_create_app" id="kt_toolbar_primary_button">ساختن</a>
            <!--end::Button-->
        </div>
        <!--end::Actions-->
    </div>
    <!--end::Container-->
</div>
