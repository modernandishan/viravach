<?php

use App\Models\GeneralSetting;
use App\Models\Page;
use Livewire\Attributes\Layout;

//use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts::landing')]
class extends Component {

    public Page $page;
    public GeneralSetting $gs;

    public function mount(): void
    {
        $this->page = Page::where('slug', '/')->firstOrFail();
        $this->gs = GeneralSetting::current();
    }

    public function render()
    {
        $pageTitle = $this->page->getTranslation('title', app()->getLocale());
        $tagline = $this->gs->getTranslation('site_tagline', app()->getLocale());

        // نام ویو (livewire.home) را بر اساس مسیر واقعی فایل خود تنظیم کنید
        return $this->view()->title("{$pageTitle} | {$tagline}");    }

};
?>

<div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">

    <!--begin::Post-->
    <div class="content flex-row-fluid" id="kt_content">
        <!--begin::Row-->
        <div class="row g-5 g-xl-8">
            <!--begin::Col-->
            <div class="col-xl-4">
                <!--begin::مخلوط Widget 5-->
                <div class="card card-xl-stretch mb-xl-3">
                    <!--begin::Beader-->
                    <div class="card-header border-0 py-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-3 mb-1">روندها</span>
                            <span class="text-muted fw-semibold fs-7">اخرین روندها</span>
                        </h3>
                        <div class="card-toolbar">
                            <!--begin::Menu-->
                            <button type="button" class="btn btn-sm btn-icon btn-color-primary btn-active-light-primary"
                                    data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                <i class="ki-duotone ki-category fs-6">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </button>
                            <!--begin::Menu 3-->
                            <div
                                class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-3"
                                data-kt-menu="true">
                                <!--begin::Heading-->
                                <div class="menu-item px-3">
                                    <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">درگاه ها</div>
                                </div>
                                <!--end::Heading-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">ساختن فاکتور</a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link flex-stack px-3">ساختن پرداخت
                                        <span class="ms-2" data-bs-toggle="tooltip"
                                              title="یک نام هدف را برای استفاده و مرجع در آینده مشخص کنید">
															<i class="ki-duotone ki-information fs-6">
																<span class="path1"></span>
																<span class="path2"></span>
																<span class="path3"></span>
															</i>
														</span></a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">بیل تولید کنید</a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3" data-kt-menu-trigger="hover"
                                     data-kt-menu-placement="right-end">
                                    <a href="#" class="menu-link px-3">
                                        <span class="menu-title">اشتراک</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <!--begin::Menu sub-->
                                    <div class="menu-sub menu-sub-dropdown w-175px py-4">
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">برنامه ریزی ها</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">صورتحساب</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">بیانه ها</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu separator-->
                                        <div class="separator my-2"></div>
                                        <!--end::Menu separator-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <div class="menu-content px-3">
                                                <!--begin::Switch-->
                                                <label
                                                    class="form-check form-switch form-check-custom form-check-solid">
                                                    <!--begin::Input-->
                                                    <input class="form-check-input w-30px h-20px" type="checkbox"
                                                           value="1" checked="checked" name="notifications"/>
                                                    <!--end::Input-->
                                                    <!--end::Tags-->
                                                    <span class="form-check-label text-muted fs-6">وضعیت</span>
                                                    <!--end::Tags-->
                                                </label>
                                                <!--end::Switch-->
                                            </div>
                                        </div>
                                        <!--end::Menu item-->
                                    </div>
                                    <!--end::Menu sub-->
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3 my-1">
                                    <a href="#" class="menu-link px-3">تنظیمات</a>
                                </div>
                                <!--end::Menu item-->
                            </div>
                            <!--end::Menu 3-->
                            <!--end::Menu-->
                        </div>
                    </div>
                    <!--end::Header-->
                    <!--begin::Body-->
                    <div class="card-body d-flex flex-column">
                        <!--begin::Chart-->
                        <div class="mixed-widget-5-chart card-rounded-top" data-kt-chart-color="success"
                             style="height: 150px"></div>
                        <!--end::Chart-->
                        <!--begin::items-->
                        <div class="mt-5">
                            <!--begin::item-->
                            <div class="d-flex flex-stack mb-5">
                                <!--begin::Section-->
                                <div class="d-flex align-items-center me-2">
                                    <!--begin::Symbol-->
                                    <div class="symbol symbol-50px me-3">
                                        <div class="symbol-label bg-light">
                                            <img src="theme/1/media/svg/brand-logos/plurk.svg" class="h-50" alt=""/>
                                        </div>
                                    </div>
                                    <!--end::Symbol-->
                                    <!--begin::Title-->
                                    <div>
                                        <a href="#" class="fs-6 text-gray-800 text-hover-primary fw-bold">بالا
                                            نویسندگان</a>
                                        <div class="fs-7 text-muted fw-semibold mt-1">محمد ابراهیمی, احمد مرادی</div>
                                    </div>
                                    <!--end::Title-->
                                </div>
                                <!--end::Section-->
                                <!--begin::Tags-->
                                <div class="badge badge-light fw-semibold py-4 px-3">+82$</div>
                                <!--end::Tags-->
                            </div>
                            <!--end::item-->
                            <!--begin::item-->
                            <div class="d-flex flex-stack mb-5">
                                <!--begin::Section-->
                                <div class="d-flex align-items-center me-2">
                                    <!--begin::Symbol-->
                                    <div class="symbol symbol-50px me-3">
                                        <div class="symbol-label bg-light">
                                            <img src="theme/1/media/svg/brand-logos/figma-1.svg" class="h-50" alt=""/>
                                        </div>
                                    </div>
                                    <!--end::Symbol-->
                                    <!--begin::Title-->
                                    <div>
                                        <a href="#" class="fs-6 text-gray-800 text-hover-primary fw-bold">بالاترین
                                            فروش</a>
                                        <div class="fs-7 text-muted fw-semibold mt-1">پیت شاپ</div>
                                    </div>
                                    <!--end::Title-->
                                </div>
                                <!--end::Section-->
                                <!--begin::Tags-->
                                <div class="badge badge-light fw-semibold py-4 px-3">+82$</div>
                                <!--end::Tags-->
                            </div>
                            <!--end::item-->
                            <!--begin::item-->
                            <div class="d-flex flex-stack">
                                <!--begin::Section-->
                                <div class="d-flex align-items-center me-2">
                                    <!--begin::Symbol-->
                                    <div class="symbol symbol-50px me-3">
                                        <div class="symbol-label bg-light">
                                            <img src="theme/1/media/svg/brand-logos/vimeo.svg" class="h-50" alt=""/>
                                        </div>
                                    </div>
                                    <!--end::Symbol-->
                                    <!--begin::Title-->
                                    <div class="py-1">
                                        <a href="#" class="fs-6 text-gray-800 text-hover-primary fw-bold">نامزدی
                                            برتر</a>
                                        <div class="fs-7 text-muted fw-semibold mt-1">KT.com</div>
                                    </div>
                                    <!--end::Title-->
                                </div>
                                <!--end::Section-->
                                <!--begin::Tags-->
                                <div class="badge badge-light fw-semibold py-4 px-3">+82$</div>
                                <!--end::Tags-->
                            </div>
                            <!--end::item-->
                        </div>
                        <!--end::items-->
                    </div>
                    <!--end::Body-->
                </div>
                <!--end::مخلوط Widget 5-->
            </div>
            <!--end::Col-->
            <!--begin::Col-->
            <div class="col-xl-8">
                <!--begin::Row-->
                <div class="row gx-5 gx-xl-8 mb-5 mb-xl-8">
                    <div class="col-xl-3 mb-5 mb-xl-0">
                        <!--begin::Tiles Widget 1-->
                        <div
                            class="card h-150px bgi-no-repeat bgi-size-cover bgi-position-y-center card-xl-stretch border-0"
                            style="background-image:url('theme/1/media/stock/600x600/img-22.jpg')">
                            <!--begin::Body-->
                            <div class="card-body p-6">
                                <!--begin::Title-->
                                <a href="#" class="text-black text-hover-primary fw-bold fs-2" data-bs-toggle="modal"
                                   data-bs-target="#kt_modal_create_app">کمپانی</a>
                                <!--end::Title-->
                            </div>
                            <!--end::Body-->
                        </div>
                        <!--end::Tiles Widget 1-->
                    </div>
                    <livewire:maps.iran-export/>

                </div>
                <!--end::Row-->
                <!--begin::Row-->
                <div class="row gx-5 gx-xl-8 mb-5 mb-xl-8">
                    <!--begin::Col-->
                    <div class="col-xl-6">
                        <!--begin::Tiles Widget 2-->
                        <div class="card h-175px bgi-no-repeat bgi-size-contain card-xl-stretch mb-5 mb-xl-8"
                             style="background-color: #663259; background-position: right; background-image:url('theme/1/media/svg/misc/taieri.svg')">
                            <!--begin::Body-->
                            <div class="card-body d-flex flex-column justify-content-between">
                                <!--begin::Title-->
                                <h2 class="text-white fw-bold mb-5">ساختن
                                    <br/>گزارشات پایه</h2>
                                <!--end::Title-->
                                <!--begin::Actions-->
                                <div class="m-0">
                                    <a href='#' class="btn btn-danger fw-semibold px-6 py-3" data-bs-toggle="modal"
                                       data-bs-target="#kt_modal_create_campaign">ساختن کمپین</a>
                                </div>
                                <!--begin::Actions-->
                            </div>
                            <!--end::Body-->
                        </div>
                        <!--end::Tiles Widget 2-->
                        <div class="row gx-5 gx-xl-8">
                            <!--begin::Col-->
                            <div class="col-xxl-6 mb-5 mb-xl-8">
                                <!--begin::Tiles Widget 5-->
                                <a href="#" class="card card-xxl-stretch bg-primary">
                                    <!--begin::Body-->
                                    <div class="card-body d-flex flex-column justify-content-between">
                                        <i class="ki-duotone ki-element-11 text-white fs-2hx ms-n1 flex-grow-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                        </i>
                                        <div class="d-flex flex-column">
                                            <div class="text-white fw-bold fs-1 mb-0 mt-5">790</div>
                                            <div class="text-white fw-semibold fs-6">محصولات جدید</div>
                                        </div>
                                    </div>
                                    <!--end::Body-->
                                </a>
                                <!--end::Tiles Widget 5-->
                            </div>
                            <!--end::Col-->
                            <!--begin::Col-->
                            <div class="col-xxl-6 mb-5 mb-xl-0">
                                <!--begin::Tiles Widget 5-->
                                <a href="#" class="card card-xxl-stretch bg-body">
                                    <!--begin::Body-->
                                    <div class="card-body d-flex flex-column justify-content-between">
                                        <i class="ki-duotone ki-rocket text-success fs-2hx ms-n1 flex-grow-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <div class="d-flex flex-column">
                                            <div class="text-gray-900 fw-bold fs-1 mb-0 mt-5">8,600</div>
                                            <div class="text-muted fw-semibold fs-6">جدید مشتریان</div>
                                        </div>
                                    </div>
                                    <!--end::Body-->
                                </a>
                                <!--end::Tiles Widget 5-->
                            </div>
                            <!--end::Col-->
                        </div>
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-xl-6">
                        <!--begin::مخلوط Widget 3-->
                        <div class="card h-100 bgi-no-repeat bgi-size-cover card-xl-stretch mb-5 mb-xl-8"
                             style="background-image:url('theme/1/media/misc/bg-2.jpg')">
                            <!--begin::Body-->
                            <div class="card-body d-flex flex-column justify-content-between">
                                <!--begin::Title-->
                                <div class="text-white fw-bold fs-2">
                                    <h2 class="fw-bold text-white mb-2">ساختن گزارشات</h2>با اپلیکیشن
                                </div>
                                <!--end::Title-->
                                <!--begin::Link-->
                                <a href='#' class="text-warning fw-semibold" data-bs-toggle="modal"
                                   data-bs-target="#kt_modal_create_campaign">ساختن گزارش
                                    <i class="ki-duotone ki-arrow-left fs-2 text-warning">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i></a>
                                <!--end::Link-->
                            </div>
                            <!--end::Body-->
                        </div>
                        <!--end::مخلوط Widget 3-->
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
            </div>
            <!--end::Col-->
        </div>
        <!--end::Row-->
        <!--begin::Row-->
        <div class="row gy-5 g-xl-8">
            <!--begin::Col-->
            <div class="col-xl-4">
                <!--begin::مخلوط Widget 2-->
                <div class="card card-xl-stretch">
                    <!--begin::Header-->
                    <div class="card-header border-0 bg-danger py-5">
                        <h3 class="card-title fw-bold text-white">فروش امار</h3>
                        <div class="card-toolbar">
                            <!--begin::Menu-->
                            <button type="button"
                                    class="btn btn-sm btn-icon btn-color-white btn-active-white btn-active-color- border-0 me-n3"
                                    data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                <i class="ki-duotone ki-category fs-6">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </button>
                            <!--begin::Menu 3-->
                            <div
                                class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-3"
                                data-kt-menu="true">
                                <!--begin::Heading-->
                                <div class="menu-item px-3">
                                    <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">درگاه ها</div>
                                </div>
                                <!--end::Heading-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">ساختن فاکتور</a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link flex-stack px-3">ساختن پرداخت
                                        <span class="ms-2" data-bs-toggle="tooltip"
                                              title="یک نام هدف را برای استفاده و مرجع در آینده مشخص کنید">
															<i class="ki-duotone ki-information fs-6">
																<span class="path1"></span>
																<span class="path2"></span>
																<span class="path3"></span>
															</i>
														</span></a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">بیل تولید کنید</a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3" data-kt-menu-trigger="hover"
                                     data-kt-menu-placement="right-end">
                                    <a href="#" class="menu-link px-3">
                                        <span class="menu-title">اشتراک</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <!--begin::Menu sub-->
                                    <div class="menu-sub menu-sub-dropdown w-175px py-4">
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">برنامه ریزی ها</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">صورتحساب</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">بیانه ها</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu separator-->
                                        <div class="separator my-2"></div>
                                        <!--end::Menu separator-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <div class="menu-content px-3">
                                                <!--begin::Switch-->
                                                <label
                                                    class="form-check form-switch form-check-custom form-check-solid">
                                                    <!--begin::Input-->
                                                    <input class="form-check-input w-30px h-20px" type="checkbox"
                                                           value="1" checked="checked" name="notifications"/>
                                                    <!--end::Input-->
                                                    <!--end::Tags-->
                                                    <span class="form-check-label text-muted fs-6">وضعیت</span>
                                                    <!--end::Tags-->
                                                </label>
                                                <!--end::Switch-->
                                            </div>
                                        </div>
                                        <!--end::Menu item-->
                                    </div>
                                    <!--end::Menu sub-->
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3 my-1">
                                    <a href="#" class="menu-link px-3">تنظیمات</a>
                                </div>
                                <!--end::Menu item-->
                            </div>
                            <!--end::Menu 3-->
                            <!--end::Menu-->
                        </div>
                    </div>
                    <!--end::Header-->
                    <!--begin::Body-->
                    <div class="card-body p-0">
                        <!--begin::Chart-->
                        <div class="mixed-widget-2-chart card-rounded-bottom bg-danger" data-kt-color="danger"
                             style="height: 200px"></div>
                        <!--end::Chart-->
                        <!--begin::Stats-->
                        <div class="card-p mt-n20 position-relative">
                            <!--begin::Row-->
                            <div class="row g-0">
                                <!--begin::Col-->
                                <div class="col d-flex flex-column bg-light-warning px-6 py-8 rounded-2 me-7 mb-7">
                                    <i class="ki-duotone ki-chart-simple fs-2x text-warning my-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                    </i>
                                    <a href="#" class="text-warning fw-semibold fs-6">فروش هفتگی</a>
                                </div>
                                <!--end::Col-->
                                <!--begin::Col-->
                                <div class="col d-flex flex-column bg-light-primary px-6 py-8 rounded-2 mb-7">
                                    <i class="ki-duotone ki-briefcase fs-2x text-primary my-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <a href="#" class="text-primary fw-semibold fs-6">جدید پروژه ها</a>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Row-->
                            <!--begin::Row-->
                            <div class="row g-0">
                                <!--begin::Col-->
                                <div class="col d-flex flex-column bg-light-danger px-6 py-8 rounded-2 me-7">
                                    <i class="ki-duotone ki-abstract-26 fs-2x text-danger my-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <a href="#" class="text-danger fw-semibold fs-6 mt-2">سفارشات مورد</a>
                                </div>
                                <!--end::Col-->
                                <!--begin::Col-->
                                <div class="col d-flex flex-column bg-light-success px-6 py-8 rounded-2">
                                    <i class="ki-duotone ki-sms fs-2x text-success my-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <a href="#" class="text-success fw-semibold fs-6 mt-2">گزارش اشکالات</a>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Row-->
                        </div>
                        <!--end::Stats-->
                    </div>
                    <!--end::Body-->
                </div>
                <!--end::مخلوط Widget 2-->
            </div>
            <!--end::Col-->
            <!--begin::Col-->
            <div class="col-xl-4">
                <!--begin::لیست Widget 5-->
                <div class="card card-xl-stretch">
                    <!--begin::Header-->
                    <div class="card-header align-items-center border-0 mt-4">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="fw-bold mb-2 text-gray-900">فعالیتها</span>
                            <span class="text-muted fw-semibold fs-7">890 فروش</span>
                        </h3>
                        <div class="card-toolbar">
                            <!--begin::Menu-->
                            <button type="button" class="btn btn-sm btn-icon btn-color-primary btn-active-light-primary"
                                    data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                <i class="ki-duotone ki-category fs-6">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </button>
                            <!--begin::Menu 1-->
                            <div class="menu menu-sub menu-sub-dropdown w-250px w-md-300px" data-kt-menu="true"
                                 id="kt_menu_6606389eb78d2">
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
                                            <select class="form-select form-select-solid" multiple="multiple"
                                                    data-kt-select2="true" data-close-on-select="false"
                                                    data-placeholder="انتخاب گزینه"
                                                    data-dropdown-parent="#kt_menu_6606389eb78d2"
                                                    data-allow-clear="true">
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
                                            <label
                                                class="form-check form-check-sm form-check-custom form-check-solid me-5">
                                                <input class="form-check-input" type="checkbox" value="1"/>
                                                <span class="form-check-label">نویسنده</span>
                                            </label>
                                            <!--end::تنظیمات-->
                                            <!--begin::تنظیمات-->
                                            <label class="form-check form-check-sm form-check-custom form-check-solid">
                                                <input class="form-check-input" type="checkbox" value="2"
                                                       checked="checked"/>
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
                                        <div
                                            class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" value=""
                                                   name="notifications" checked="checked"/>
                                            <label class="form-check-label">فعال</label>
                                        </div>
                                        <!--end::Switch-->
                                    </div>
                                    <!--end::Input group-->
                                    <!--begin::Actions-->
                                    <div class="d-flex justify-content-end">
                                        <button type="reset" class="btn btn-sm btn-light btn-active-light-primary me-2"
                                                data-kt-menu-dismiss="true">ریست
                                        </button>
                                        <button type="submit" class="btn btn-sm btn-primary"
                                                data-kt-menu-dismiss="true">تایید
                                        </button>
                                    </div>
                                    <!--end::Actions-->
                                </div>
                                <!--end::form-->
                            </div>
                            <!--end::Menu 1-->
                            <!--end::Menu-->
                        </div>
                    </div>
                    <!--end::Header-->
                    <!--begin::Body-->
                    <div class="card-body pt-5">
                        <!--begin::تایم لاین-->
                        <div class="تایم لاین-label">
                            <!--begin::item-->
                            <div class="تایم لاین-item">
                                <!--begin::Tags-->
                                <div class="تایم لاین-label fw-bold text-gray-800 fs-6">08:42</div>
                                <!--end::Tags-->
                                <!--begin::Badge-->
                                <div class="تایم لاین-badge">
                                    <i class="fa fa-genderless text-warning fs-1"></i>
                                </div>
                                <!--end::Badge-->
                                <!--begin::Text-->
                                <div class="fw-mیاmal تایم لاین-content text-muted ps-3">رئوس مطالب شما را صادق نگه می
                                    دارد. و ساختار را حفظ کنید
                                </div>
                                <!--end::Text-->
                            </div>
                            <!--end::item-->
                            <!--begin::item-->
                            <div class="تایم لاین-item">
                                <!--begin::Tags-->
                                <div class="تایم لاین-label fw-bold text-gray-800 fs-6">10:00</div>
                                <!--end::Tags-->
                                <!--begin::Badge-->
                                <div class="تایم لاین-badge">
                                    <i class="fa fa-genderless text-success fs-1"></i>
                                </div>
                                <!--end::Badge-->
                                <!--begin::Content-->
                                <div class="تایم لاین-content d-flex">
                                    <span class="fw-bold text-gray-800 ps-3">جلسه ایول</span>
                                </div>
                                <!--end::Content-->
                            </div>
                            <!--end::item-->
                            <!--begin::item-->
                            <div class="تایم لاین-item">
                                <!--begin::Tags-->
                                <div class="تایم لاین-label fw-bold text-gray-800 fs-6">14:37</div>
                                <!--end::Tags-->
                                <!--begin::Badge-->
                                <div class="تایم لاین-badge">
                                    <i class="fa fa-genderless text-danger fs-1"></i>
                                </div>
                                <!--end::Badge-->
                                <!--begin::Desc-->
                                <div class="تایم لاین-content fw-bold text-gray-800 ps-3">سپرده گذاری کنید
                                    <a href="#" class="text-primary">700دلار</a>به ایسل
                                </div>
                                <!--end::Desc-->
                            </div>
                            <!--end::item-->
                            <!--begin::item-->
                            <div class="تایم لاین-item">
                                <!--begin::Tags-->
                                <div class="تایم لاین-label fw-bold text-gray-800 fs-6">16:50</div>
                                <!--end::Tags-->
                                <!--begin::Badge-->
                                <div class="تایم لاین-badge">
                                    <i class="fa fa-genderless text-primary fs-1"></i>
                                </div>
                                <!--end::Badge-->
                                <!--begin::Text-->
                                <div class="تایم لاین-content fw-mیاmal text-muted ps-3">لذت بردن از رانندگی ضعیف و حفظ
                                    ساختار عالی است
                                </div>
                                <!--end::Text-->
                            </div>
                            <!--end::item-->
                            <!--begin::item-->
                            <div class="تایم لاین-item">
                                <!--begin::Tags-->
                                <div class="تایم لاین-label fw-bold text-gray-800 fs-6">21:03</div>
                                <!--end::Tags-->
                                <!--begin::Badge-->
                                <div class="تایم لاین-badge">
                                    <i class="fa fa-genderless text-danger fs-1"></i>
                                </div>
                                <!--end::Badge-->
                                <!--begin::Desc-->
                                <div class="تایم لاین-content fw-semibold text-gray-800 ps-3">سفارش جدید قرار داده شده
                                    است
                                    <a href="#" class="text-primary">#XF-2356</a>.
                                </div>
                                <!--end::Desc-->
                            </div>
                            <!--end::item-->
                            <!--begin::item-->
                            <div class="تایم لاین-item">
                                <!--begin::Tags-->
                                <div class="تایم لاین-label fw-bold text-gray-800 fs-6">16:50</div>
                                <!--end::Tags-->
                                <!--begin::Badge-->
                                <div class="تایم لاین-badge">
                                    <i class="fa fa-genderless text-primary fs-1"></i>
                                </div>
                                <!--end::Badge-->
                                <!--begin::Text-->
                                <div class="تایم لاین-content fw-mیاmal text-muted ps-3">لذت بردن از رانندگی ضعیف و حفظ
                                    ساختار عالی است
                                </div>
                                <!--end::Text-->
                            </div>
                            <!--end::item-->
                            <!--begin::item-->
                            <div class="تایم لاین-item">
                                <!--begin::Tags-->
                                <div class="تایم لاین-label fw-bold text-gray-800 fs-6">21:03</div>
                                <!--end::Tags-->
                                <!--begin::Badge-->
                                <div class="تایم لاین-badge">
                                    <i class="fa fa-genderless text-danger fs-1"></i>
                                </div>
                                <!--end::Badge-->
                                <!--begin::Desc-->
                                <div class="تایم لاین-content fw-semibold text-gray-800 ps-3">سفارش جدید قرار داده شده
                                    است
                                    <a href="#" class="text-primary">#XF-2356</a>.
                                </div>
                                <!--end::Desc-->
                            </div>
                            <!--end::item-->
                            <!--begin::item-->
                            <div class="تایم لاین-item">
                                <!--begin::Tags-->
                                <div class="تایم لاین-label fw-bold text-gray-800 fs-6">10:30</div>
                                <!--end::Tags-->
                                <!--begin::Badge-->
                                <div class="تایم لاین-badge">
                                    <i class="fa fa-genderless text-success fs-1"></i>
                                </div>
                                <!--end::Badge-->
                                <!--begin::Text-->
                                <div class="تایم لاین-content fw-mیاmal text-muted ps-3">جلسه مقدماتی برنامه موبایل
                                </div>
                                <!--end::Text-->
                            </div>
                            <!--end::item-->
                        </div>
                        <!--end::تایم لاین-->
                    </div>
                    <!--end: کارت Body-->
                </div>
                <!--end: لیست Widget 5-->
            </div>
            <!--end::Col-->
            <!--begin::Col-->
            <div class="col-xl-4">
                <!--begin::مخلوط Widget 7-->
                <div class="card card-xl-stretch-50 mb-5 mb-xl-8">
                    <!--begin::Body-->
                    <div class="card-body d-flex flex-column p-0">
                        <!--begin::Stats-->
                        <div class="flex-grow-1 card-p pb-0">
                            <div class="d-flex flex-stack flex-wrap">
                                <div class="me-2">
                                    <a href="#" class="text-gray-900 text-hover-primary fw-bold fs-3">گزارش ایجاد
                                        کنید</a>
                                    <div class="text-muted fs-7 fw-bold">گزارش های مالی و حسابداری</div>
                                </div>
                                <div class="fw-bold fs-3 text-primary">$24,500</div>
                            </div>
                        </div>
                        <!--end::Stats-->
                        <!--begin::Chart-->
                        <div class="mixed-widget-7-chart card-rounded-bottom" data-kt-chart-color="primary"
                             style="height: 150px"></div>
                        <!--end::Chart-->
                    </div>
                    <!--end::Body-->
                </div>
                <!--end::مخلوط Widget 7-->
                <!--begin::مخلوط Widget 10-->
                <div class="card card-xl-stretch-50 mb-5 mb-xl-8">
                    <!--begin::Body-->
                    <div class="card-body p-0 d-flex justify-content-between flex-column overflow-hidden">
                        <!--begin::Hidden-->
                        <div class="d-flex flex-stack flex-wrap flex-grow-1 px-9 pt-9 pb-3">
                            <div class="me-2">
                                <span class="fw-bold text-gray-800 d-block fs-3">فروش</span>
                                <span class="text-gray-500 fw-bold">Oct 8 - Oct 26 24</span>
                            </div>
                            <div class="fw-bold fs-3 text-primary">$15,300</div>
                        </div>
                        <!--end::Hidden-->
                        <!--begin::Chart-->
                        <div class="mixed-widget-10-chart" data-kt-color="primary" style="height: 175px"></div>
                        <!--end::Chart-->
                    </div>
                </div>
                <!--end::مخلوط Widget 10-->
            </div>
            <!--end::Col-->
        </div>
        <!--end::Row-->
        <!--begin::Row-->
        <div class="row gy-5 g-xl-8">
            <!--begin::Col-->
            <div class="col-xl-4">
                <!--begin::لیست Widget 3-->
                <div class="card card-xl-stretch mb-xl-3">
                    <!--begin::Header-->
                    <div class="card-header border-0">
                        <h3 class="card-title fw-bold text-gray-900">انجام دادن</h3>
                        <div class="card-toolbar">
                            <!--begin::Menu-->
                            <button type="button" class="btn btn-sm btn-icon btn-color-primary btn-active-light-primary"
                                    data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                <i class="ki-duotone ki-category fs-6">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </button>
                            <!--begin::Menu 3-->
                            <div
                                class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-3"
                                data-kt-menu="true">
                                <!--begin::Heading-->
                                <div class="menu-item px-3">
                                    <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">درگاه ها</div>
                                </div>
                                <!--end::Heading-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">ساختن فاکتور</a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link flex-stack px-3">ساختن پرداخت
                                        <span class="ms-2" data-bs-toggle="tooltip"
                                              title="یک نام هدف را برای استفاده و مرجع در آینده مشخص کنید">
															<i class="ki-duotone ki-information fs-6">
																<span class="path1"></span>
																<span class="path2"></span>
																<span class="path3"></span>
															</i>
														</span></a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">بیل تولید کنید</a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3" data-kt-menu-trigger="hover"
                                     data-kt-menu-placement="right-end">
                                    <a href="#" class="menu-link px-3">
                                        <span class="menu-title">اشتراک</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <!--begin::Menu sub-->
                                    <div class="menu-sub menu-sub-dropdown w-175px py-4">
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">برنامه ریزی ها</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">صورتحساب</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">بیانه ها</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu separator-->
                                        <div class="separator my-2"></div>
                                        <!--end::Menu separator-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <div class="menu-content px-3">
                                                <!--begin::Switch-->
                                                <label
                                                    class="form-check form-switch form-check-custom form-check-solid">
                                                    <!--begin::Input-->
                                                    <input class="form-check-input w-30px h-20px" type="checkbox"
                                                           value="1" checked="checked" name="notifications"/>
                                                    <!--end::Input-->
                                                    <!--end::Tags-->
                                                    <span class="form-check-label text-muted fs-6">وضعیت</span>
                                                    <!--end::Tags-->
                                                </label>
                                                <!--end::Switch-->
                                            </div>
                                        </div>
                                        <!--end::Menu item-->
                                    </div>
                                    <!--end::Menu sub-->
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3 my-1">
                                    <a href="#" class="menu-link px-3">تنظیمات</a>
                                </div>
                                <!--end::Menu item-->
                            </div>
                            <!--end::Menu 3-->
                            <!--end::Menu-->
                        </div>
                    </div>
                    <!--end::Header-->
                    <!--begin::Body-->
                    <div class="card-body pt-2">
                        <!--begin::item-->
                        <div class="d-flex align-items-center mb-8">
                            <!--begin::Bullet-->
                            <span class="bullet bullet-vertical h-40px bg-success"></span>
                            <!--end::Bullet-->
                            <!--begin::Checkbox-->
                            <div class="form-check form-check-custom form-check-solid mx-5">
                                <input class="form-check-input" type="checkbox" value=""/>
                            </div>
                            <!--end::Checkbox-->
                            <!--begin::توضیحات-->
                            <div class="flex-grow-1">
                                <a href="#" class="text-gray-800 text-hover-primary fw-bold fs-6">ساختن لوگو فروشگاه</a>
                                <span class="text-muted fw-semibold d-block">سررسید در 2 روز</span>
                            </div>
                            <!--end::توضیحات-->
                            <span class="badge badge-light-success fs-8 fw-bold">جدید</span>
                        </div>
                        <!--end:آیتم-->
                        <!--begin::item-->
                        <div class="d-flex align-items-center mb-8">
                            <!--begin::Bullet-->
                            <span class="bullet bullet-vertical h-40px bg-primary"></span>
                            <!--end::Bullet-->
                            <!--begin::Checkbox-->
                            <div class="form-check form-check-custom form-check-solid mx-5">
                                <input class="form-check-input" type="checkbox" value=""/>
                            </div>
                            <!--end::Checkbox-->
                            <!--begin::توضیحات-->
                            <div class="flex-grow-1">
                                <a href="#" class="text-gray-800 text-hover-primary fw-bold fs-6">جلسه ذینفعان</a>
                                <span class="text-muted fw-semibold d-block">سررسید سه روز</span>
                            </div>
                            <!--end::توضیحات-->
                            <span class="badge badge-light-primary fs-8 fw-bold">جدید</span>
                        </div>
                        <!--end:آیتم-->
                        <!--begin::item-->
                        <div class="d-flex align-items-center mb-8">
                            <!--begin::Bullet-->
                            <span class="bullet bullet-vertical h-40px bg-warning"></span>
                            <!--end::Bullet-->
                            <!--begin::Checkbox-->
                            <div class="form-check form-check-custom form-check-solid mx-5">
                                <input class="form-check-input" type="checkbox" value=""/>
                            </div>
                            <!--end::Checkbox-->
                            <!--begin::توضیحات-->
                            <div class="flex-grow-1">
                                <a href="#" class="text-gray-800 text-hover-primary fw-bold fs-6">ارزیابی و ارزیابی</a>
                                <span class="text-muted fw-semibold d-block">سررسید در 5 روز</span>
                            </div>
                            <!--end::توضیحات-->
                            <span class="badge badge-light-warning fs-8 fw-bold">جدید</span>
                        </div>
                        <!--end:آیتم-->
                        <!--begin::item-->
                        <div class="d-flex align-items-center mb-8">
                            <!--begin::Bullet-->
                            <span class="bullet bullet-vertical h-40px bg-primary"></span>
                            <!--end::Bullet-->
                            <!--begin::Checkbox-->
                            <div class="form-check form-check-custom form-check-solid mx-5">
                                <input class="form-check-input" type="checkbox" value=""/>
                            </div>
                            <!--end::Checkbox-->
                            <!--begin::توضیحات-->
                            <div class="flex-grow-1">
                                <a href="#" class="text-gray-800 text-hover-primary fw-bold fs-6">ویترین برنامه</a>
                                <span class="text-muted fw-semibold d-block">سررسید در 2 روز</span>
                            </div>
                            <!--end::توضیحات-->
                            <span class="badge badge-light-primary fs-8 fw-bold">جدید</span>
                        </div>
                        <!--end:آیتم-->
                        <!--begin::item-->
                        <div class="d-flex align-items-center mb-8">
                            <!--begin::Bullet-->
                            <span class="bullet bullet-vertical h-40px bg-danger"></span>
                            <!--end::Bullet-->
                            <!--begin::Checkbox-->
                            <div class="form-check form-check-custom form-check-solid mx-5">
                                <input class="form-check-input" type="checkbox" value=""/>
                            </div>
                            <!--end::Checkbox-->
                            <!--begin::توضیحات-->
                            <div class="flex-grow-1">
                                <a href="#" class="text-gray-800 text-hover-primary fw-bold fs-6">ملاقات پروژه ها</a>
                                <span class="text-muted fw-semibold d-block">سررسید در دوازده روز</span>
                            </div>
                            <!--end::توضیحات-->
                            <span class="badge badge-light-danger fs-8 fw-bold">جدید</span>
                        </div>
                        <!--end:آیتم-->
                        <!--begin::item-->
                        <div class="d-flex align-items-center">
                            <!--begin::Bullet-->
                            <span class="bullet bullet-vertical h-40px bg-success"></span>
                            <!--end::Bullet-->
                            <!--begin::Checkbox-->
                            <div class="form-check form-check-custom form-check-solid mx-5">
                                <input class="form-check-input" type="checkbox" value=""/>
                            </div>
                            <!--end::Checkbox-->
                            <!--begin::توضیحات-->
                            <div class="flex-grow-1">
                                <a href="#" class="text-gray-800 text-hover-primary fw-bold fs-6">مشتریان بروزرسانی</a>
                                <span class="text-muted fw-semibold d-block">سررسید در یک هفته</span>
                            </div>
                            <!--end::توضیحات-->
                            <span class="badge badge-light-success fs-8 fw-bold">جدید</span>
                        </div>
                        <!--end:آیتم-->
                    </div>
                    <!--end::Body-->
                </div>
                <!--end:لیست Widget 3-->
            </div>
            <!--end::Col-->
            <!--begin::Col-->
            <div class="col-xl-8">
                <!--begin::جداول Widget 9-->
                <div class="card card-xl-stretch mb-5 mb-xl-8">
                    <!--begin::Header-->
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-3 mb-1">اعضا امار</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">بالای 500 عضو</span>
                        </h3>
                        <div class="card-toolbar" data-bs-toggle="tooltip" data-bs-placement="top"
                             data-bs-trigger="hover" title="Click to add a user">
                            <a href="#" class="btn btn-sm btn-light btn-active-primary" data-bs-toggle="modal"
                               data-bs-target="#kt_modal_invite_friends">
                                <i class="ki-duotone ki-plus fs-2"></i>عضو جدید</a>
                        </div>
                    </div>
                    <!--end::Header-->
                    <!--begin::Body-->
                    <div class="card-body py-3">
                        <!--begin::Table container-->
                        <div class="table-responsive">
                            <!--begin::Table-->
                            <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                                <!--begin::Table head-->
                                <thead>
                                <tr class="fw-bold text-muted">
                                    <th class="w-25px">
                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                   data-kt-check="true" data-kt-check-target=".widget-9-check"/>
                                        </div>
                                    </th>
                                    <th class="min-w-200px">نویسندگان</th>
                                    <th class="min-w-150px">کمپانی</th>
                                    <th class="min-w-150px">پردازش</th>
                                    <th class="min-w-100px text-end">عملیات</th>
                                </tr>
                                </thead>
                                <!--end::Table head-->
                                <!--begin::Table body-->
                                <tbody>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                            <input class="form-check-input widget-9-check" type="checkbox" value="1"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-45px me-5">
                                                <img src="theme/1/media/avatars/300-14.jpg" alt=""/>
                                            </div>
                                            <div class="d-flex justify-content-start flex-column">
                                                <a href="#" class="text-gray-900 fw-bold text-hover-primary fs-6">محمد
                                                    رضایی</a>
                                                <span class="text-muted fw-semibold text-muted d-block fs-7">HTML, JS, ReactJS</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="#"
                                           class="text-gray-900 fw-bold text-hover-primary d-block fs-6">ساتراس</a>
                                        <span class="text-muted fw-semibold text-muted d-block fs-7">Web, طراحی</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-column w-100 me-2">
                                            <div class="d-flex flex-stack mb-2">
                                                <span class="text-muted me-2 fs-7 fw-bold">50%</span>
                                            </div>
                                            <div class="progress h-6px w-100">
                                                <div class="progress-bar bg-primary" role="progressbar"
                                                     style="width: 50%" aria-valuenow="50" aria-valuemin="0"
                                                     aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-end flex-shrink-0">
                                            <a href="#"
                                               class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
                                                <i class="ki-duotone ki-switch fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </a>
                                            <a href="#"
                                               class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
                                                <i class="ki-duotone ki-pencil fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </a>
                                            <a href="#"
                                               class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                                                <i class="ki-duotone ki-trash fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                    <span class="path3"></span>
                                                    <span class="path4"></span>
                                                    <span class="path5"></span>
                                                </i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                            <input class="form-check-input widget-9-check" type="checkbox" value="1"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-45px me-5">
                                                <img src="theme/1/media/avatars/300-2.jpg" alt=""/>
                                            </div>
                                            <div class="d-flex justify-content-start flex-column">
                                                <a href="#" class="text-gray-900 fw-bold text-hover-primary fs-6">علی
                                                    رضایی</a>
                                                <span class="text-muted fw-semibold text-muted d-block fs-7">C#, ASP.NET, MS SQL</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="#"
                                           class="text-gray-900 fw-bold text-hover-primary d-block fs-6">غلامی</a>
                                        <span
                                            class="text-muted fw-semibold text-muted d-block fs-7">Houses & Hotels</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-column w-100 me-2">
                                            <div class="d-flex flex-stack mb-2">
                                                <span class="text-muted me-2 fs-7 fw-bold">70%</span>
                                            </div>
                                            <div class="progress h-6px w-100">
                                                <div class="progress-bar bg-danger" role="progressbar"
                                                     style="width: 70%" aria-valuenow="70" aria-valuemin="0"
                                                     aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-end flex-shrink-0">
                                            <a href="#"
                                               class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
                                                <i class="ki-duotone ki-switch fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </a>
                                            <a href="#"
                                               class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
                                                <i class="ki-duotone ki-pencil fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </a>
                                            <a href="#"
                                               class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                                                <i class="ki-duotone ki-trash fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                    <span class="path3"></span>
                                                    <span class="path4"></span>
                                                    <span class="path5"></span>
                                                </i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                            <input class="form-check-input widget-9-check" type="checkbox" value="1"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-45px me-5">
                                                <img src="theme/1/media/avatars/300-5.jpg" alt=""/>
                                            </div>
                                            <div class="d-flex justify-content-start flex-column">
                                                <a href="#" class="text-gray-900 fw-bold text-hover-primary fs-6">محمد
                                                    ابراهیمی</a>
                                                <span class="text-muted fw-semibold text-muted d-block fs-7">PHP, Laravel, VueJS</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="#"
                                           class="text-gray-900 fw-bold text-hover-primary d-block fs-6">جوادی</a>
                                        <span class="text-muted fw-semibold text-muted d-block fs-7">جابجایی</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-column w-100 me-2">
                                            <div class="d-flex flex-stack mb-2">
                                                <span class="text-muted me-2 fs-7 fw-bold">60%</span>
                                            </div>
                                            <div class="progress h-6px w-100">
                                                <div class="progress-bar bg-success" role="progressbar"
                                                     style="width: 60%" aria-valuenow="60" aria-valuemin="0"
                                                     aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-end flex-shrink-0">
                                            <a href="#"
                                               class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
                                                <i class="ki-duotone ki-switch fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </a>
                                            <a href="#"
                                               class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
                                                <i class="ki-duotone ki-pencil fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </a>
                                            <a href="#"
                                               class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                                                <i class="ki-duotone ki-trash fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                    <span class="path3"></span>
                                                    <span class="path4"></span>
                                                    <span class="path5"></span>
                                                </i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                            <input class="form-check-input widget-9-check" type="checkbox" value="1"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-45px me-5">
                                                <img src="theme/1/media/avatars/300-20.jpg" alt=""/>
                                            </div>
                                            <div class="d-flex justify-content-start flex-column">
                                                <a href="#" class="text-gray-900 fw-bold text-hover-primary fs-6">محسن
                                                    برومند</a>
                                                <span class="text-muted fw-semibold text-muted d-block fs-7">Python, PostgreSQL, ReactJS</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="#" class="text-gray-900 fw-bold text-hover-primary d-block fs-6">کاویانی</a>
                                        <span class="text-muted fw-semibold text-muted d-block fs-7">بیمه</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-column w-100 me-2">
                                            <div class="d-flex flex-stack mb-2">
                                                <span class="text-muted me-2 fs-7 fw-bold">50%</span>
                                            </div>
                                            <div class="progress h-6px w-100">
                                                <div class="progress-bar bg-warning" role="progressbar"
                                                     style="width: 50%" aria-valuenow="50" aria-valuemin="0"
                                                     aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-end flex-shrink-0">
                                            <a href="#"
                                               class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
                                                <i class="ki-duotone ki-switch fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </a>
                                            <a href="#"
                                               class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
                                                <i class="ki-duotone ki-pencil fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </a>
                                            <a href="#"
                                               class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                                                <i class="ki-duotone ki-trash fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                    <span class="path3"></span>
                                                    <span class="path4"></span>
                                                    <span class="path5"></span>
                                                </i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                            <input class="form-check-input widget-9-check" type="checkbox" value="1"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-45px me-5">
                                                <img src="theme/1/media/avatars/300-23.jpg" alt=""/>
                                            </div>
                                            <div class="d-flex justify-content-start flex-column">
                                                <a href="#" class="text-gray-900 fw-bold text-hover-primary fs-6">اتنا
                                                    رضایی</a>
                                                <span class="text-muted fw-semibold text-muted d-block fs-7">HTML, JS, ReactJS</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="#"
                                           class="text-gray-900 fw-bold text-hover-primary d-block fs-6">جوادی</a>
                                        <span
                                            class="text-muted fw-semibold text-muted d-block fs-7">کارگردان هنری</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-column w-100 me-2">
                                            <div class="d-flex flex-stack mb-2">
                                                <span class="text-muted me-2 fs-7 fw-bold">90%</span>
                                            </div>
                                            <div class="progress h-6px w-100">
                                                <div class="progress-bar bg-info" role="progressbar" style="width: 90%"
                                                     aria-valuenow="90" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-end flex-shrink-0">
                                            <a href="#"
                                               class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
                                                <i class="ki-duotone ki-switch fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </a>
                                            <a href="#"
                                               class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
                                                <i class="ki-duotone ki-pencil fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </a>
                                            <a href="#"
                                               class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                                                <i class="ki-duotone ki-trash fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                    <span class="path3"></span>
                                                    <span class="path4"></span>
                                                    <span class="path5"></span>
                                                </i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                                <!--end::Table body-->
                            </table>
                            <!--end::Table-->
                        </div>
                        <!--end::Table container-->
                    </div>
                    <!--begin::Body-->
                </div>
                <!--end::جداول Widget 9-->
            </div>
            <!--end::Col-->
        </div>
        <!--end::Row-->
        <!--begin::Row-->
        <div class="row gy-5 g-xl-8">
            <!--begin::Col-->
            <div class="col-xl-4">
                <!--begin::لیست Widget 2-->
                <div class="card card-xl-stretch mb-xl-8">
                    <!--begin::Header-->
                    <div class="card-header border-0">
                        <h3 class="card-title fw-bold text-gray-900">نویسندگان</h3>
                        <div class="card-toolbar">
                            <!--begin::Menu-->
                            <button type="button" class="btn btn-sm btn-icon btn-color-primary btn-active-light-primary"
                                    data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                <i class="ki-duotone ki-category fs-6">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </button>
                            <!--begin::Menu 2-->
                            <div
                                class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px"
                                data-kt-menu="true">
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <div class="menu-content fs-6 text-gray-900 fw-bold px-3 py-4">عملیات سریع</div>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu separator-->
                                <div class="separator mb-3 opacity-75"></div>
                                <!--end::Menu separator-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">تیکت جدید</a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">جدید مشتری</a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3" data-kt-menu-trigger="hover"
                                     data-kt-menu-placement="left-start">
                                    <!--begin::Menu item-->
                                    <a href="#" class="menu-link px-3">
                                        <span class="menu-title">گروه جدید</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <!--end::Menu item-->
                                    <!--begin::Menu sub-->
                                    <div class="menu-sub menu-sub-dropdown w-175px py-4">
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">گروه مدیر</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">گروه کارکنان</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">گروه عضوها</a>
                                        </div>
                                        <!--end::Menu item-->
                                    </div>
                                    <!--end::Menu sub-->
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">مخاطبین جدید</a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu separator-->
                                <div class="separator mt-3 opacity-75"></div>
                                <!--end::Menu separator-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <div class="menu-content px-3 py-3">
                                        <a class="btn btn-primary btn-sm px-4" href="#">گزارش ایجاد کنید</a>
                                    </div>
                                </div>
                                <!--end::Menu item-->
                            </div>
                            <!--end::Menu 2-->
                            <!--end::Menu-->
                        </div>
                    </div>
                    <!--end::Header-->
                    <!--begin::Body-->
                    <div class="card-body pt-2">
                        <!--begin::item-->
                        <div class="d-flex align-items-center mb-7">
                            <!--begin::Avatar-->
                            <div class="symbol symbol-50px me-5">
                                <img src="theme/1/media/avatars/300-6.jpg" class="" alt=""/>
                            </div>
                            <!--end::Avatar-->
                            <!--begin::Text-->
                            <div class="flex-grow-1">
                                <a href="#" class="text-gray-900 fw-bold text-hover-primary fs-6">مرادی نیا</a>
                                <span class="text-muted d-block fw-bold">مدیر پروژه</span>
                            </div>
                            <!--end::Text-->
                        </div>
                        <!--end::item-->
                        <!--begin::item-->
                        <div class="d-flex align-items-center mb-7">
                            <!--begin::Avatar-->
                            <div class="symbol symbol-50px me-5">
                                <img src="theme/1/media/avatars/300-5.jpg" class="" alt=""/>
                            </div>
                            <!--end::Avatar-->
                            <!--begin::Text-->
                            <div class="flex-grow-1">
                                <a href="#" class="text-gray-900 fw-bold text-hover-primary fs-6">محسن برومند</a>
                                <span class="text-muted d-block fw-bold">PHP, SQLite, هنرisan CLI</span>
                            </div>
                            <!--end::Text-->
                        </div>
                        <!--end::item-->
                        <!--begin::item-->
                        <div class="d-flex align-items-center mb-7">
                            <!--begin::Avatar-->
                            <div class="symbol symbol-50px me-5">
                                <img src="theme/1/media/avatars/300-11.jpg" class="" alt=""/>
                            </div>
                            <!--end::Avatar-->
                            <!--begin::Text-->
                            <div class="flex-grow-1">
                                <a href="#" class="text-gray-900 fw-bold text-hover-primary fs-6">رضا علی ابادی</a>
                                <span class="text-muted d-block fw-bold">PHP, SQLite, هنرisan CLI</span>
                            </div>
                            <!--end::Text-->
                        </div>
                        <!--end::item-->
                        <!--begin::item-->
                        <div class="d-flex align-items-center mb-7">
                            <!--begin::Avatar-->
                            <div class="symbol symbol-50px me-5">
                                <img src="theme/1/media/avatars/300-9.jpg" class="" alt=""/>
                            </div>
                            <!--end::Avatar-->
                            <!--begin::Text-->
                            <div class="flex-grow-1">
                                <a href="#" class="text-gray-900 fw-bold text-hover-primary fs-6">محمد رصایی</a>
                                <span class="text-muted d-block fw-bold">PHP, SQLite, هنرisan CLI</span>
                            </div>
                            <!--end::Text-->
                        </div>
                        <!--end::item-->
                        <!--begin::item-->
                        <div class="d-flex align-items-center">
                            <!--begin::Avatar-->
                            <div class="symbol symbol-50px me-5">
                                <img src="theme/1/media/avatars/300-23.jpg" class="" alt=""/>
                            </div>
                            <!--end::Avatar-->
                            <!--begin::Text-->
                            <div class="flex-grow-1">
                                <a href="#" class="text-gray-900 fw-bold text-hover-primary fs-6">علی کاربر</a>
                                <span class="text-muted d-block fw-bold">PHP, SQLite, هنرisan CLI</span>
                            </div>
                            <!--end::Text-->
                        </div>
                        <!--end::item-->
                    </div>
                    <!--end::Body-->
                </div>
                <!--end::لیست Widget 2-->
            </div>
            <!--end::Col-->
            <!--begin::Col-->
            <div class="col-xl-4">
                <!--begin::لیست Widget 6-->
                <div class="card card-xl-stretch mb-xl-8">
                    <!--begin::Header-->
                    <div class="card-header border-0">
                        <h3 class="card-title fw-bold text-gray-900">اعلان ها</h3>
                        <div class="card-toolbar">
                            <!--begin::Menu-->
                            <button type="button" class="btn btn-sm btn-icon btn-color-primary btn-active-light-primary"
                                    data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                <i class="ki-duotone ki-category fs-6">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </button>
                            <!--begin::Menu 3-->
                            <div
                                class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-3"
                                data-kt-menu="true">
                                <!--begin::Heading-->
                                <div class="menu-item px-3">
                                    <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">درگاه ها</div>
                                </div>
                                <!--end::Heading-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">ساختن فاکتور</a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link flex-stack px-3">ساختن پرداخت
                                        <span class="ms-2" data-bs-toggle="tooltip"
                                              title="یک نام هدف را برای استفاده و مرجع در آینده مشخص کنید">
															<i class="ki-duotone ki-information fs-6">
																<span class="path1"></span>
																<span class="path2"></span>
																<span class="path3"></span>
															</i>
														</span></a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">بیل تولید کنید</a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3" data-kt-menu-trigger="hover"
                                     data-kt-menu-placement="right-end">
                                    <a href="#" class="menu-link px-3">
                                        <span class="menu-title">اشتراک</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <!--begin::Menu sub-->
                                    <div class="menu-sub menu-sub-dropdown w-175px py-4">
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">برنامه ریزی ها</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">صورتحساب</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">بیانه ها</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu separator-->
                                        <div class="separator my-2"></div>
                                        <!--end::Menu separator-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <div class="menu-content px-3">
                                                <!--begin::Switch-->
                                                <label
                                                    class="form-check form-switch form-check-custom form-check-solid">
                                                    <!--begin::Input-->
                                                    <input class="form-check-input w-30px h-20px" type="checkbox"
                                                           value="1" checked="checked" name="notifications"/>
                                                    <!--end::Input-->
                                                    <!--end::Tags-->
                                                    <span class="form-check-label text-muted fs-6">وضعیت</span>
                                                    <!--end::Tags-->
                                                </label>
                                                <!--end::Switch-->
                                            </div>
                                        </div>
                                        <!--end::Menu item-->
                                    </div>
                                    <!--end::Menu sub-->
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3 my-1">
                                    <a href="#" class="menu-link px-3">تنظیمات</a>
                                </div>
                                <!--end::Menu item-->
                            </div>
                            <!--end::Menu 3-->
                            <!--end::Menu-->
                        </div>
                    </div>
                    <!--end::Header-->
                    <!--begin::Body-->
                    <div class="card-body pt-0">
                        <!--begin::item-->
                        <div class="d-flex align-items-center bg-light-warning rounded p-5 mb-7">
                            <i class="ki-duotone ki-abstract-26 text-warning fs-1 me-5">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <!--begin::Title-->
                            <div class="flex-grow-1 me-2">
                                <a href="#" class="fw-bold text-gray-800 text-hover-primary fs-6">جشن ناهار گروهی</a>
                                <span class="text-muted fw-semibold d-block">سررسید در 2 روز</span>
                            </div>
                            <!--end::Title-->
                            <!--begin::Lable-->
                            <span class="fw-bold text-warning py-1">+28%</span>
                            <!--end::Lable-->
                        </div>
                        <!--end::item-->
                        <!--begin::item-->
                        <div class="d-flex align-items-center bg-light-success rounded p-5 mb-7">
                            <i class="ki-duotone ki-abstract-26 text-success fs-1 me-5">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <!--begin::Title-->
                            <div class="flex-grow-1 me-2">
                                <a href="#" class="fw-bold text-gray-800 text-hover-primary fs-6">بهینه سازی پیمایش</a>
                                <span class="text-muted fw-semibold d-block">سررسید در 2 روز</span>
                            </div>
                            <!--end::Title-->
                            <!--begin::Lable-->
                            <span class="fw-bold text-success py-1">+50%</span>
                            <!--end::Lable-->
                        </div>
                        <!--end::item-->
                        <!--begin::item-->
                        <div class="d-flex align-items-center bg-light-danger rounded p-5 mb-7">
                            <i class="ki-duotone ki-abstract-26 text-danger fs-1 me-5">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <!--begin::Title-->
                            <div class="flex-grow-1 me-2">
                                <a href="#" class="fw-bold text-gray-800 text-hover-primary fs-6">تغییر نام برنامه ریزی
                                    استراتژی</a>
                                <span class="text-muted fw-semibold d-block">سررسید در 5 روز</span>
                            </div>
                            <!--end::Title-->
                            <!--begin::Lable-->
                            <span class="fw-bold text-danger py-1">-27%</span>
                            <!--end::Lable-->
                        </div>
                        <!--end::item-->
                        <!--begin::item-->
                        <div class="d-flex align-items-center bg-light-info rounded p-5">
                            <i class="ki-duotone ki-abstract-26 text-info fs-1 me-5">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <!--begin::Title-->
                            <div class="flex-grow-1 me-2">
                                <a href="#" class="fw-bold text-gray-800 text-hover-primary fs-6">استراتژی اهداف
                                    محصول</a>
                                <span class="text-muted fw-semibold d-block">سررسید در 7 روز پیش</span>
                            </div>
                            <!--end::Title-->
                            <!--begin::Lable-->
                            <span class="fw-bold text-info py-1">+8%</span>
                            <!--end::Lable-->
                        </div>
                        <!--end::item-->
                    </div>
                    <!--end::Body-->
                </div>
                <!--end::لیست Widget 6-->
            </div>
            <!--end::Col-->
            <!--begin::Col-->
            <div class="col-xl-4">
                <!--begin::لیست Widget 4-->
                <div class="card card-xl-stretch mb-5 mb-xl-8">
                    <!--begin::Header-->
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">روندها</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">جدیدترین روندهای فناوری</span>
                        </h3>
                        <div class="card-toolbar">
                            <!--begin::Menu-->
                            <button type="button" class="btn btn-sm btn-icon btn-color-primary btn-active-light-primary"
                                    data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                <i class="ki-duotone ki-category fs-6">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </button>
                            <!--begin::Menu 3-->
                            <div
                                class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-3"
                                data-kt-menu="true">
                                <!--begin::Heading-->
                                <div class="menu-item px-3">
                                    <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">درگاه ها</div>
                                </div>
                                <!--end::Heading-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">ساختن فاکتور</a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link flex-stack px-3">ساختن پرداخت
                                        <span class="ms-2" data-bs-toggle="tooltip"
                                              title="یک نام هدف را برای استفاده و مرجع در آینده مشخص کنید">
															<i class="ki-duotone ki-information fs-6">
																<span class="path1"></span>
																<span class="path2"></span>
																<span class="path3"></span>
															</i>
														</span></a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">بیل تولید کنید</a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3" data-kt-menu-trigger="hover"
                                     data-kt-menu-placement="right-end">
                                    <a href="#" class="menu-link px-3">
                                        <span class="menu-title">اشتراک</span>
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <!--begin::Menu sub-->
                                    <div class="menu-sub menu-sub-dropdown w-175px py-4">
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">برنامه ریزی ها</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">صورتحساب</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3">بیانه ها</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu separator-->
                                        <div class="separator my-2"></div>
                                        <!--end::Menu separator-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <div class="menu-content px-3">
                                                <!--begin::Switch-->
                                                <label
                                                    class="form-check form-switch form-check-custom form-check-solid">
                                                    <!--begin::Input-->
                                                    <input class="form-check-input w-30px h-20px" type="checkbox"
                                                           value="1" checked="checked" name="notifications"/>
                                                    <!--end::Input-->
                                                    <!--end::Tags-->
                                                    <span class="form-check-label text-muted fs-6">وضعیت</span>
                                                    <!--end::Tags-->
                                                </label>
                                                <!--end::Switch-->
                                            </div>
                                        </div>
                                        <!--end::Menu item-->
                                    </div>
                                    <!--end::Menu sub-->
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3 my-1">
                                    <a href="#" class="menu-link px-3">تنظیمات</a>
                                </div>
                                <!--end::Menu item-->
                            </div>
                            <!--end::Menu 3-->
                            <!--end::Menu-->
                        </div>
                    </div>
                    <!--end::Header-->
                    <!--begin::Body-->
                    <div class="card-body pt-5">
                        <!--begin::item-->
                        <div class="d-flex align-items-sm-center mb-7">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-50px me-5">
													<span class="symbol-label">
														<img src="theme/1/media/svg/brand-logos/plurk.svg"
                                                             class="h-50 align-self-center" alt=""/>
													</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Section-->
                            <div class="d-flex align-items-center flex-row-fluid flex-wrap">
                                <div class="flex-grow-1 me-2">
                                    <a href="#" class="text-gray-800 text-hover-primary fs-6 fw-bold">بالا نویسندگان</a>
                                    <span class="text-muted fw-semibold d-block fs-7">جوادی و جمشیدی</span>
                                </div>
                                <span class="badge badge-light fw-bold my-2">+82$</span>
                            </div>
                            <!--end::Section-->
                        </div>
                        <!--end::item-->
                        <!--begin::item-->
                        <div class="d-flex align-items-sm-center mb-7">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-50px me-5">
													<span class="symbol-label">
														<img src="theme/1/media/svg/brand-logos/telegram.svg"
                                                             class="h-50 align-self-center" alt=""/>
													</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Section-->
                            <div class="d-flex align-items-center flex-row-fluid flex-wrap">
                                <div class="flex-grow-1 me-2">
                                    <a href="#" class="text-gray-800 text-hover-primary fs-6 fw-bold">محبوب
                                        نویسندگان</a>
                                    <span class="text-muted fw-semibold d-block fs-7">جواد محمدی</span>
                                </div>
                                <span class="badge badge-light fw-bold my-2">+280$</span>
                            </div>
                            <!--end::Section-->
                        </div>
                        <!--end::item-->
                        <!--begin::item-->
                        <div class="d-flex align-items-sm-center mb-7">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-50px me-5">
													<span class="symbol-label">
														<img src="theme/1/media/svg/brand-logos/vimeo.svg"
                                                             class="h-50 align-self-center" alt=""/>
													</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Section-->
                            <div class="d-flex align-items-center flex-row-fluid flex-wrap">
                                <div class="flex-grow-1 me-2">
                                    <a href="#" class="text-gray-800 text-hover-primary fs-6 fw-bold">جدید کاربران</a>
                                    <span class="text-muted fw-semibold d-block fs-7">محمدی و سعیدی</span>
                                </div>
                                <span class="badge badge-light fw-bold my-2">+4500$</span>
                            </div>
                            <!--end::Section-->
                        </div>
                        <!--end::item-->
                        <!--begin::item-->
                        <div class="d-flex align-items-sm-center mb-7">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-50px me-5">
													<span class="symbol-label">
														<img src="theme/1/media/svg/brand-logos/bebo.svg"
                                                             class="h-50 align-self-center" alt=""/>
													</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Section-->
                            <div class="d-flex align-items-center flex-row-fluid flex-wrap">
                                <div class="flex-grow-1 me-2">
                                    <a href="#" class="text-gray-800 text-hover-primary fs-6 fw-bold">فعال مشتریان</a>
                                    <span class="text-muted fw-semibold d-block fs-7">جوادی و جمشیدی</span>
                                </div>
                                <span class="badge badge-light fw-bold my-2">+686$</span>
                            </div>
                            <!--end::Section-->
                        </div>
                        <!--end::item-->
                        <!--begin::item-->
                        <div class="d-flex align-items-sm-center mb-7">
                            <!--begin::Symbol-->
                            <div class="symbol symbol-50px me-5">
													<span class="symbol-label">
														<img src="theme/1/media/svg/brand-logos/kickstarter.svg"
                                                             class="h-50 align-self-center" alt=""/>
													</span>
                            </div>
                            <!--end::Symbol-->
                            <!--begin::Section-->
                            <div class="d-flex align-items-center flex-row-fluid flex-wrap">
                                <div class="flex-grow-1 me-2">
                                    <a href="#" class="text-gray-800 text-hover-primary fs-6 fw-bold">تم پرفروش</a>
                                    <span class="text-muted fw-semibold d-block fs-7">دیسکو، رترو، اسپیاتز</span>
                                </div>
                                <span class="badge badge-light fw-bold my-2">+726$</span>
                            </div>
                            <!--end::Section-->
                        </div>
                        <!--end::item-->
                    </div>
                    <!--end::Body-->
                </div>
                <!--end::لیست Widget 4-->
            </div>
            <!--end::Col-->
        </div>
        <!--end::Row-->
    </div>
    <!--end::Post-->
</div>
