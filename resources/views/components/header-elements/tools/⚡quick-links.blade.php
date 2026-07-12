<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="d-flex align-items-center ms-1 ms-lg-3">
    <!--begin::Menu wrapper-->
    <div class="btn btn-icon btn-active-light-primary btn-custom w-30px h-30px w-md-40px h-md-40px" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'bottom-end' : 'bottom-start' }}">
        <i class="ki-duotone ki-element-11 fs-1">
            <span class="path1"></span>
            <span class="path2"></span>
            <span class="path3"></span>
            <span class="path4"></span>
        </i>
    </div>
    <!--begin::Menu-->
    <div class="menu menu-sub menu-sub-dropdown menu-column w-250px w-lg-325px" data-kt-menu="true">
        <!--begin::Heading-->
        <div class="d-flex flex-column flex-center bgi-no-repeat rounded-top px-9 py-10" style="background-image:url('theme/1/media/misc/menu-header-bg.jpg')">
            <!--begin::Title-->
            <h3 class="text-white fw-semibold mb-3">لینک های سریع</h3>
            <!--end::Title-->
            <!--begin::وضعیت-->
            <span class="badge bg-primary text-inverse-primary py-2 px-3">25 وظیفه در انتظار</span>
            <!--end::وضعیت-->
        </div>
        <!--end::Heading-->
        <!--begin:Nav-->
        <div class="row g-0">
            <!--begin:آیتم-->
            <div class="col-6">
                <a href="apps/projects/budget.html" class="d-flex flex-column flex-center h-100 p-6 bg-hover-light border-end border-bottom">
                    <i class="ki-duotone ki-dollar fs-3x text-primary mb-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    <span class="fs-5 fw-semibold text-gray-800 mb-0">حسابداری</span>
                    <span class="fs-7 text-gray-500">تجارت</span>
                </a>
            </div>
            <!--end:آیتم-->
            <!--begin:آیتم-->
            <div class="col-6">
                <a href="apps/projects/settings.html" class="d-flex flex-column flex-center h-100 p-6 bg-hover-light border-bottom">
                    <i class="ki-duotone ki-sms fs-3x text-primary mb-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <span class="fs-5 fw-semibold text-gray-800 mb-0">مدیریت</span>
                    <span class="fs-7 text-gray-500">کنسول</span>
                </a>
            </div>
            <!--end:آیتم-->
            <!--begin:آیتم-->
            <div class="col-6">
                <a href="apps/projects/list.html" class="d-flex flex-column flex-center h-100 p-6 bg-hover-light border-end">
                    <i class="ki-duotone ki-abstract-41 fs-3x text-primary mb-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <span class="fs-5 fw-semibold text-gray-800 mb-0">پروژه ها</span>
                    <span class="fs-7 text-gray-500">انتظار وظیفه ها</span>
                </a>
            </div>
            <!--end:آیتم-->
            <!--begin:آیتم-->
            <div class="col-6">
                <a href="apps/projects/users.html" class="d-flex flex-column flex-center h-100 p-6 bg-hover-light">
                    <i class="ki-duotone ki-briefcase fs-3x text-primary mb-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <span class="fs-5 fw-semibold text-gray-800 mb-0">مشتریان</span>
                    <span class="fs-7 text-gray-500">آخرین موارد</span>
                </a>
            </div>
            <!--end:آیتم-->
        </div>
        <!--end:Nav-->
        <!--begin::نمایش بیشتر-->
        <div class="py-2 text-center border-top">
            <a href="pages/user-profile/activity.html" class="btn btn-color-gray-600 btn-active-color-primary">نمایش همه
                <i class="ki-duotone ki-arrow-left fs-5">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i></a>
        </div>
        <!--end::نمایش بیشتر-->
    </div>
    <!--end::Menu-->
    <!--end::Menu wrapper-->
</div>
