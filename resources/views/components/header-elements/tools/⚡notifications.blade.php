<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="d-flex align-items-center ms-1 ms-lg-3">
    <!--begin::Menu- wrapper-->
    <div class="position-relative btn btn-icon btn-active-light-primary btn-custom w-30px h-30px w-md-40px h-md-40px" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="{{ app()->getLocale() === 'rtl' ? 'bottom-end' : 'bottom-start' }}">
        <i class="ki-duotone ki-binance fs-1">
            <span class="path1"></span>
            <span class="path2"></span>
            <span class="path3"></span>
            <span class="path4"></span>
            <span class="path5"></span>
        </i>
    </div>
    <!--begin::Menu-->
    <div class="menu menu-sub menu-sub-dropdown menu-column w-350px w-lg-375px" data-kt-menu="true" id="kt_menu_notifications">
        <!--begin::Heading-->
        <div class="d-flex flex-column bgi-no-repeat rounded-top" style="background-image:url('theme/1/media/misc/menu-header-bg.jpg')">
            <!--begin::Title-->
            <h3 class="text-white fw-semibold px-9 mt-10 mb-6">اعلان ها
                <span class="fs-8 opacity-75 ps-3">24 گزارش</span></h3>
            <!--end::Title-->
            <!--begin::Tabs-->
            <ul class="nav nav-line-tabs nav-line-tabs-2x nav-stretch fw-semibold px-9">
                <li class="nav-item">
                    <a class="nav-link text-white opacity-75 opacity-state-100 pb-4" data-bs-toggle="tab" href="#kt_topbar_notifications_1">هشدارها</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white opacity-75 opacity-state-100 pb-4 active" data-bs-toggle="tab" href="#kt_topbar_notifications_2">بروزرسانی ها</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white opacity-75 opacity-state-100 pb-4" data-bs-toggle="tab" href="#kt_topbar_notifications_3">گزارش</a>
                </li>
            </ul>
            <!--end::Tabs-->
        </div>
        <!--end::Heading-->
        <!--begin::Tab content-->
        <div class="tab-content">
            <!--begin::Tab panel-->
            <div class="tab-pane fade" id="kt_topbar_notifications_1" role="tabpanel">
                <!--begin::items-->
                <div class="scroll-y mh-325px my-5 px-8">
                    <!--begin::item-->
                    <div class="d-flex flex-stack py-4">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-35px me-4">
																	<span class="symbol-label bg-light-primary">
																		<i class="ki-duotone ki-abstract-28 fs-2 text-primary">
																			<span class="path1"></span>
																			<span class="path2"></span>
																		</i>
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="mb-0 me-2">
                                <a href="#" class="fs-6 text-gray-800 text-hover-primary fw-bold">پروژه آلیس</a>
                                <div class="text-gray-500 fs-7">توسعه فاز 1</div>
                            </div>
                            <!--end::Title-->
                        </div>
                        <!--end::Section-->
                        <!--begin::Tags-->
                        <span class="badge badge-light fs-8">1 ساعت</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::item-->
                    <!--begin::item-->
                    <div class="d-flex flex-stack py-4">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-35px me-4">
																	<span class="symbol-label bg-light-danger">
																		<i class="ki-duotone ki-information fs-2 text-danger">
																			<span class="path1"></span>
																			<span class="path2"></span>
																			<span class="path3"></span>
																		</i>
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="mb-0 me-2">
                                <a href="#" class="fs-6 text-gray-800 text-hover-primary fw-bold">محرمانه</a>
                                <div class="text-gray-500 fs-7">اسناد محرمانه کارکنان</div>
                            </div>
                            <!--end::Title-->
                        </div>
                        <!--end::Section-->
                        <!--begin::Tags-->
                        <span class="badge badge-light fs-8">2 ساعت</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::item-->
                    <!--begin::item-->
                    <div class="d-flex flex-stack py-4">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-35px me-4">
																	<span class="symbol-label bg-light-warning">
																		<i class="ki-duotone ki-briefcase fs-2 text-warning">
																			<span class="path1"></span>
																			<span class="path2"></span>
																		</i>
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="mb-0 me-2">
                                <a href="#" class="fs-6 text-gray-800 text-hover-primary fw-bold">کمپانی HR</a>
                                <div class="text-gray-500 fs-7">مشخصات کارکنان را شریک کنید</div>
                            </div>
                            <!--end::Title-->
                        </div>
                        <!--end::Section-->
                        <!--begin::Tags-->
                        <span class="badge badge-light fs-8">5 ساعت</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::item-->
                    <!--begin::item-->
                    <div class="d-flex flex-stack py-4">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-35px me-4">
																	<span class="symbol-label bg-light-success">
																		<i class="ki-duotone ki-abstract-12 fs-2 text-success">
																			<span class="path1"></span>
																			<span class="path2"></span>
																		</i>
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="mb-0 me-2">
                                <a href="#" class="fs-6 text-gray-800 text-hover-primary fw-bold">پروژه ریداکس</a>
                                <div class="text-gray-500 fs-7">تم ادمین جدید</div>
                            </div>
                            <!--end::Title-->
                        </div>
                        <!--end::Section-->
                        <!--begin::Tags-->
                        <span class="badge badge-light fs-8">2 روز</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::item-->
                    <!--begin::item-->
                    <div class="d-flex flex-stack py-4">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-35px me-4">
																	<span class="symbol-label bg-light-primary">
																		<i class="ki-duotone ki-colors-square fs-2 text-primary">
																			<span class="path1"></span>
																			<span class="path2"></span>
																			<span class="path3"></span>
																			<span class="path4"></span>
																		</i>
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="mb-0 me-2">
                                <a href="#" class="fs-6 text-gray-800 text-hover-primary fw-bold">پروژه بریفینگ</a>
                                <div class="text-gray-500 fs-7">به روز رسانی وضعیت راه اندازی محصول</div>
                            </div>
                            <!--end::Title-->
                        </div>
                        <!--end::Section-->
                        <!--begin::Tags-->
                        <span class="badge badge-light fs-8">21 Jan</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::item-->
                    <!--begin::item-->
                    <div class="d-flex flex-stack py-4">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-35px me-4">
																	<span class="symbol-label bg-light-info">
																		<i class="ki-duotone ki-picture fs-2 text-info"></i>
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="mb-0 me-2">
                                <a href="#" class="fs-6 text-gray-800 text-hover-primary fw-bold">دارایی های بنر</a>
                                <div class="text-gray-500 fs-7">مجموعه ای از تصویر بنرها</div>
                            </div>
                            <!--end::Title-->
                        </div>
                        <!--end::Section-->
                        <!--begin::Tags-->
                        <span class="badge badge-light fs-8">21 Jan</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::item-->
                    <!--begin::item-->
                    <div class="d-flex flex-stack py-4">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-35px me-4">
																	<span class="symbol-label bg-light-warning">
																		<i class="ki-duotone ki-color-swatch fs-2 text-warning">
																			<span class="path1"></span>
																			<span class="path2"></span>
																			<span class="path3"></span>
																			<span class="path4"></span>
																			<span class="path5"></span>
																			<span class="path6"></span>
																			<span class="path7"></span>
																			<span class="path8"></span>
																			<span class="path9"></span>
																			<span class="path10"></span>
																			<span class="path11"></span>
																			<span class="path12"></span>
																			<span class="path13"></span>
																			<span class="path14"></span>
																			<span class="path15"></span>
																			<span class="path16"></span>
																			<span class="path17"></span>
																			<span class="path18"></span>
																			<span class="path19"></span>
																			<span class="path20"></span>
																			<span class="path21"></span>
																		</i>
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="mb-0 me-2">
                                <a href="#" class="fs-6 text-gray-800 text-hover-primary fw-bold">نماد دارایی ها</a>
                                <div class="text-gray-500 fs-7">مجموعه ای از ایکون ها</div>
                            </div>
                            <!--end::Title-->
                        </div>
                        <!--end::Section-->
                        <!--begin::Tags-->
                        <span class="badge badge-light fs-8">20 March</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::item-->
                </div>
                <!--end::items-->
                <!--begin::نمایش بیشتر-->
                <div class="py-3 text-center border-top">
                    <a href="pages/user-profile/activity.html" class="btn btn-color-gray-600 btn-active-color-primary">نمایش همه
                        <i class="ki-duotone ki-arrow-left fs-5">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i></a>
                </div>
                <!--end::نمایش بیشتر-->
            </div>
            <!--end::Tab panel-->
            <!--begin::Tab panel-->
            <div class="tab-pane fade show active" id="kt_topbar_notifications_2" role="tabpanel">
                <!--begin::Wrapper-->
                <div class="d-flex flex-column px-9">
                    <!--begin::Section-->
                    <div class="pt-10 pb-0">
                        <!--begin::Title-->
                        <h3 class="text-gray-900 text-center fw-bold">دسترسی حرفه ای را دریافت کنید</h3>
                        <!--end::Title-->
                        <!--begin::Text-->
                        <div class="text-center text-gray-600 fw-semibold pt-1">رئوس مطالب شما را صادق نگه می دارد. آنها جلوی شگفت انگیز بودن شما درمورد رانندگی را می گیرند</div>
                        <!--end::Text-->
                        <!--begin::Actions-->
                        <div class="text-center mt-5 mb-9">
                            <a href="#" class="btn btn-sm btn-primary px-6" data-bs-toggle="modal" data-bs-target="#kt_modal_upgrade_plan">ارتقا دهید</a>
                        </div>
                        <!--end::Actions-->
                    </div>
                    <!--end::Section-->
                    <!--begin::Illustration-->
                    <div class="text-center px-4">
                        <img class="mw-100 mh-200px" alt="image" src="theme/1/media/illustrations/sigma-1/1.png" />
                    </div>
                    <!--end::Illustration-->
                </div>
                <!--end::Wrapper-->
            </div>
            <!--end::Tab panel-->
            <!--begin::Tab panel-->
            <div class="tab-pane fade" id="kt_topbar_notifications_3" role="tabpanel">
                <!--begin::items-->
                <div class="scroll-y mh-325px my-5 px-8">
                    <!--begin::item-->
                    <div class="d-flex flex-stack py-4">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center me-2">
                            <!--begin::Code-->
                            <span class="w-70px badge badge-light-success me-4">200</span>
                            <!--end::Code-->
                            <!--begin::Title-->
                            <a href="#" class="text-gray-800 text-hover-primary fw-semibold">سفارش جدید</a>
                            <!--end::Title-->
                        </div>
                        <!--end::Section-->
                        <!--begin::Tags-->
                        <span class="badge badge-light fs-8">فقط</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::item-->
                    <!--begin::item-->
                    <div class="d-flex flex-stack py-4">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center me-2">
                            <!--begin::Code-->
                            <span class="w-70px badge badge-light-danger me-4">500</span>
                            <!--end::Code-->
                            <!--begin::Title-->
                            <a href="#" class="text-gray-800 text-hover-primary fw-semibold">مشتری جدید</a>
                            <!--end::Title-->
                        </div>
                        <!--end::Section-->
                        <!--begin::Tags-->
                        <span class="badge badge-light fs-8">2 ساعت</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::item-->
                    <!--begin::item-->
                    <div class="d-flex flex-stack py-4">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center me-2">
                            <!--begin::Code-->
                            <span class="w-70px badge badge-light-success me-4">200</span>
                            <!--end::Code-->
                            <!--begin::Title-->
                            <a href="#" class="text-gray-800 text-hover-primary fw-semibold">پردازش درگاه</a>
                            <!--end::Title-->
                        </div>
                        <!--end::Section-->
                        <!--begin::Tags-->
                        <span class="badge badge-light fs-8">5 ساعت</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::item-->
                    <!--begin::item-->
                    <div class="d-flex flex-stack py-4">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center me-2">
                            <!--begin::Code-->
                            <span class="w-70px badge badge-light-warning me-4">300</span>
                            <!--end::Code-->
                            <!--begin::Title-->
                            <a href="#" class="text-gray-800 text-hover-primary fw-semibold">جستجوی کوئری</a>
                            <!--end::Title-->
                        </div>
                        <!--end::Section-->
                        <!--begin::Tags-->
                        <span class="badge badge-light fs-8">2 روز</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::item-->
                    <!--begin::item-->
                    <div class="d-flex flex-stack py-4">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center me-2">
                            <!--begin::Code-->
                            <span class="w-70px badge badge-light-success me-4">200</span>
                            <!--end::Code-->
                            <!--begin::Title-->
                            <a href="#" class="text-gray-800 text-hover-primary fw-semibold">ارتباط کلید دسترسی</a>
                            <!--end::Title-->
                        </div>
                        <!--end::Section-->
                        <!--begin::Tags-->
                        <span class="badge badge-light fs-8">1 هفته</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::item-->
                    <!--begin::item-->
                    <div class="d-flex flex-stack py-4">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center me-2">
                            <!--begin::Code-->
                            <span class="w-70px badge badge-light-success me-4">200</span>
                            <!--end::Code-->
                            <!--begin::Title-->
                            <a href="#" class="text-gray-800 text-hover-primary fw-semibold">دیتابیس بازگرداندن</a>
                            <!--end::Title-->
                        </div>
                        <!--end::Section-->
                        <!--begin::Tags-->
                        <span class="badge badge-light fs-8">Mar 5</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::item-->
                    <!--begin::item-->
                    <div class="d-flex flex-stack py-4">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center me-2">
                            <!--begin::Code-->
                            <span class="w-70px badge badge-light-warning me-4">300</span>
                            <!--end::Code-->
                            <!--begin::Title-->
                            <a href="#" class="text-gray-800 text-hover-primary fw-semibold">بروزرسانی سیستم</a>
                            <!--end::Title-->
                        </div>
                        <!--end::Section-->
                        <!--begin::Tags-->
                        <span class="badge badge-light fs-8">May 15</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::item-->
                    <!--begin::item-->
                    <div class="d-flex flex-stack py-4">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center me-2">
                            <!--begin::Code-->
                            <span class="w-70px badge badge-light-warning me-4">300</span>
                            <!--end::Code-->
                            <!--begin::Title-->
                            <a href="#" class="text-gray-800 text-hover-primary fw-semibold">اپدیت سیستم عامل سرور</a>
                            <!--end::Title-->
                        </div>
                        <!--end::Section-->
                        <!--begin::Tags-->
                        <span class="badge badge-light fs-8">Apr 3</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::item-->
                    <!--begin::item-->
                    <div class="d-flex flex-stack py-4">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center me-2">
                            <!--begin::Code-->
                            <span class="w-70px badge badge-light-warning me-4">300</span>
                            <!--end::Code-->
                            <!--begin::Title-->
                            <a href="#" class="text-gray-800 text-hover-primary fw-semibold">برگشت کلید دسترسی</a>
                            <!--end::Title-->
                        </div>
                        <!--end::Section-->
                        <!--begin::Tags-->
                        <span class="badge badge-light fs-8">Jun 30</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::item-->
                    <!--begin::item-->
                    <div class="d-flex flex-stack py-4">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center me-2">
                            <!--begin::Code-->
                            <span class="w-70px badge badge-light-danger me-4">500</span>
                            <!--end::Code-->
                            <!--begin::Title-->
                            <a href="#" class="text-gray-800 text-hover-primary fw-semibold">روند بازپرداخت</a>
                            <!--end::Title-->
                        </div>
                        <!--end::Section-->
                        <!--begin::Tags-->
                        <span class="badge badge-light fs-8">Jul 10</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::item-->
                    <!--begin::item-->
                    <div class="d-flex flex-stack py-4">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center me-2">
                            <!--begin::Code-->
                            <span class="w-70px badge badge-light-danger me-4">500</span>
                            <!--end::Code-->
                            <!--begin::Title-->
                            <a href="#" class="text-gray-800 text-hover-primary fw-semibold">روند برداشت</a>
                            <!--end::Title-->
                        </div>
                        <!--end::Section-->
                        <!--begin::Tags-->
                        <span class="badge badge-light fs-8">Sep 10</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::item-->
                    <!--begin::item-->
                    <div class="d-flex flex-stack py-4">
                        <!--begin::Section-->
                        <div class="d-flex align-items-center me-2">
                            <!--begin::Code-->
                            <span class="w-70px badge badge-light-danger me-4">500</span>
                            <!--end::Code-->
                            <!--begin::Title-->
                            <a href="#" class="text-gray-800 text-hover-primary fw-semibold">وظایف نامه</a>
                            <!--end::Title-->
                        </div>
                        <!--end::Section-->
                        <!--begin::Tags-->
                        <span class="badge badge-light fs-8">Dec 10</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::item-->
                </div>
                <!--end::items-->
                <!--begin::نمایش بیشتر-->
                <div class="py-3 text-center border-top">
                    <a href="pages/user-profile/activity.html" class="btn btn-color-gray-600 btn-active-color-primary">نمایش همه
                        <i class="ki-duotone ki-arrow-left fs-5">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i></a>
                </div>
                <!--end::نمایش بیشتر-->
            </div>
            <!--end::Tab panel-->
        </div>
        <!--end::Tab content-->
    </div>
    <!--end::Menu-->
    <!--end::Menu wrapper-->
    <!--begin::Modal - upgrade plan-->
    <div class="modal fade" id="kt_modal_upgrade_plan" tabindex="-1" aria-hidden="true">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-xl">
            <!--begin::Modal content-->
            <div class="modal-content rounded">
                <!--begin::Modal header-->
                <div class="modal-header justify-content-end border-0 pb-0">
                    <!--begin::Close-->
                    <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body pt-0 pb-15 px-5 px-xl-20">
                    <!--begin::Heading-->
                    <div class="mb-13 text-center">
                        <h1 class="mb-3">یک برنامه را ارتقا کنید</h1>
                        <div class="text-muted fw-semibold fs-5">اگر به اطلاعات لازم دارید ، لطفاً بررسی کنید
                            <a href="#" class="link-primary fw-bold">دستورالعمل قیمت گذاری</a>.</div>
                    </div>
                    <!--end::Heading-->
                    <!--begin::برنامه ریزی ها-->
                    <div class="d-flex flex-column">
                        <!--begin::Nav group-->
                        <div class="nav-group nav-group-outline mx-auto" data-kt-buttons="true">
                            <button class="btn btn-color-gray-500 btn-active btn-active-secondary px-6 py-3 me-2 active" data-kt-plan="month">ماهانه</button>
                            <button class="btn btn-color-gray-500 btn-active btn-active-secondary px-6 py-3" data-kt-plan="annual">سالانه</button>
                        </div>
                        <!--end::Nav group-->
                        <!--begin::Row-->
                        <div class="row mt-10">
                            <!--begin::Col-->
                            <div class="col-lg-6 mb-10 mb-lg-0">
                                <!--begin::Tabs-->
                                <div class="nav flex-column">
                                    <!--begin::Tab link-->
                                    <label class="nav-link btn btn-outline btn-outline-dashed btn-color-dark btn-active btn-active-primary d-flex flex-stack text-start p-6 active mb-6" data-bs-toggle="tab" data-bs-target="#kt_upgrade_plan_startup">
                                        <!--end::توضیحات-->
                                        <div class="d-flex align-items-center me-2">
                                            <!--begin::رادیو-->
                                            <div class="form-check form-check-custom form-check-solid form-check-success flex-shrink-0 me-6">
                                                <input class="form-check-input" type="radio" name="plan" checked="checked" value="startup" />
                                            </div>
                                            <!--end::رادیو-->
                                            <!--begin::Info-->
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center fs-2 fw-bold flex-wrap">استارت آپ</div>
                                                <div class="fw-semibold opacity-75">برای استارت آپ ها</div>
                                            </div>
                                            <!--end::Info-->
                                        </div>
                                        <!--end::توضیحات-->
                                        <!--begin::قیمت-->
                                        <div class="ms-5">
                                            <span class="mb-2">ت</span>
                                            <span class="fs-3x fw-bold" data-kt-plan-price-month="39" data-kt-plan-price-annual="399">39</span>
                                            <span class="fs-7 opacity-50">/
												<span data-kt-element="period">دوشنبه</span></span>
                                        </div>
                                        <!--end::قیمت-->
                                    </label>
                                    <!--end::Tab link-->
                                    <!--begin::Tab link-->
                                    <label class="nav-link btn btn-outline btn-outline-dashed btn-color-dark btn-active btn-active-primary d-flex flex-stack text-start p-6 mb-6" data-bs-toggle="tab" data-bs-target="#kt_upgrade_plan_advanced">
                                        <!--end::توضیحات-->
                                        <div class="d-flex align-items-center me-2">
                                            <!--begin::رادیو-->
                                            <div class="form-check form-check-custom form-check-solid form-check-success flex-shrink-0 me-6">
                                                <input class="form-check-input" type="radio" name="plan" value="advanced" />
                                            </div>
                                            <!--end::رادیو-->
                                            <!--begin::Info-->
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center fs-2 fw-bold flex-wrap">پیشرفته</div>
                                                <div class="fw-semibold opacity-75">برتر for 100+ team size</div>
                                            </div>
                                            <!--end::Info-->
                                        </div>
                                        <!--end::توضیحات-->
                                        <!--begin::قیمت-->
                                        <div class="ms-5">
                                            <span class="mb-2">ت</span>
                                            <span class="fs-3x fw-bold" data-kt-plan-price-month="339" data-kt-plan-price-annual="3399">339</span>
                                            <span class="fs-7 opacity-50">/
												<span data-kt-element="period">دوشنبه</span></span>
                                        </div>
                                        <!--end::قیمت-->
                                    </label>
                                    <!--end::Tab link-->
                                    <!--begin::Tab link-->
                                    <label class="nav-link btn btn-outline btn-outline-dashed btn-color-dark btn-active btn-active-primary d-flex flex-stack text-start p-6 mb-6" data-bs-toggle="tab" data-bs-target="#kt_upgrade_plan_enterprise">
                                        <!--end::توضیحات-->
                                        <div class="d-flex align-items-center me-2">
                                            <!--begin::رادیو-->
                                            <div class="form-check form-check-custom form-check-solid form-check-success flex-shrink-0 me-6">
                                                <input class="form-check-input" type="radio" name="plan" value="enterprise" />
                                            </div>
                                            <!--end::رادیو-->
                                            <!--begin::Info-->
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center fs-2 fw-bold flex-wrap">شرکت، پروژه
                                                    <span class="badge badge-light-success ms-2 py-2 px-3 fs-7">محبوب</span></div>
                                                <div class="fw-semibold opacity-75">ارزش برتر برای تیم 1000+</div>
                                            </div>
                                            <!--end::Info-->
                                        </div>
                                        <!--end::توضیحات-->
                                        <!--begin::قیمت-->
                                        <div class="ms-5">
                                            <span class="mb-2">ت</span>
                                            <span class="fs-3x fw-bold" data-kt-plan-price-month="999" data-kt-plan-price-annual="9999">999</span>
                                            <span class="fs-7 opacity-50">/
												<span data-kt-element="period">دوشنبه</span></span>
                                        </div>
                                        <!--end::قیمت-->
                                    </label>
                                    <!--end::Tab link-->
                                    <!--begin::Tab link-->
                                    <label class="nav-link btn btn-outline btn-outline-dashed btn-color-dark btn-active btn-active-primary d-flex flex-stack text-start p-6 mb-6" data-bs-toggle="tab" data-bs-target="#kt_upgrade_plan_custom">
                                        <!--end::توضیحات-->
                                        <div class="d-flex align-items-center me-2">
                                            <!--begin::رادیو-->
                                            <div class="form-check form-check-custom form-check-solid form-check-success flex-shrink-0 me-6">
                                                <input class="form-check-input" type="radio" name="plan" value="custom" />
                                            </div>
                                            <!--end::رادیو-->
                                            <!--begin::Info-->
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center fs-2 fw-bold flex-wrap">سفارشی</div>
                                                <div class="fw-semibold opacity-75">دوباره مجوز سفارشی را دوباره تهیه کنید</div>
                                            </div>
                                            <!--end::Info-->
                                        </div>
                                        <!--end::توضیحات-->
                                        <!--begin::قیمت-->
                                        <div class="ms-5">
                                            <a href="#" class="btn btn-sm btn-success">تماس با ما</a>
                                        </div>
                                        <!--end::قیمت-->
                                    </label>
                                    <!--end::Tab link-->
                                </div>
                                <!--end::Tabs-->
                            </div>
                            <!--end::Col-->
                            <!--begin::Col-->
                            <div class="col-lg-6">
                                <!--begin::Tab content-->
                                <div class="tab-content rounded h-100 bg-light p-10">
                                    <!--begin::Tab Pane-->
                                    <div class="tab-pane fade show active" id="kt_upgrade_plan_startup">
                                        <!--begin::Heading-->
                                        <div class="pb-5">
                                            <h2 class="fw-bold text-gray-900">برنامه ی استارت اپ شما چیست؟</h2>
                                            <div class="text-muted fw-semibold">بهینه برای تیم بیش از 10 نفر و استارت آپ جدید</div>
                                        </div>
                                        <!--end::Heading-->
                                        <!--begin::Body-->
                                        <div class="pt-1">
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">فعال ترین کاربران</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">حداکثر 30 ادغام پروژه</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">ماژول تجزیه و تحلیل</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-muted flex-grow-1">ماژول دارایی ، مالیه ، سرمایه گذاری</span>
                                                <i class="ki-duotone ki-cross-circle fs-1">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-muted flex-grow-1">ماژول حسابداری</span>
                                                <i class="ki-duotone ki-cross-circle fs-1">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-muted flex-grow-1">بستر شبکه</span>
                                                <i class="ki-duotone ki-cross-circle fs-1">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center">
                                                <span class="fw-semibold fs-5 text-muted flex-grow-1">فضای نامحدود ابر</span>
                                                <i class="ki-duotone ki-cross-circle fs-1">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                        </div>
                                        <!--end::Body-->
                                    </div>
                                    <!--end::Tab Pane-->
                                    <!--begin::Tab Pane-->
                                    <div class="tab-pane fade" id="kt_upgrade_plan_advanced">
                                        <!--begin::Heading-->
                                        <div class="pb-5">
                                            <h2 class="fw-bold text-gray-900">برنامه ی استارت اپ شما چیست؟</h2>
                                            <div class="text-muted fw-semibold">بهینه برای تیم 100+ شششرکت الکترونیکی و رشد یافته</div>
                                        </div>
                                        <!--end::Heading-->
                                        <!--begin::Body-->
                                        <div class="pt-1">
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">فعال ترین کاربران</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">حداکثر 30 ادغام پروژه</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">ماژول تجزیه و تحلیل</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">ماژول دارایی ، مالیه ، سرمایه گذاری</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">ماژول حسابداری</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-muted flex-grow-1">بستر شبکه</span>
                                                <i class="ki-duotone ki-cross-circle fs-1">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center">
                                                <span class="fw-semibold fs-5 text-muted flex-grow-1">فضای نامحدود ابر</span>
                                                <i class="ki-duotone ki-cross-circle fs-1">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                        </div>
                                        <!--end::Body-->
                                    </div>
                                    <!--end::Tab Pane-->
                                    <!--begin::Tab Pane-->
                                    <div class="tab-pane fade" id="kt_upgrade_plan_enterprise">
                                        <!--begin::Heading-->
                                        <div class="pb-5">
                                            <h2 class="fw-bold text-gray-900">برنامه ی استارت اپ شما چیست؟</h2>
                                            <div class="text-muted fw-semibold">Optimal for 1000+ team و بنگاه اقتصادی</div>
                                        </div>
                                        <!--end::Heading-->
                                        <!--begin::Body-->
                                        <div class="pt-1">
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">فعال ترین کاربران</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">حداکثر 30 ادغام پروژه</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">ماژول تجزیه و تحلیل</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">ماژول دارایی ، مالیه ، سرمایه گذاری</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">ماژول حسابداری</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">بستر شبکه</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">فضای نامحدود ابر</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                        </div>
                                        <!--end::Body-->
                                    </div>
                                    <!--end::Tab Pane-->
                                    <!--begin::Tab Pane-->
                                    <div class="tab-pane fade" id="kt_upgrade_plan_custom">
                                        <!--begin::Heading-->
                                        <div class="pb-5">
                                            <h2 class="fw-bold text-gray-900">برنامه ی استارت اپ شما چیست؟</h2>
                                            <div class="text-muted fw-semibold">بهینه برای شرکت ها</div>
                                        </div>
                                        <!--end::Heading-->
                                        <!--begin::Body-->
                                        <div class="pt-1">
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">نامحدود کاربران</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">نامحدود پروژه ادغام ها</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">ماژول تجزیه و تحلیل</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">ماژول دارایی ، مالیه ، سرمایه گذاری</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">ماژول حسابداری</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center mb-7">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">بستر شبکه</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                            <!--begin::item-->
                                            <div class="d-flex align-items-center">
                                                <span class="fw-semibold fs-5 text-gray-700 flex-grow-1">فضای نامحدود ابر</span>
                                                <i class="ki-duotone ki-check-circle fs-1 text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                            <!--end::item-->
                                        </div>
                                        <!--end::Body-->
                                    </div>
                                    <!--end::Tab Pane-->
                                </div>
                                <!--end::Tab content-->
                            </div>
                            <!--end::Col-->
                        </div>
                        <!--end::Row-->
                    </div>
                    <!--end::برنامه ریزی ها-->
                    <!--begin::Actions-->
                    <div class="d-flex flex-center flex-row-fluid pt-12">
                        <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-primary" id="kt_modal_upgrade_plan_btn">
                            <!--begin::Indicatیا label-->
                            <span class="indicator-label">ارتقا طرح</span>
                            <!--end::Indicatیا label-->
                            <!--begin::Indicatیا progress-->
                            <span class="indicator-progress">لطفا صبر کنید...
								<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            <!--end::Indicatیا progress-->
                        </button>
                    </div>
                    <!--end::Actions-->
                </div>
                <!--end::Modal body-->
            </div>
            <!--end::Modal content-->
        </div>
        <!--end::Modal dialog-->
    </div>
    <!--end::Modal - upgrade plan-->
</div>
