<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="d-flex align-items-stretch ms-1 ms-lg-3">
    <!--begin::جستجو-->
    <div id="kt_header_search" class="header-search d-flex align-items-stretch" data-kt-search-keypress="true" data-kt-search-min-length="2" data-kt-search-enter="enter" data-kt-search-layout="menu" data-kt-menu-trigger="auto" data-kt-menu-overflow="false" data-kt-menu-permanent="true" data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'bottom-end' : 'bottom-start' }}">
        <!--begin::جستجو toggle-->
        <div class="d-flex align-items-center" data-kt-search-element="toggle" id="kt_header_search_toggle">
            <div class="btn btn-icon btn-active-light-primary btn-custom w-30px h-30px w-md-40px h-md-40px">
                <i class="ki-duotone ki-magnifier fs-1">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </div>
        </div>
        <!--end::جستجو toggle-->
        <!--begin::Menu-->
        <div data-kt-search-element="content" class="menu menu-sub menu-sub-dropdown p-7 w-325px w-md-375px">
            <!--begin::Wrapper-->
            <div data-kt-search-element="wrapper">
                <!--begin::form-->
                <form data-kt-search-element="form" class="w-100 position-relative mb-3" autocomplete="off">
                    <!--begin::Icon-->
                    <i class="ki-duotone ki-magnifier fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-0">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <!--end::Icon-->
                    <!--begin::Input-->
                    <input type="text" class="search-input form-control form-control-flush ps-10" name="search" value="" placeholder="جستجو..." data-kt-search-element="input" />
                    <!--end::Input-->
                    <!--begin::Spinner-->
                    <span class="search-spinner position-absolute top-50 end-0 translate-middle-y lh-0 d-none me-1" data-kt-search-element="spinner">
															<span class="spinner-border h-15px w-15px align-middle text-gray-500"></span>
														</span>
                    <!--end::Spinner-->
                    <!--begin::ریست-->
                    <span class="search-reset btn btn-flush btn-active-color-primary position-absolute top-50 end-0 translate-middle-y lh-0 d-none" data-kt-search-element="clear">
															<i class="ki-duotone ki-cross fs-2 fs-lg-1 me-0">
																<span class="path1"></span>
																<span class="path2"></span>
															</i>
														</span>
                    <!--end::ریست-->
                    <!--begin::Toolbar-->
                    <div class="position-absolute top-50 end-0 translate-middle-y" data-kt-search-element="toolbar">
                        <!--begin::اولویت ها toggle-->
                        <div data-kt-search-element="preferences-show" class="btn btn-icon w-20px btn-sm btn-active-color-primary me-1" data-bs-toggle="tooltip" title="نمایش تنظیمات جستجو">
                            <i class="ki-duotone ki-setting-2 fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>
                        <!--end::اولویت ها toggle-->
                        <!--begin::پیشرفته search toggle-->
                        <div data-kt-search-element="advanced-options-form-show" class="btn btn-icon w-20px btn-sm btn-active-color-primary" data-bs-toggle="tooltip" title="نمایش گزینه های جستجوی بیشتر">
                            <i class="ki-duotone ki-down fs-2"></i>
                        </div>
                        <!--end::پیشرفته search toggle-->
                    </div>
                    <!--end::Toolbar-->
                </form>
                <!--end::form-->
                <!--begin::separator-->
                <div class="separator border-gray-200 mb-6"></div>
                <!--end::separator-->
                <!--begin::اخیرly viewed-->
                <div data-kt-search-element="results" class="d-none">
                    <!--begin::items-->
                    <div class="scroll-y mh-200px mh-lg-350px">
                        <!--begin::دسته بندی title-->
                        <h3 class="fs-5 text-muted m-0 pb-5" data-kt-search-element="category-title">کاربران</h3>
                        <!--end::دسته بندی title-->
                        <!--begin::item-->
                        <a href="#" class="d-flex text-gray-900 text-hover-primary align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
                                <img src="theme/1/media/avatars/300-6.jpg" alt="" />
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column justify-content-start fw-semibold">
                                <span class="fs-6 fw-semibold">کارینا کالرک</span>
                                <span class="fs-7 fw-semibold text-muted">مدیریت بازاریابی</span>
                            </div>
                            <!--end::Title-->
                        </a>
                        <!--end::item-->
                        <!--begin::item-->
                        <a href="#" class="d-flex text-gray-900 text-hover-primary align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
                                <img src="theme/1/media/avatars/300-2.jpg" alt="" />
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column justify-content-start fw-semibold">
                                <span class="fs-6 fw-semibold">غلامی</span>
                                <span class="fs-7 fw-semibold text-muted">مهندس نرم افزار</span>
                            </div>
                            <!--end::Title-->
                        </a>
                        <!--end::item-->
                        <!--begin::item-->
                        <a href="#" class="d-flex text-gray-900 text-hover-primary align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
                                <img src="theme/1/media/avatars/300-9.jpg" alt="" />
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column justify-content-start fw-semibold">
                                <span class="fs-6 fw-semibold">حسینی</span>
                                <span class="fs-7 fw-semibold text-muted">طراح یو ای و یوایکس</span>
                            </div>
                            <!--end::Title-->
                        </a>
                        <!--end::item-->
                        <!--begin::item-->
                        <a href="#" class="d-flex text-gray-900 text-hover-primary align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
                                <img src="theme/1/media/avatars/300-14.jpg" alt="" />
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column justify-content-start fw-semibold">
                                <span class="fs-6 fw-semibold">موسوی</span>
                                <span class="fs-7 fw-semibold text-muted">کارگردان هنری</span>
                            </div>
                            <!--end::Title-->
                        </a>
                        <!--end::item-->
                        <!--begin::item-->
                        <a href="#" class="d-flex text-gray-900 text-hover-primary align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
                                <img src="theme/1/media/avatars/300-11.jpg" alt="" />
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column justify-content-start fw-semibold">
                                <span class="fs-6 fw-semibold">بناری</span>
                                <span class="fs-7 fw-semibold text-muted">مدیر سیستم</span>
                            </div>
                            <!--end::Title-->
                        </a>
                        <!--end::item-->
                        <!--begin::دسته بندی title-->
                        <h3 class="fs-5 text-muted m-0 pt-5 pb-5" data-kt-search-element="category-title">مشتریان</h3>
                        <!--end::دسته بندی title-->
                        <!--begin::item-->
                        <a href="#" class="d-flex text-gray-900 text-hover-primary align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
																	<span class="symbol-label bg-light">
																		<img class="w-20px h-20px" src="theme/1/media/svg/brand-logos/volicity-9.svg" alt="" />
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column justify-content-start fw-semibold">
                                <span class="fs-6 fw-semibold">کمپانی برندینگ</span>
                                <span class="fs-7 fw-semibold text-muted">طراح</span>
                            </div>
                            <!--end::Title-->
                        </a>
                        <!--end::item-->
                        <!--begin::item-->
                        <a href="#" class="d-flex text-gray-900 text-hover-primary align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
																	<span class="symbol-label bg-light">
																		<img class="w-20px h-20px" src="theme/1/media/svg/brand-logos/tvit.svg" alt="" />
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column justify-content-start fw-semibold">
                                <span class="fs-6 fw-semibold">کمپانی برندینگ</span>
                                <span class="fs-7 fw-semibold text-muted">توسعه دهنده وب</span>
                            </div>
                            <!--end::Title-->
                        </a>
                        <!--end::item-->
                        <!--begin::item-->
                        <a href="#" class="d-flex text-gray-900 text-hover-primary align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
																	<span class="symbol-label bg-light">
																		<img class="w-20px h-20px" src="theme/1/media/svg/misc/infography.svg" alt="" />
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column justify-content-start fw-semibold">
                                <span class="fs-6 fw-semibold">بیزینس آنالیتیکس اپلیکیشن</span>
                                <span class="fs-7 fw-semibold text-muted">مدیریت</span>
                            </div>
                            <!--end::Title-->
                        </a>
                        <!--end::item-->
                        <!--begin::item-->
                        <a href="#" class="d-flex text-gray-900 text-hover-primary align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
																	<span class="symbol-label bg-light">
																		<img class="w-20px h-20px" src="theme/1/media/svg/brand-logos/leaf.svg" alt="" />
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column justify-content-start fw-semibold">
                                <span class="fs-6 fw-semibold">اکولیف اپلیکیشن لانچ</span>
                                <span class="fs-7 fw-semibold text-muted">بازاریابی</span>
                            </div>
                            <!--end::Title-->
                        </a>
                        <!--end::item-->
                        <!--begin::item-->
                        <a href="#" class="d-flex text-gray-900 text-hover-primary align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
																	<span class="symbol-label bg-light">
																		<img class="w-20px h-20px" src="theme/1/media/svg/brand-logos/tower.svg" alt="" />
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column justify-content-start fw-semibold">
                                <span class="fs-6 fw-semibold">قدرت گروه وب سایت</span>
                                <span class="fs-7 fw-semibold text-muted">گوگل کلمات پیشنهادی</span>
                            </div>
                            <!--end::Title-->
                        </a>
                        <!--end::item-->
                        <!--begin::دسته بندی title-->
                        <h3 class="fs-5 text-muted m-0 pt-5 pb-5" data-kt-search-element="category-title">پروژه ها</h3>
                        <!--end::دسته بندی title-->
                        <!--begin::item-->
                        <a href="#" class="d-flex text-gray-900 text-hover-primary align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
																	<span class="symbol-label bg-light">
																		<i class="ki-duotone ki-notepad fs-2 text-primary">
																			<span class="path1"></span>
																			<span class="path2"></span>
																			<span class="path3"></span>
																			<span class="path4"></span>
																			<span class="path5"></span>
																		</i>
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column">
                                <span class="fs-6 fw-semibold">پروژه توسط ساتراس وب</span>
                                <span class="fs-7 fw-semibold text-muted">#45670</span>
                            </div>
                            <!--end::Title-->
                        </a>
                        <!--end::item-->
                        <!--begin::item-->
                        <a href="#" class="d-flex text-gray-900 text-hover-primary align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
																	<span class="symbol-label bg-light">
																		<i class="ki-duotone ki-frame fs-2 text-primary">
																			<span class="path1"></span>
																			<span class="path2"></span>
																			<span class="path3"></span>
																			<span class="path4"></span>
																		</i>
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column">
                                <span class="fs-6 fw-semibold">اپلیکیشن فونیکس</span>
                                <span class="fs-7 fw-semibold text-muted">#45690</span>
                            </div>
                            <!--end::Title-->
                        </a>
                        <!--end::item-->
                        <!--begin::item-->
                        <a href="#" class="d-flex text-gray-900 text-hover-primary align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
																	<span class="symbol-label bg-light">
																		<i class="ki-duotone ki-message-text-2 fs-2 text-primary">
																			<span class="path1"></span>
																			<span class="path2"></span>
																			<span class="path3"></span>
																		</i>
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column">
                                <span class="fs-6 fw-semibold">دارایی، مالیه، سرمایه گذاری نظارت بر بحث SAAS</span>
                                <span class="fs-7 fw-semibold text-muted">#21090</span>
                            </div>
                            <!--end::Title-->
                        </a>
                        <!--end::item-->
                        <!--begin::item-->
                        <a href="#" class="d-flex text-gray-900 text-hover-primary align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
																	<span class="symbol-label bg-light">
																		<i class="ki-duotone ki-profile-circle fs-2 text-primary">
																			<span class="path1"></span>
																			<span class="path2"></span>
																			<span class="path3"></span>
																		</i>
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column">
                                <span class="fs-6 fw-semibold">داشبورد انالیتیکس</span>
                                <span class="fs-7 fw-semibold text-muted">#34560</span>
                            </div>
                            <!--end::Title-->
                        </a>
                        <!--end::item-->
                    </div>
                    <!--end::items-->
                </div>
                <!--end::اخیرly viewed-->
                <!--begin::اخیرly viewed-->
                <div class="mb-5" data-kt-search-element="main">
                    <!--begin::Heading-->
                    <div class="d-flex flex-stack fw-semibold mb-4">
                        <!--begin::Tags-->
                        <span class="text-muted fs-6 me-2">نتایج اخیر:</span>
                        <!--end::Tags-->
                    </div>
                    <!--end::Heading-->
                    <!--begin::items-->
                    <div class="scroll-y mh-200px mh-lg-325px">
                        <!--begin::item-->
                        <div class="d-flex align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
																	<span class="symbol-label bg-light">
																		<i class="ki-duotone ki-laptop fs-2 text-primary">
																			<span class="path1"></span>
																			<span class="path2"></span>
																		</i>
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column">
                                <a href="#" class="fs-6 text-gray-800 text-hover-primary fw-semibold">ساتراس وب</a>
                                <span class="fs-7 text-muted fw-semibold">#45789</span>
                            </div>
                            <!--end::Title-->
                        </div>
                        <!--end::item-->
                        <!--begin::item-->
                        <div class="d-flex align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
																	<span class="symbol-label bg-light">
																		<i class="ki-duotone ki-chart-simple fs-2 text-primary">
																			<span class="path1"></span>
																			<span class="path2"></span>
																			<span class="path3"></span>
																			<span class="path4"></span>
																		</i>
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column">
                                <a href="#" class="fs-6 text-gray-800 text-hover-primary fw-semibold">"ملاقات پروژه ها</a>
                                <span class="fs-7 text-muted fw-semibold">#84050</span>
                            </div>
                            <!--end::Title-->
                        </div>
                        <!--end::item-->
                        <!--begin::item-->
                        <div class="d-flex align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
																	<span class="symbol-label bg-light">
																		<i class="ki-duotone ki-chart fs-2 text-primary">
																			<span class="path1"></span>
																			<span class="path2"></span>
																		</i>
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column">
                                <a href="#" class="fs-6 text-gray-800 text-hover-primary fw-semibold">اپلیکیشن مانیتورینگ</a>
                                <span class="fs-7 text-muted fw-semibold">#84250</span>
                            </div>
                            <!--end::Title-->
                        </div>
                        <!--end::item-->
                        <!--begin::item-->
                        <div class="d-flex align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
																	<span class="symbol-label bg-light">
																		<i class="ki-duotone ki-chart-line-down fs-2 text-primary">
																			<span class="path1"></span>
																			<span class="path2"></span>
																		</i>
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column">
                                <a href="#" class="fs-6 text-gray-800 text-hover-primary fw-semibold">سوالات متداول</a>
                                <span class="fs-7 text-muted fw-semibold">#67945</span>
                            </div>
                            <!--end::Title-->
                        </div>
                        <!--end::item-->
                        <!--begin::item-->
                        <div class="d-flex align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
																	<span class="symbol-label bg-light">
																		<i class="ki-duotone ki-sms fs-2 text-primary">
																			<span class="path1"></span>
																			<span class="path2"></span>
																		</i>
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column">
                                <a href="#" class="fs-6 text-gray-800 text-hover-primary fw-semibold">"اپلیکیشن کیت پرو</a>
                                <span class="fs-7 text-muted fw-semibold">#84250</span>
                            </div>
                            <!--end::Title-->
                        </div>
                        <!--end::item-->
                        <!--begin::item-->
                        <div class="d-flex align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
																	<span class="symbol-label bg-light">
																		<i class="ki-duotone ki-bank fs-2 text-primary">
																			<span class="path1"></span>
																			<span class="path2"></span>
																		</i>
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column">
                                <a href="#" class="fs-6 text-gray-800 text-hover-primary fw-semibold">اپلیکیشن فونیکس</a>
                                <span class="fs-7 text-muted fw-semibold">#45690</span>
                            </div>
                            <!--end::Title-->
                        </div>
                        <!--end::item-->
                        <!--begin::item-->
                        <div class="d-flex align-items-center mb-5">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-40px me-4">
																	<span class="symbol-label bg-light">
																		<i class="ki-duotone ki-chart-line-down fs-2 text-primary">
																			<span class="path1"></span>
																			<span class="path2"></span>
																		</i>
																	</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Title-->
                            <div class="d-flex flex-column">
                                <a href="#" class="fs-6 text-gray-800 text-hover-primary fw-semibold">"صفحه فرود طراح" لانچ</a>
                                <span class="fs-7 text-muted fw-semibold">#24005</span>
                            </div>
                            <!--end::Title-->
                        </div>
                        <!--end::item-->
                    </div>
                    <!--end::items-->
                </div>
                <!--end::اخیرly viewed-->
                <!--begin::Empty-->
                <div data-kt-search-element="empty" class="text-center d-none">
                    <!--begin::Icon-->
                    <div class="pt-10 pb-10">
                        <i class="ki-duotone ki-search-list fs-4x opacity-50">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                    </div>
                    <!--end::Icon-->
                    <!--begin::Message-->
                    <div class="pb-15 fw-semibold">
                        <h3 class="text-gray-600 fs-5 mb-2">نتیجه ای یافت نشد</h3>
                        <div class="text-muted fs-7">لطفاً با یک پرسش دیگر دوباره امتحان کنید</div>
                    </div>
                    <!--end::Message-->
                </div>
                <!--end::Empty-->
            </div>
            <!--end::Wrapper-->
            <!--begin::اولویت ها-->
            <form data-kt-search-element="advanced-options-form" class="pt-1 d-none">
                <!--begin::Heading-->
                <h3 class="fw-semibold text-gray-900 mb-7">پیشرفته جستجو</h3>
                <!--end::Heading-->
                <!--begin::Input group-->
                <div class="mb-5">
                    <input type="text" class="form-control form-control-sm form-control-solid" placeholder="متن را وارد کنید" name="query" />
                </div>
                <!--end::Input group-->
                <!--begin::Input group-->
                <div class="mb-5">
                    <!--begin::رادیو group-->
                    <div class="nav-group nav-group-fluid">
                        <!--begin::Option-->
                        <label>
                            <input type="radio" class="btn-check" name="type" value="has" checked="checked" />
                            <span class="btn btn-sm btn-color-muted btn-active btn-active-primary">همه</span>
                        </label>
                        <!--end::Option-->
                        <!--begin::Option-->
                        <label>
                            <input type="radio" class="btn-check" name="type" value="users" />
                            <span class="btn btn-sm btn-color-muted btn-active btn-active-primary px-4">کاربران</span>
                        </label>
                        <!--end::Option-->
                        <!--begin::Option-->
                        <label>
                            <input type="radio" class="btn-check" name="type" value="orders" />
                            <span class="btn btn-sm btn-color-muted btn-active btn-active-primary px-4">سفارشات</span>
                        </label>
                        <!--end::Option-->
                        <!--begin::Option-->
                        <label>
                            <input type="radio" class="btn-check" name="type" value="projects" />
                            <span class="btn btn-sm btn-color-muted btn-active btn-active-primary px-4">پروژه ها</span>
                        </label>
                        <!--end::Option-->
                    </div>
                    <!--end::رادیو group-->
                </div>
                <!--end::Input group-->
                <!--begin::Input group-->
                <div class="mb-5">
                    <input type="text" name="assignedto" class="form-control form-control-sm form-control-solid" placeholder="اختصاص دادن" value="" />
                </div>
                <!--end::Input group-->
                <!--begin::Input group-->
                <div class="mb-5">
                    <input type="text" name="collaborators" class="form-control form-control-sm form-control-solid" placeholder="همکاران" value="" />
                </div>
                <!--end::Input group-->
                <!--begin::Input group-->
                <div class="mb-5">
                    <!--begin::رادیو group-->
                    <div class="nav-group nav-group-fluid">
                        <!--begin::Option-->
                        <label>
                            <input type="radio" class="btn-check" name="attachment" value="has" checked="checked" />
                            <span class="btn btn-sm btn-color-muted btn-active btn-active-primary">ضمیمه دار</span>
                        </label>
                        <!--end::Option-->
                        <!--begin::Option-->
                        <label>
                            <input type="radio" class="btn-check" name="attachment" value="any" />
                            <span class="btn btn-sm btn-color-muted btn-active btn-active-primary px-4">هیچ</span>
                        </label>
                        <!--end::Option-->
                    </div>
                    <!--end::رادیو group-->
                </div>
                <!--end::Input group-->
                <!--begin::Input group-->
                <div class="mb-5">
                    <select name="timezone" aria-label="انتخاب زمان محلی" data-control="select2" data-dropdown-parent="#kt_header_search" data-placeholder="date_period" class="form-select form-select-sm form-select-solid">
                        <option value="next">بعدی</option>
                        <option value="last">قبلی</option>
                        <option value="between">بین</option>
                        <option value="on">روشن</option>
                    </select>
                </div>
                <!--end::Input group-->
                <!--begin::Input group-->
                <div class="row mb-8">
                    <!--begin::Col-->
                    <div class="col-6">
                        <input type="number" name="date_number" class="form-control form-control-sm form-control-solid" placeholder="طول" value="" />
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-6">
                        <select name="date_typer" aria-label="انتخاب زمان محلی" data-control="select2" data-dropdown-parent="#kt_header_search" data-placeholder="Period" class="form-select form-select-sm form-select-solid">
                            <option value="days">روزها</option>
                            <option value="weeks">هفته ها</option>
                            <option value="months">ماه ها</option>
                            <option value="years">سال ها</option>
                        </select>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Input group-->
                <!--begin::Actions-->
                <div class="d-flex justify-content-end">
                    <button type="reset" class="btn btn-sm btn-light fw-bold btn-active-light-primary me-2" data-kt-search-element="advanced-options-form-cancel">انصراف</button>
                    <a href="utilities/search/horizontal.html" class="btn btn-sm fw-bold btn-primary" data-kt-search-element="advanced-options-form-search">جستجو</a>
                </div>
                <!--end::Actions-->
            </form>
            <!--end::اولویت ها-->
            <!--begin::اولویت ها-->
            <form data-kt-search-element="preferences" class="pt-1 d-none">
                <!--begin::Heading-->
                <h3 class="fw-semibold text-gray-900 mb-7">جستجو پرفرمنس</h3>
                <!--end::Heading-->
                <!--begin::Input group-->
                <div class="pb-4 border-bottom">
                    <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack">
                        <span class="form-check-label text-gray-700 fs-6 fw-semibold ms-0 me-2">پروژه ها</span>
                        <input class="form-check-input" type="checkbox" value="1" checked="checked" />
                    </label>
                </div>
                <!--end::Input group-->
                <!--begin::Input group-->
                <div class="py-4 border-bottom">
                    <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack">
                        <span class="form-check-label text-gray-700 fs-6 fw-semibold ms-0 me-2">اهداف</span>
                        <input class="form-check-input" type="checkbox" value="1" checked="checked" />
                    </label>
                </div>
                <!--end::Input group-->
                <!--begin::Input group-->
                <div class="py-4 border-bottom">
                    <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack">
                        <span class="form-check-label text-gray-700 fs-6 fw-semibold ms-0 me-2">برنامه بازاریابی</span>
                        <input class="form-check-input" type="checkbox" value="1" />
                    </label>
                </div>
                <!--end::Input group-->
                <!--begin::Input group-->
                <div class="py-4 border-bottom">
                    <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack">
                        <span class="form-check-label text-gray-700 fs-6 fw-semibold ms-0 me-2">مراجعات</span>
                        <input class="form-check-input" type="checkbox" value="1" checked="checked" />
                    </label>
                </div>
                <!--end::Input group-->
                <!--begin::Input group-->
                <div class="py-4 border-bottom">
                    <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack">
                        <span class="form-check-label text-gray-700 fs-6 fw-semibold ms-0 me-2">کاربران</span>
                        <input class="form-check-input" type="checkbox" value="1" />
                    </label>
                </div>
                <!--end::Input group-->
                <!--begin::Actions-->
                <div class="d-flex justify-content-end pt-7">
                    <button type="reset" class="btn btn-sm btn-light fw-bold btn-active-light-primary me-2" data-kt-search-element="preferences-dismiss">انصراف</button>
                    <button type="submit" class="btn btn-sm fw-bold btn-primary">ذخیره تغییرات</button>
                </div>
                <!--end::Actions-->
            </form>
            <!--end::اولویت ها-->
        </div>
        <!--end::Menu-->
    </div>
    <!--end::جستجو-->
</div>
