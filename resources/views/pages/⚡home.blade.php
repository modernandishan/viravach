<?php

use App\Livewire\Concerns\RecordsPageView;
use App\Models\GeneralSetting;
use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts::landing')]
class extends Component {

    use RecordsPageView;

    public Page $page;
    public GeneralSetting $gs;

    public function mount(): void
    {
        $this->page = Page::where('slug', '/')->firstOrFail();
        $this->gs = GeneralSetting::current();
        $this->recordPageView($this->page);
    }

    public function render()
    {
        $pageTitle = $this->page->getTranslation('title', app()->getLocale());
        $tagline = $this->gs->getTranslation('site_tagline', app()->getLocale());

        return $this->view()->title("{$pageTitle} | {$tagline}");
    }

};
?>

<div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">



    <!--begin::Post-->
    <div class="content flex-row-fluid" id="kt_content">

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
        </div>
        <!--end::Row-->
    </div>
    <!--end::Post-->


</div>
