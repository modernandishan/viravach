<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">
        <livewire:dashboard-elements.infobar />

        <div class="card" id="kt_pricing">
            <!--begin::کارت body-->
            <div class="card-body p-lg-17">
                <!--begin::برنامه ریزی ها-->
                <div class="d-flex flex-column">
                    <!--begin::Heading-->
                    <div class="mb-13 text-center">
                        <h1 class="fs-2hx fw-bold mb-5">برنامه را انتخاب کنید</h1>
                        <div class="text-gray-600 fw-semibold fs-5">اگر به اطلاعات بیشتری در مورد قیمت ما نیاز دارید، لطفا بررسی کنید
                            <a href="#" class="link-primary fw-bold">دستورالعمل قیمت گذاری</a>.</div>
                    </div>
                    <!--end::Heading-->
                    <!--begin::Nav group-->
                    <div class="nav-group nav-group-outline mx-auto mb-15" data-kt-buttons="true" data-kt-initialized="1">
                        <button class="btn btn-color-gray-600 btn-active btn-active-secondary px-6 py-3 me-2" data-kt-plan="month">ماهانه</button>
                        <button class="btn btn-color-gray-600 btn-active btn-active-secondary px-6 py-3 active" data-kt-plan="annual">سالانه</button>
                    </div>
                    <!--end::Nav group-->
                    <!--begin::Row-->
                    <div class="row g-10">
                        <!--begin::Col-->
                        <div class="col-xl-4">
                            <div class="d-flex h-100 align-items-center">
                                <!--begin::Option-->
                                <div class="w-100 d-flex flex-column flex-center rounded-3 bg-light bg-opacity-75 py-15 px-10">
                                    <!--begin::Heading-->
                                    <div class="mb-7 text-center">
                                        <!--begin::Title-->
                                        <h1 class="text-gray-900 mb-5 fw-bolder">استارت آپ</h1>
                                        <!--end::Title-->
                                        <!--begin::توضیحات-->
                                        <div class="text-gray-600 fw-semibold mb-5">بهینه برای تیم بیش از 10 نفر
                                            <br>و استارت آپ جدید</div>
                                        <!--end::توضیحات-->
                                        <!--begin::قیمت-->
                                        <div class="text-center">
                                            <span class="mb-2 text-primary">$</span>
                                            <span class="fs-3x fw-bold text-primary" data-kt-plan-price-month="39" data-kt-plan-price-annual="399">39</span>
                                            <span class="fs-7 fw-semibold opacity-50">/
																<span data-kt-element="period">دوشنبه</span></span>
                                        </div>
                                        <!--end::قیمت-->
                                    </div>
                                    <!--end::Heading-->
                                    <!--begin::ویژگی ها-->
                                    <div class="w-100 mb-10">
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fw-semibold fs-6 text-gray-800 flex-grow-1 pe-3">فعال ترین کاربران</span>
                                            <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fw-semibold fs-6 text-gray-800 flex-grow-1 pe-3">حداکثر 30 ادغام پروژه</span>
                                            <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fw-semibold fs-6 text-gray-800 flex-grow-1 pe-3">ماژول تجزیه و تحلیل</span>
                                            <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fw-semibold fs-6 text-gray-600 flex-grow-1">ماژول دارایی ، مالیه ، سرمایه گذاری</span>
                                            <i class="ki-duotone ki-cross-circle fs-1">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fw-semibold fs-6 text-gray-600 flex-grow-1">ماژول حسابداری</span>
                                            <i class="ki-duotone ki-cross-circle fs-1">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fw-semibold fs-6 text-gray-600 flex-grow-1">بستر شبکه</span>
                                            <i class="ki-duotone ki-cross-circle fs-1">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center">
                                            <span class="fw-semibold fs-6 text-gray-600 flex-grow-1">فضای نامحدود ابر</span>
                                            <i class="ki-duotone ki-cross-circle fs-1">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                    </div>
                                    <!--end::ویژگی ها-->
                                    <!--begin::انتخاب-->
                                    <a href="#" class="btn btn-sm btn-primary">انتخاب</a>
                                    <!--end::انتخاب-->
                                </div>
                                <!--end::Option-->
                            </div>
                        </div>
                        <!--end::Col-->
                        <!--begin::Col-->
                        <div class="col-xl-4">
                            <div class="d-flex h-100 align-items-center">
                                <!--begin::Option-->
                                <div class="w-100 d-flex flex-column flex-center rounded-3 bg-light bg-opacity-75 py-20 px-10">
                                    <!--begin::Heading-->
                                    <div class="mb-7 text-center">
                                        <!--begin::Title-->
                                        <h1 class="text-gray-900 mb-5 fw-bolder">پیشرفته</h1>
                                        <!--end::Title-->
                                        <!--begin::توضیحات-->
                                        <div class="text-gray-600 fw-semibold mb-5">بهینه برای تیم 100+ شش
                                            <br>شرکت الکترونیکی و رشد یافته</div>
                                        <!--end::توضیحات-->
                                        <!--begin::قیمت-->
                                        <div class="text-center">
                                            <span class="mb-2 text-primary">$</span>
                                            <span class="fs-3x fw-bold text-primary" data-kt-plan-price-month="339" data-kt-plan-price-annual="3399">339</span>
                                            <span class="fs-7 fw-semibold opacity-50">/
																<span data-kt-element="period">دوشنبه</span></span>
                                        </div>
                                        <!--end::قیمت-->
                                    </div>
                                    <!--end::Heading-->
                                    <!--begin::ویژگی ها-->
                                    <div class="w-100 mb-10">
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fw-semibold fs-6 text-gray-800 flex-grow-1 pe-3">فعال ترین کاربران</span>
                                            <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fw-semibold fs-6 text-gray-800 flex-grow-1 pe-3">حداکثر 30 ادغام پروژه</span>
                                            <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fw-semibold fs-6 text-gray-800 flex-grow-1 pe-3">ماژول تجزیه و تحلیل</span>
                                            <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fw-semibold fs-6 text-gray-800 flex-grow-1 pe-3">ماژول دارایی ، مالیه ، سرمایه گذاری</span>
                                            <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fw-semibold fs-6 text-gray-800 flex-grow-1 pe-3">ماژول حسابداری</span>
                                            <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fw-semibold fs-6 text-gray-600 flex-grow-1">بستر شبکه</span>
                                            <i class="ki-duotone ki-cross-circle fs-1">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center">
                                            <span class="fw-semibold fs-6 text-gray-600 flex-grow-1">فضای نامحدود ابر</span>
                                            <i class="ki-duotone ki-cross-circle fs-1">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                    </div>
                                    <!--end::ویژگی ها-->
                                    <!--begin::انتخاب-->
                                    <a href="#" class="btn btn-sm btn-primary">انتخاب</a>
                                    <!--end::انتخاب-->
                                </div>
                                <!--end::Option-->
                            </div>
                        </div>
                        <!--end::Col-->
                        <!--begin::Col-->
                        <div class="col-xl-4">
                            <div class="d-flex h-100 align-items-center">
                                <!--begin::Option-->
                                <div class="w-100 d-flex flex-column flex-center rounded-3 bg-light bg-opacity-75 py-15 px-10">
                                    <!--begin::Heading-->
                                    <div class="mb-7 text-center">
                                        <!--begin::Title-->
                                        <h1 class="text-gray-900 mb-5 fw-bolder">شرکت، پروژه</h1>
                                        <!--end::Title-->
                                        <!--begin::توضیحات-->
                                        <div class="text-gray-600 fw-semibold mb-5">Optimal for 1000+ team
                                            <br>و بنگاه اقتصادی</div>
                                        <!--end::توضیحات-->
                                        <!--begin::قیمت-->
                                        <div class="text-center">
                                            <span class="mb-2 text-primary">$</span>
                                            <span class="fs-3x fw-bold text-primary" data-kt-plan-price-month="999" data-kt-plan-price-annual="9999">999</span>
                                            <span class="fs-7 fw-semibold opacity-50">/
																<span data-kt-element="period">دوشنبه</span></span>
                                        </div>
                                        <!--end::قیمت-->
                                    </div>
                                    <!--end::Heading-->
                                    <!--begin::ویژگی ها-->
                                    <div class="w-100 mb-10">
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fw-semibold fs-6 text-gray-800 flex-grow-1 pe-3">فعال ترین کاربران</span>
                                            <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fw-semibold fs-6 text-gray-800 flex-grow-1 pe-3">حداکثر 30 ادغام پروژه</span>
                                            <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fw-semibold fs-6 text-gray-800 flex-grow-1 pe-3">ماژول تجزیه و تحلیل</span>
                                            <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fw-semibold fs-6 text-gray-800 flex-grow-1 pe-3">ماژول دارایی ، مالیه ، سرمایه گذاری</span>
                                            <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fw-semibold fs-6 text-gray-800 flex-grow-1 pe-3">ماژول حسابداری</span>
                                            <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center mb-5">
                                            <span class="fw-semibold fs-6 text-gray-800 flex-grow-1 pe-3">بستر شبکه</span>
                                            <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex align-items-center">
                                            <span class="fw-semibold fs-6 text-gray-800 flex-grow-1 pe-3">فضای نامحدود ابر</span>
                                            <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                        <!--end::item-->
                                    </div>
                                    <!--end::ویژگی ها-->
                                    <!--begin::انتخاب-->
                                    <a href="#" class="btn btn-sm btn-primary">انتخاب</a>
                                    <!--end::انتخاب-->
                                </div>
                                <!--end::Option-->
                            </div>
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Row-->
                </div>
                <!--end::برنامه ریزی ها-->
            </div>
            <!--end::کارت body-->
        </div>
    </div>
</div>
