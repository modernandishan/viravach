<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div class="d-flex align-items-center ms-1 ms-lg-3">
    <!--begin::drawer toggle-->
    <div class="btn btn-icon btn-active-light-primary btn-custom w-30px h-30px w-md-40px h-md-40px"
         id="kt_activities_toggle">
        <i class="ki-duotone ki-chart-simple fs-1">
            <span class="path1"></span>
            <span class="path2"></span>
            <span class="path3"></span>
            <span class="path4"></span>
        </i>
    </div>
    <!--end::drawer toggle-->
    <!--begin::activities drawer-->
    <div id="kt_activities" class="bg-body" data-kt-drawer="true" data-kt-drawer-name="activities"
         data-kt-drawer-activate="true" data-kt-drawer-overlay="true"
         data-kt-drawer-width="{default:'300px', 'lg': '900px'}" data-kt-drawer-direction="end"
         data-kt-drawer-toggle="#kt_activities_toggle" data-kt-drawer-close="#kt_activities_close">
        <div class="card shadow-none border-0 rounded-0">
            <!--begin::Header-->
            <div class="card-header" id="kt_activities_header">
                <h3 class="card-title fw-bold text-gray-900">گزارش ها</h3>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-sm btn-icon btn-active-light-primary me-n5"
                            id="kt_activities_close">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </button>
                </div>
            </div>
            <!--end::Header-->
            <!--begin::Body-->
            <div class="card-body position-relative" id="kt_activities_body">
                <!--begin::Content-->
                <div id="kt_activities_scroll" class="position-relative scroll-y me-n5 pe-5" data-kt-scroll="true"
                     data-kt-scroll-height="auto" data-kt-scroll-wrappers="#kt_activities_body"
                     data-kt-scroll-dependencies="#kt_activities_header, #kt_activities_footer"
                     data-kt-scroll-offset="5px">
                    <!--begin::تایم لاین items-->
                    <div class="تایم لاین تایم لاین-border-dashed">
                        <!--begin::تایم لاین item-->
                        <div class="تایم لاین-item">
                            <!--begin::تایم لاین line-->
                            <div class="تایم لاین-line"></div>
                            <!--end::تایم لاین line-->
                            <!--begin::تایم لاین icon-->
                            <div class="تایم لاین-icon">
                                <i class="ki-duotone ki-message-text-2 fs-2 text-gray-500">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                            </div>
                            <!--end::تایم لاین icon-->
                            <!--begin::تایم لاین content-->
                            <div class="تایم لاین-content mb-10 mt-n1">
                                <!--begin::تایم لاین heading-->
                                <div class="pe-3 mb-5">
                                    <!--begin::Title-->
                                    <div class="fs-5 fw-semibold mb-2">در پروژه اپلیکیشن موبایل کار جدید برای شما وجود
                                        دارد:
                                    </div>
                                    <!--end::Title-->
                                    <!--begin::توضیحات-->
                                    <div class="d-flex align-items-center mt-1 fs-6">
                                        <!--begin::Info-->
                                        <div class="text-muted me-2 fs-7">اضافه شده در ساعت 4:12</div>
                                        <!--end::Info-->
                                        <!--begin::user-->
                                        <div class="symbol symbol-circle symbol-25px" data-bs-toggle="tooltip"
                                             data-bs-boundary="window" data-bs-placement="top" title="Nina Nilson">
                                            <img src="theme/1/media/avatars/300-14.jpg" alt="img"/>
                                        </div>
                                        <!--end::user-->
                                    </div>
                                    <!--end::توضیحات-->
                                </div>
                                <!--end::تایم لاین heading-->
                                <!--begin::تایم لاین details-->
                                <div class="overflow-auto pb-5">
                                    <!--begin::Record-->
                                    <div
                                        class="d-flex align-items-center border border-dashed border-gray-300 rounded min-w-750px px-7 py-3 mb-5">
                                        <!--begin::Title-->
                                        <a href="apps/projects/project.html"
                                           class="fs-5 text-gray-900 text-hover-primary fw-semibold w-375px min-w-200px">ملاقات
                                            با مشتری</a>
                                        <!--end::Title-->
                                        <!--begin::Tags-->
                                        <div class="min-w-175px pe-2">
                                            <span class="badge badge-light text-muted">طراح نرم افزار</span>
                                        </div>
                                        <!--end::Tags-->
                                        <!--begin::users-->
                                        <div class="symbol-group symbol-hover flex-nowrap flex-grow-1 min-w-100px pe-2">
                                            <!--begin::user-->
                                            <div class="symbol symbol-circle symbol-25px">
                                                <img src="theme/1/media/avatars/300-2.jpg" alt="img"/>
                                            </div>
                                            <!--end::user-->
                                            <!--begin::user-->
                                            <div class="symbol symbol-circle symbol-25px">
                                                <img src="theme/1/media/avatars/300-14.jpg" alt="img"/>
                                            </div>
                                            <!--end::user-->
                                            <!--begin::user-->
                                            <div class="symbol symbol-circle symbol-25px">
                                                <div
                                                    class="symbol-label fs-8 fw-semibold bg-primary text-inverse-primary">
                                                    A
                                                </div>
                                            </div>
                                            <!--end::user-->
                                        </div>
                                        <!--end::users-->
                                        <!--begin::پردازش-->
                                        <div class="min-w-125px pe-2">
                                            <span class="badge badge-light-primary">درحال پردازش</span>
                                        </div>
                                        <!--end::پردازش-->
                                        <!--begin::Actions-->
                                        <a href="apps/projects/project.html"
                                           class="btn btn-sm btn-light btn-active-light-primary">نمایش</a>
                                        <!--end::Actions-->
                                    </div>
                                    <!--end::Record-->
                                    <!--begin::Record-->
                                    <div
                                        class="d-flex align-items-center border border-dashed border-gray-300 rounded min-w-750px px-7 py-3 mb-0">
                                        <!--begin::Title-->
                                        <a href="apps/projects/project.html"
                                           class="fs-5 text-gray-900 text-hover-primary fw-semibold w-375px min-w-200px">آماده
                                            سازی تحویل پروژه</a>
                                        <!--end::Title-->
                                        <!--begin::Tags-->
                                        <div class="min-w-175px">
                                            <span class="badge badge-light text-muted">توسعه دهنده سیستم</span>
                                        </div>
                                        <!--end::Tags-->
                                        <!--begin::users-->
                                        <div class="symbol-group symbol-hover flex-nowrap flex-grow-1 min-w-100px">
                                            <!--begin::user-->
                                            <div class="symbol symbol-circle symbol-25px">
                                                <img src="theme/1/media/avatars/300-20.jpg" alt="img"/>
                                            </div>
                                            <!--end::user-->
                                            <!--begin::user-->
                                            <div class="symbol symbol-circle symbol-25px">
                                                <div
                                                    class="symbol-label fs-8 fw-semibold bg-success text-inverse-primary">
                                                    B
                                                </div>
                                            </div>
                                            <!--end::user-->
                                        </div>
                                        <!--end::users-->
                                        <!--begin::پردازش-->
                                        <div class="min-w-125px">
                                            <span class="badge badge-light-success">کامل شد</span>
                                        </div>
                                        <!--end::پردازش-->
                                        <!--begin::Actions-->
                                        <a href="apps/projects/project.html"
                                           class="btn btn-sm btn-light btn-active-light-primary">نمایش</a>
                                        <!--end::Actions-->
                                    </div>
                                    <!--end::Record-->
                                </div>
                                <!--end::تایم لاین details-->
                            </div>
                            <!--end::تایم لاین content-->
                        </div>
                        <!--end::تایم لاین item-->
                        <!--begin::تایم لاین item-->
                        <div class="تایم لاین-item">
                            <!--begin::تایم لاین line-->
                            <div class="تایم لاین-line"></div>
                            <!--end::تایم لاین line-->
                            <!--begin::تایم لاین icon-->
                            <div class="تایم لاین-icon me-4">
                                <i class="ki-duotone ki-flag fs-2 text-gray-500">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <!--end::تایم لاین icon-->
                            <!--begin::تایم لاین content-->
                            <div class="تایم لاین-content mb-10 mt-n2">
                                <!--begin::تایم لاین heading-->
                                <div class="overflow-auto pe-3">
                                    <!--begin::Title-->
                                    <div class="fs-5 fw-semibold mb-2">دعوت نامه برای ساخت طراحی های جذاب که کارگاه
                                        انسانی را بیان می کنند
                                    </div>
                                    <!--end::Title-->
                                    <!--begin::توضیحات-->
                                    <div class="d-flex align-items-center mt-1 fs-6">
                                        <!--begin::Info-->
                                        <div class="text-muted me-2 fs-7">ارسال شده در ساعت 4:23</div>
                                        <!--end::Info-->
                                        <!--begin::user-->
                                        <div class="symbol symbol-circle symbol-25px" data-bs-toggle="tooltip"
                                             data-bs-boundary="window" data-bs-placement="top" title="Alan Nilson">
                                            <img src="theme/1/media/avatars/300-1.jpg" alt="img"/>
                                        </div>
                                        <!--end::user-->
                                    </div>
                                    <!--end::توضیحات-->
                                </div>
                                <!--end::تایم لاین heading-->
                            </div>
                            <!--end::تایم لاین content-->
                        </div>
                        <!--end::تایم لاین item-->
                        <!--begin::تایم لاین item-->
                        <div class="تایم لاین-item">
                            <!--begin::تایم لاین line-->
                            <div class="تایم لاین-line"></div>
                            <!--end::تایم لاین line-->
                            <!--begin::تایم لاین icon-->
                            <div class="تایم لاین-icon">
                                <i class="ki-duotone ki-disconnect fs-2 text-gray-500">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                    <span class="path5"></span>
                                </i>
                            </div>
                            <!--end::تایم لاین icon-->
                            <!--begin::تایم لاین content-->
                            <div class="تایم لاین-content mb-10 mt-n1">
                                <!--begin::تایم لاین heading-->
                                <div class="mb-5 pe-3">
                                    <!--begin::Title-->
                                    <a href="#" class="fs-5 fw-semibold text-gray-800 text-hover-primary mb-2">3 پروژه
                                        ورودی جدید پرونده ها:</a>
                                    <!--end::Title-->
                                    <!--begin::توضیحات-->
                                    <div class="d-flex align-items-center mt-1 fs-6">
                                        <!--begin::Info-->
                                        <div class="text-muted me-2 fs-7">ارسال شده در ساعت 10:30</div>
                                        <!--end::Info-->
                                        <!--begin::user-->
                                        <div class="symbol symbol-circle symbol-25px" data-bs-toggle="tooltip"
                                             data-bs-boundary="window" data-bs-placement="top" title="Jan Hummer">
                                            <img src="theme/1/media/avatars/300-23.jpg" alt="img"/>
                                        </div>
                                        <!--end::user-->
                                    </div>
                                    <!--end::توضیحات-->
                                </div>
                                <!--end::تایم لاین heading-->
                                <!--begin::تایم لاین details-->
                                <div class="overflow-auto pb-5">
                                    <div
                                        class="d-flex align-items-center border border-dashed border-gray-300 rounded min-w-700px p-5">
                                        <!--begin::item-->
                                        <div class="d-flex flex-aligns-center pe-10 pe-lg-20">
                                            <!--begin::Icon-->
                                            <img alt="" class="w-30px me-3" src="theme/1/media/svg/files/pdf.svg"/>
                                            <!--end::Icon-->
                                            <!--begin::Info-->
                                            <div class="ms-1 fw-semibold">
                                                <!--begin::Desc-->
                                                <a href="apps/projects/project.html"
                                                   class="fs-6 text-hover-primary fw-bold">دارایی، مالیه، سرمایه
                                                    گذاری </a>
                                                <!--end::Desc-->
                                                <!--begin::شماره کارت-->
                                                <div class="text-gray-500">1.9mb</div>
                                                <!--end::شماره کارت-->
                                            </div>
                                            <!--begin::Info-->
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex flex-aligns-center pe-10 pe-lg-20">
                                            <!--begin::Icon-->
                                            <img alt="apps/projects/project.html" class="w-30px me-3"
                                                 src="theme/1/media/svg/files/doc.svg"/>
                                            <!--end::Icon-->
                                            <!--begin::Info-->
                                            <div class="ms-1 fw-semibold">
                                                <!--begin::Desc-->
                                                <a href="#" class="fs-6 text-hover-primary fw-bold">مشتری نتایج تست</a>
                                                <!--end::Desc-->
                                                <!--begin::شماره کارت-->
                                                <div class="text-gray-500">18kb</div>
                                                <!--end::شماره کارت-->
                                            </div>
                                            <!--end::Info-->
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="d-flex flex-aligns-center">
                                            <!--begin::Icon-->
                                            <img alt="apps/projects/project.html" class="w-30px me-3"
                                                 src="theme/1/media/svg/files/css.svg"/>
                                            <!--end::Icon-->
                                            <!--begin::Info-->
                                            <div class="ms-1 fw-semibold">
                                                <!--begin::Desc-->
                                                <a href="#" class="fs-6 text-hover-primary fw-bold">دارایی، مالیه،
                                                    سرمایه گذاری گزارشات</a>
                                                <!--end::Desc-->
                                                <!--begin::شماره کارت-->
                                                <div class="text-gray-500">20mb</div>
                                                <!--end::شماره کارت-->
                                            </div>
                                            <!--end::Icon-->
                                        </div>
                                        <!--end::item-->
                                    </div>
                                </div>
                                <!--end::تایم لاین details-->
                            </div>
                            <!--end::تایم لاین content-->
                        </div>
                        <!--end::تایم لاین item-->
                        <!--begin::تایم لاین item-->
                        <div class="تایم لاین-item">
                            <!--begin::تایم لاین line-->
                            <div class="تایم لاین-line"></div>
                            <!--end::تایم لاین line-->
                            <!--begin::تایم لاین icon-->
                            <div class="تایم لاین-icon">
                                <i class="ki-duotone ki-abstract-26 fs-2 text-gray-500">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <!--end::تایم لاین icon-->
                            <!--begin::تایم لاین content-->
                            <div class="تایم لاین-content mb-10 mt-n1">
                                <!--begin::تایم لاین heading-->
                                <div class="pe-3 mb-5">
                                    <!--begin::Title-->
                                    <div class="fs-5 fw-semibold mb-2">وظیفه
                                        <a href="#" class="text-primary fw-bold me-1">#45890</a>ادغام با
                                        <a href="#" class="text-primary fw-bold me-1">#45890</a>داشبورد پروژه ها:
                                    </div>
                                    <!--end::Title-->
                                    <!--begin::توضیحات-->
                                    <div class="d-flex align-items-center mt-1 fs-6">
                                        <!--begin::Info-->
                                        <div class="text-muted me-2 fs-7">آغاز شده در 4:23 بعد از ظهر توسط</div>
                                        <!--end::Info-->
                                        <!--begin::user-->
                                        <div class="symbol symbol-circle symbol-25px" data-bs-toggle="tooltip"
                                             data-bs-boundary="window" data-bs-placement="top" title="Nina Nilson">
                                            <img src="theme/1/media/avatars/300-14.jpg" alt="img"/>
                                        </div>
                                        <!--end::user-->
                                    </div>
                                    <!--end::توضیحات-->
                                </div>
                                <!--end::تایم لاین heading-->
                            </div>
                            <!--end::تایم لاین content-->
                        </div>
                        <!--end::تایم لاین item-->
                        <!--begin::تایم لاین item-->
                        <div class="تایم لاین-item">
                            <!--begin::تایم لاین line-->
                            <div class="تایم لاین-line"></div>
                            <!--end::تایم لاین line-->
                            <!--begin::تایم لاین icon-->
                            <div class="تایم لاین-icon">
                                <i class="ki-duotone ki-pencil fs-2 text-gray-500">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <!--end::تایم لاین icon-->
                            <!--begin::تایم لاین content-->
                            <div class="تایم لاین-content mb-10 mt-n1">
                                <!--begin::تایم لاین heading-->
                                <div class="pe-3 mb-5">
                                    <!--begin::Title-->
                                    <div class="fs-5 fw-semibold mb-2">3 مفهوم جدید طراحی برنامه اضافه شده است:</div>
                                    <!--end::Title-->
                                    <!--begin::توضیحات-->
                                    <div class="d-flex align-items-center mt-1 fs-6">
                                        <!--begin::Info-->
                                        <div class="text-muted me-2 fs-7">ایجاد شده در 4:23 بعد از ظهر توسط</div>
                                        <!--end::Info-->
                                        <!--begin::user-->
                                        <div class="symbol symbol-circle symbol-25px" data-bs-toggle="tooltip"
                                             data-bs-boundary="window" data-bs-placement="top" title="Marcus Dotson">
                                            <img src="theme/1/media/avatars/300-2.jpg" alt="img"/>
                                        </div>
                                        <!--end::user-->
                                    </div>
                                    <!--end::توضیحات-->
                                </div>
                                <!--end::تایم لاین heading-->
                                <!--begin::تایم لاین details-->
                                <div class="overflow-auto pb-5">
                                    <div
                                        class="d-flex align-items-center border border-dashed border-gray-300 rounded min-w-700px p-7">
                                        <!--begin::item-->
                                        <div class="overlay me-10">
                                            <!--begin::Image-->
                                            <div class="overlay-wrapper">
                                                <img alt="img" class="rounded w-150px"
                                                     src="theme/1/media/stock/600x400/img-29.jpg"/>
                                            </div>
                                            <!--end::Image-->
                                            <!--begin::Link-->
                                            <div class="overlay-layer bg-dark bg-opacity-10 rounded">
                                                <a href="#" class="btn btn-sm btn-primary btn-shadow">کاوش کنید</a>
                                            </div>
                                            <!--end::Link-->
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="overlay me-10">
                                            <!--begin::Image-->
                                            <div class="overlay-wrapper">
                                                <img alt="img" class="rounded w-150px"
                                                     src="theme/1/media/stock/600x400/img-31.jpg"/>
                                            </div>
                                            <!--end::Image-->
                                            <!--begin::Link-->
                                            <div class="overlay-layer bg-dark bg-opacity-10 rounded">
                                                <a href="#" class="btn btn-sm btn-primary btn-shadow">کاوش کنید</a>
                                            </div>
                                            <!--end::Link-->
                                        </div>
                                        <!--end::item-->
                                        <!--begin::item-->
                                        <div class="overlay">
                                            <!--begin::Image-->
                                            <div class="overlay-wrapper">
                                                <img alt="img" class="rounded w-150px"
                                                     src="theme/1/media/stock/600x400/img-40.jpg"/>
                                            </div>
                                            <!--end::Image-->
                                            <!--begin::Link-->
                                            <div class="overlay-layer bg-dark bg-opacity-10 rounded">
                                                <a href="#" class="btn btn-sm btn-primary btn-shadow">کاوش کنید</a>
                                            </div>
                                            <!--end::Link-->
                                        </div>
                                        <!--end::item-->
                                    </div>
                                </div>
                                <!--end::تایم لاین details-->
                            </div>
                            <!--end::تایم لاین content-->
                        </div>
                        <!--end::تایم لاین item-->
                        <!--begin::تایم لاین item-->
                        <div class="تایم لاین-item">
                            <!--begin::تایم لاین line-->
                            <div class="تایم لاین-line"></div>
                            <!--end::تایم لاین line-->
                            <!--begin::تایم لاین icon-->
                            <div class="تایم لاین-icon">
                                <i class="ki-duotone ki-sms fs-2 text-gray-500">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <!--end::تایم لاین icon-->
                            <!--begin::تایم لاین content-->
                            <div class="تایم لاین-content mb-10 mt-n1">
                                <!--begin::تایم لاین heading-->
                                <div class="pe-3 mb-5">
                                    <!--begin::Title-->
                                    <div class="fs-5 fw-semibold mb-2">کیس جدید
                                        <a href="#" class="text-primary fw-bold me-1">#67890</a>در پروژه چند پلتفرمی
                                        دیتابیس دیزاین به شما واگذار شده است
                                    </div>
                                    <!--end::Title-->
                                    <!--begin::توضیحات-->
                                    <div class="overflow-auto pb-5">
                                        <!--begin::Wrapper-->
                                        <div class="d-flex align-items-center mt-1 fs-6">
                                            <!--begin::Info-->
                                            <div class="text-muted me-2 fs-7">اضافه شده در ساعت 4:12</div>
                                            <!--end::Info-->
                                            <!--begin::user-->
                                            <a href="#" class="text-primary fw-bold me-1">رضا علی ابادی</a>
                                            <!--end::user-->
                                        </div>
                                        <!--end::Wrapper-->
                                    </div>
                                    <!--end::توضیحات-->
                                </div>
                                <!--end::تایم لاین heading-->
                            </div>
                            <!--end::تایم لاین content-->
                        </div>
                        <!--end::تایم لاین item-->
                        <!--begin::تایم لاین item-->
                        <div class="تایم لاین-item">
                            <!--begin::تایم لاین line-->
                            <div class="تایم لاین-line"></div>
                            <!--end::تایم لاین line-->
                            <!--begin::تایم لاین icon-->
                            <div class="تایم لاین-icon">
                                <i class="ki-duotone ki-pencil fs-2 text-gray-500">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <!--end::تایم لاین icon-->
                            <!--begin::تایم لاین content-->
                            <div class="تایم لاین-content mb-10 mt-n1">
                                <!--begin::تایم لاین heading-->
                                <div class="pe-3 mb-5">
                                    <!--begin::Title-->
                                    <div class="fs-5 fw-semibold mb-2">رسید به دست شما سفارش جدید</div>
                                    <!--end::Title-->
                                    <!--begin::توضیحات-->
                                    <div class="d-flex align-items-center mt-1 fs-6">
                                        <!--begin::Info-->
                                        <div class="text-muted me-2 fs-7">در 5:05 صبح توسط</div>
                                        <!--end::Info-->
                                        <!--begin::user-->
                                        <div class="symbol symbol-circle symbol-25px" data-bs-toggle="tooltip"
                                             data-bs-boundary="window" data-bs-placement="top" title="Robert Rich">
                                            <img src="theme/1/media/avatars/300-4.jpg" alt="img"/>
                                        </div>
                                        <!--end::user-->
                                    </div>
                                    <!--end::توضیحات-->
                                </div>
                                <!--end::تایم لاین heading-->
                                <!--begin::تایم لاین details-->
                                <div class="overflow-auto pb-5">
                                    <!--begin::Notice-->
                                    <div
                                        class="notice d-flex bg-light-primary rounded border-primary border border-dashed min-w-lg-600px flex-shrink-0 p-6">
                                        <!--begin::Icon-->
                                        <i class="ki-duotone ki-devices-2 fs-2tx text-primary me-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                        <!--end::Icon-->
                                        <!--begin::Wrapper-->
                                        <div class="d-flex flex-stack flex-grow-1 flex-wrap flex-md-nowrap">
                                            <!--begin::Content-->
                                            <div class="mb-3 mb-md-0 fw-semibold">
                                                <h4 class="text-gray-900 fw-bold">پردازش دیتابی کامل شد</h4>
                                                <div class="fs-6 text-gray-700 pe-7">وارد ادمین داشبورد شوید تا مطمئن
                                                    شوید که یکپارچگی داده ها موفق است
                                                </div>
                                            </div>
                                            <!--end::Content-->
                                            <!--begin::Actions-->
                                            <a href="#" class="btn btn-primary px-6 align-self-center text-nowrap">پردازش</a>
                                            <!--end::Actions-->
                                        </div>
                                        <!--end::Wrapper-->
                                    </div>
                                    <!--end::Notice-->
                                </div>
                                <!--end::تایم لاین details-->
                            </div>
                            <!--end::تایم لاین content-->
                        </div>
                        <!--end::تایم لاین item-->
                        <!--begin::تایم لاین item-->
                        <div class="تایم لاین-item">
                            <!--begin::تایم لاین line-->
                            <div class="تایم لاین-line"></div>
                            <!--end::تایم لاین line-->
                            <!--begin::تایم لاین icon-->
                            <div class="تایم لاین-icon">
                                <i class="ki-duotone ki-basket fs-2 text-gray-500">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </div>
                            <!--end::تایم لاین icon-->
                            <!--begin::تایم لاین content-->
                            <div class="تایم لاین-content mt-n1">
                                <!--begin::تایم لاین heading-->
                                <div class="pe-3 mb-5">
                                    <!--begin::Title-->
                                    <div class="fs-5 fw-semibold mb-2">سفارش جدید
                                        <a href="#" class="text-primary fw-bold me-1">#67890</a>برای برنامه ریزی کارگاه
                                        و برآورد بودجه قرار داده شده است
                                    </div>
                                    <!--end::Title-->
                                    <!--begin::توضیحات-->
                                    <div class="d-flex align-items-center mt-1 fs-6">
                                        <!--begin::Info-->
                                        <div class="text-muted me-2 fs-7">در ساعت 4:23 بعد از ظهر توسط</div>
                                        <!--end::Info-->
                                        <!--begin::user-->
                                        <a href="#" class="text-primary fw-bold me-1">محسن علی ابادی</a>
                                        <!--end::user-->
                                    </div>
                                    <!--end::توضیحات-->
                                </div>
                                <!--end::تایم لاین heading-->
                            </div>
                            <!--end::تایم لاین content-->
                        </div>
                        <!--end::تایم لاین item-->
                    </div>
                    <!--end::تایم لاین items-->
                </div>
                <!--end::Content-->
            </div>
            <!--end::Body-->
            <!--begin::Footer-->
            <div class="card-footer py-5 text-center" id="kt_activities_footer">
                <a href="pages/user-profile/activity.html" class="btn btn-bg-body text-primary">نمایش تمام فعالیت ها
                    <i class="ki-duotone ki-arrow-left fs-3 text-primary">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i></a>
            </div>
            <!--end::Footer-->
        </div>
    </div>
    <!--end::activities drawer-->
</div>
