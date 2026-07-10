<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ LaravelLocalization::getCurrentLocaleDirection() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content=""/>
    <meta name="keywords" content=""/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta property="og:locale" content="en_US"/>
    <meta property="og:type" content="article"/>
    <meta property="og:title" content=""/>
    <meta property="og:url" content="https://viravach.com"/>
    <meta property="og:site_name" content="Viravach"/>
    <link rel="canonical" href=""/>

    <link rel="shortcut icon" href="{{asset('favicon.ico')}}"/>

    <title>{{ $title ?? config('app.name') }}</title>

    <!--begin::Fonts(mandatory for all pages)-->
    {{--<link rel="stylesheet" href="https://fonts.googleapis.com/cssfamily=Inter:300,400,500,600,700" />--}}
    <!--end::Fonts-->
    <!--begin::Vendor Stylesheets(used for this page only)-->
    <link href="{{asset('theme/1/plugins/custom/fullcalendar/fullcalendar.bundle.css')}}" rel="stylesheet"
          type="text/css"/>
    <link href="{{asset('theme/1/plugins/custom/datatables/datatables.bundle.css')}}" rel="stylesheet" type="text/css"/>
    <!--end::Vendor Stylesheets-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    @if (LaravelLocalization::getCurrentLocaleDirection() === 'rtl')
        <link href="{{asset('theme/1/plugins/global/plugins.bundle.rtl.css')}}" rel="stylesheet" type="text/css"/>
        <link href="{{asset('theme/1/css/style.bundle.rtl.css')}}" rel="stylesheet" type="text/css"/>
    @else
        <link href="{{asset('theme/1/plugins/global/plugins.bundle.css')}}" rel="stylesheet" type="text/css"/>
        <link href="{{asset('theme/1/css/style.bundle.css')}}" rel="stylesheet" type="text/css"/>
    @endif
    <!--end::Global Stylesheets Bundle-->
    <!--begin::Sticky header stacking fix-->
    <!-- KTDrawer overlays (.drawer-overlay) are appended to <body> at z-index 109. Once the
         header becomes sticky it switches to position:fixed with z-index 97, which creates a
         stacking context that traps any drawer/menu rendered inside the header (e.g. the
         toolbar's activities/chat/quick-links drawers) below that overlay. Raising the sticky
         header's z-index above the overlay keeps those drawers visible while scrolled. -->
    <style>
        .header-fixed[data-kt-sticky-header=on] .header,
        .header-tablet-and-mobile-fixed[data-kt-sticky-header=on] .header {
            z-index: 111;
        }
    </style>
    <!--end::Sticky header stacking fix-->
    <script>
        // Frame-busting to prevent site from being loaded within a frame without permission (click-jacking) if (window.top != window.self) { window.top.location.replace(window.self.location.href); }
    </script>

    {{--@vite(['resources/css/app.css', 'resources/js/app.js'])--}}

    @livewireStyles

</head>
<body id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled">
<!--begin::Theme mode setup on page load-->
<script>
    var defaultThemeMode = "light";
    var themeMode;
    if (document.documentElement) {
        if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
            themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
        } else {
            if (localStorage.getItem("data-bs-theme") !== null) {
                themeMode = localStorage.getItem("data-bs-theme");
            } else {
                themeMode = defaultThemeMode;
            }
        }
        if (themeMode === "system") {
            themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
        }
        document.documentElement.setAttribute("data-bs-theme", themeMode);
    }
</script>
<!--end::Theme mode setup on page load-->
<!--begin::main-->


<!--end::main-->
<!--begin::Root-->
<div class="d-flex flex-column flex-root">
    <!--begin::Page-->
    <div class="page d-flex flex-row flex-column-fluid">
        <!--begin::Wrapper-->
        <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
            <!--begin::Header-->
            <livewire:header />
            <!--end::Header-->
            <!--begin::Toolbar-->
            <livewire:after-menu />
            <!--end::Toolbar-->
            <!--begin::Container-->
            {{ $slot }}
            <!--end::Container-->
            <!--begin::Footer-->
            <livewire:footer />
            <!--end::Footer-->
        </div>
        <!--end::Wrapper-->
    </div>
    <!--end::Page-->
</div>
<!--end::Root-->

<!--begin::scroll-top-->
<livewire:global-elements.scroll-top />
<!--end::scroll-top-->

<!--begin::Modals-->

<!--begin::Modal - create app-->
<div class="modal fade" id="kt_modal_create_app" tabindex="-1" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Modal header-->
            <div class="modal-header">
                <!--begin::Modal title-->
                <h2>ساختن اپ</h2>
                <!--end::Modal title-->
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
            <div class="modal-body py-lg-10 px-lg-10">
                <!--begin::Stepper-->
                <div class="stepper stepper-pills stepper-column d-flex flex-column flex-xl-row flex-row-fluid" id="kt_modal_create_app_stepper">
                    <!--begin::کناری-->
                    <div class="d-flex justify-content-center justify-content-xl-start flex-row-auto w-100 w-xl-300px">
                        <!--begin::Nav-->
                        <div class="stepper-nav ps-lg-10">
                            <!--begin::مرحله 1-->
                            <div class="stepper-item current" data-kt-stepper-element="nav">
                                <!--begin::Wrapper-->
                                <div class="stepper-wrapper">
                                    <!--begin::Icon-->
                                    <div class="stepper-icon w-40px h-40px">
                                        <i class="ki-duotone ki-check stepper-check fs-2"></i>
                                        <span class="stepper-number">1</span>
                                    </div>
                                    <!--end::Icon-->
                                    <!--begin::Tags-->
                                    <div class="stepper-label">
                                        <h3 class="stepper-title">جزییات</h3>
                                        <div class="stepper-desc">نام اپلیکیشن شما</div>
                                    </div>
                                    <!--end::Tags-->
                                </div>
                                <!--end::Wrapper-->
                                <!--begin::Line-->
                                <div class="stepper-line h-40px"></div>
                                <!--end::Line-->
                            </div>
                            <!--end::مرحله 1-->
                            <!--begin::مرحله 2-->
                            <div class="stepper-item" data-kt-stepper-element="nav">
                                <!--begin::Wrapper-->
                                <div class="stepper-wrapper">
                                    <!--begin::Icon-->
                                    <div class="stepper-icon w-40px h-40px">
                                        <i class="ki-duotone ki-check stepper-check fs-2"></i>
                                        <span class="stepper-number">2</span>
                                    </div>
                                    <!--begin::Icon-->
                                    <!--begin::Tags-->
                                    <div class="stepper-label">
                                        <h3 class="stepper-title">فریمورک</h3>
                                        <div class="stepper-desc">چارچوب برنامه خود را تعریف کنید</div>
                                    </div>
                                    <!--begin::Tags-->
                                </div>
                                <!--end::Wrapper-->
                                <!--begin::Line-->
                                <div class="stepper-line h-40px"></div>
                                <!--end::Line-->
                            </div>
                            <!--end::مرحله 2-->
                            <!--begin::مرحله 3-->
                            <div class="stepper-item" data-kt-stepper-element="nav">
                                <!--begin::Wrapper-->
                                <div class="stepper-wrapper">
                                    <!--begin::Icon-->
                                    <div class="stepper-icon w-40px h-40px">
                                        <i class="ki-duotone ki-check stepper-check fs-2"></i>
                                        <span class="stepper-number">3</span>
                                    </div>
                                    <!--end::Icon-->
                                    <!--begin::Tags-->
                                    <div class="stepper-label">
                                        <h3 class="stepper-title">دیتابیس</h3>
                                        <div class="stepper-desc">انتخاب نوع دیتابیس</div>
                                    </div>
                                    <!--end::Tags-->
                                </div>
                                <!--end::Wrapper-->
                                <!--begin::Line-->
                                <div class="stepper-line h-40px"></div>
                                <!--end::Line-->
                            </div>
                            <!--end::مرحله 3-->
                            <!--begin::مرحله 4-->
                            <div class="stepper-item" data-kt-stepper-element="nav">
                                <!--begin::Wrapper-->
                                <div class="stepper-wrapper">
                                    <!--begin::Icon-->
                                    <div class="stepper-icon w-40px h-40px">
                                        <i class="ki-duotone ki-check stepper-check fs-2"></i>
                                        <span class="stepper-number">4</span>
                                    </div>
                                    <!--end::Icon-->
                                    <!--begin::Tags-->
                                    <div class="stepper-label">
                                        <h3 class="stepper-title">صورتحساب</h3>
                                        <div class="stepper-desc">جزییات پرداخت</div>
                                    </div>
                                    <!--end::Tags-->
                                </div>
                                <!--end::Wrapper-->
                                <!--begin::Line-->
                                <div class="stepper-line h-40px"></div>
                                <!--end::Line-->
                            </div>
                            <!--end::مرحله 4-->
                            <!--begin::مرحله 5-->
                            <div class="stepper-item mark-completed" data-kt-stepper-element="nav">
                                <!--begin::Wrapper-->
                                <div class="stepper-wrapper">
                                    <!--begin::Icon-->
                                    <div class="stepper-icon w-40px h-40px">
                                        <i class="ki-duotone ki-check stepper-check fs-2"></i>
                                        <span class="stepper-number">5</span>
                                    </div>
                                    <!--end::Icon-->
                                    <!--begin::Tags-->
                                    <div class="stepper-label">
                                        <h3 class="stepper-title">کامل شد</h3>
                                        <div class="stepper-desc">بازبینی و ثبت</div>
                                    </div>
                                    <!--end::Tags-->
                                </div>
                                <!--end::Wrapper-->
                            </div>
                            <!--end::مرحله 5-->
                        </div>
                        <!--end::Nav-->
                    </div>
                    <!--begin::کناری-->
                    <!--begin::Content-->
                    <div class="flex-row-fluid py-lg-5 px-lg-15">
                        <!--begin::form-->
                        <form class="form" novalidate="novalidate" id="kt_modal_create_app_form">
                            <!--begin::مرحله 1-->
                            <div class="current" data-kt-stepper-element="content">
                                <div class="w-100">
                                    <!--begin::Input group-->
                                    <div class="fv-row mb-10">
                                        <!--begin::Tags-->
                                        <label class="d-flex align-items-center fs-5 fw-semibold mb-2">
                                            <span class="required">اپلیکیشن نام</span>
                                            <span class="ms-1" data-bs-toggle="tooltip" title="نام برنامه منحصر به فرد خود را مشخص کنید">
														<i class="ki-duotone ki-information-5 text-gray-500 fs-6">
															<span class="path1"></span>
															<span class="path2"></span>
															<span class="path3"></span>
														</i>
													</span>
                                        </label>
                                        <!--end::Tags-->
                                        <!--begin::Input-->
                                        <input type="text" class="form-control form-control-lg form-control-solid" name="name" placeholder="" value="" />
                                        <!--end::Input-->
                                    </div>
                                    <!--end::Input group-->
                                    <!--begin::Input group-->
                                    <div class="fv-row">
                                        <!--begin::Tags-->
                                        <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                            <span class="required">دسته بندی</span>
                                            <span class="ms-1" data-bs-toggle="tooltip" title="انتخاب دسته بندی اپلیکیشن">
														<i class="ki-duotone ki-information-5 text-gray-500 fs-6">
															<span class="path1"></span>
															<span class="path2"></span>
															<span class="path3"></span>
														</i>
													</span>
                                        </label>
                                        <!--end::Tags-->
                                        <!--begin:تنظیمات-->
                                        <div class="fv-row">
                                            <!--begin:Option-->
                                            <label class="d-flex flex-stack mb-5 cursor-pointer">
                                                <!--begin:برچسب-->
                                                <span class="d-flex align-items-center me-2">
															<!--begin:Icon-->
															<span class="symbol symbol-50px me-6">
																<span class="symbol-label bg-light-primary">
																	<i class="ki-duotone ki-compass fs-1 text-primary">
																		<span class="path1"></span>
																		<span class="path2"></span>
																	</i>
																</span>
															</span>
                                                    <!--end:Icon-->
                                                    <!--begin:Info-->
															<span class="d-flex flex-column">
																<span class="fw-bold fs-6">دوره های آنلاین سریع</span>
																<span class="fs-7 text-muted">ایجاد یک ساختار متن واضح فقط یک  سئو است</span>
															</span>
                                                    <!--end:Info-->
														</span>
                                                <!--end:برچسب-->
                                                <!--begin:Input-->
                                                <span class="form-check form-check-custom form-check-solid">
															<input class="form-check-input" type="radio" name="category" value="1" />
														</span>
                                                <!--end:Input-->
                                            </label>
                                            <!--end::Option-->
                                            <!--begin:Option-->
                                            <label class="d-flex flex-stack mb-5 cursor-pointer">
                                                <!--begin:برچسب-->
                                                <span class="d-flex align-items-center me-2">
															<!--begin:Icon-->
															<span class="symbol symbol-50px me-6">
																<span class="symbol-label bg-light-danger">
																	<i class="ki-duotone ki-element-11 fs-1 text-danger">
																		<span class="path1"></span>
																		<span class="path2"></span>
																		<span class="path3"></span>
																		<span class="path4"></span>
																	</i>
																</span>
															</span>
                                                    <!--end:Icon-->
                                                    <!--begin:Info-->
															<span class="d-flex flex-column">
																<span class="fw-bold fs-6">بحث های چهره به چهره</span>
																<span class="fs-7 text-muted">ایجاد یک ساختار متن روشن فقط یک جنبه است</span>
															</span>
                                                    <!--end:Info-->
														</span>
                                                <!--end:برچسب-->
                                                <!--begin:Input-->
                                                <span class="form-check form-check-custom form-check-solid">
															<input class="form-check-input" type="radio" name="category" value="2" />
														</span>
                                                <!--end:Input-->
                                            </label>
                                            <!--end::Option-->
                                            <!--begin:Option-->
                                            <label class="d-flex flex-stack cursor-pointer">
                                                <!--begin:برچسب-->
                                                <span class="d-flex align-items-center me-2">
															<!--begin:Icon-->
															<span class="symbol symbol-50px me-6">
																<span class="symbol-label bg-light-success">
																	<i class="ki-duotone ki-timer fs-1 text-success">
																		<span class="path1"></span>
																		<span class="path2"></span>
																		<span class="path3"></span>
																	</i>
																</span>
															</span>
                                                    <!--end:Icon-->
                                                    <!--begin:Info-->
															<span class="d-flex flex-column">
																<span class="fw-bold fs-6">آموزش مقدماتی کامل</span>
																<span class="fs-7 text-muted">ایجاد یک متن متن روشن برای نوشتن متن</span>
															</span>
                                                    <!--end:Info-->
														</span>
                                                <!--end:برچسب-->
                                                <!--begin:Input-->
                                                <span class="form-check form-check-custom form-check-solid">
															<input class="form-check-input" type="radio" name="category" value="3" />
														</span>
                                                <!--end:Input-->
                                            </label>
                                            <!--end::Option-->
                                        </div>
                                        <!--end:تنظیمات-->
                                    </div>
                                    <!--end::Input group-->
                                </div>
                            </div>
                            <!--end::مرحله 1-->
                            <!--begin::مرحله 2-->
                            <div data-kt-stepper-element="content">
                                <div class="w-100">
                                    <!--begin::Input group-->
                                    <div class="fv-row">
                                        <!--begin::Tags-->
                                        <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                            <span class="required">انتخاب فریمورک</span>
                                            <span class="ms-1" data-bs-toggle="tooltip" title="فریمویاک برنامه های خود را مشخص کنید">
														<i class="ki-duotone ki-information-5 text-gray-500 fs-6">
															<span class="path1"></span>
															<span class="path2"></span>
															<span class="path3"></span>
														</i>
													</span>
                                        </label>
                                        <!--end::Tags-->
                                        <!--begin:Option-->
                                        <label class="d-flex flex-stack cursor-pointer mb-5">
                                            <!--begin:برچسب-->
                                            <span class="d-flex align-items-center me-2">
														<!--begin:Icon-->
														<span class="symbol symbol-50px me-6">
															<span class="symbol-label bg-light-warning">
																<i class="ki-duotone ki-html fs-2x text-warning">
																	<span class="path1"></span>
																	<span class="path2"></span>
																</i>
															</span>
														</span>
                                                <!--end:Icon-->
                                                <!--begin:Info-->
														<span class="d-flex flex-column">
															<span class="fw-bold fs-6">HTML5</span>
															<span class="fs-7 text-muted">Base Web حرفه ایjec</span>
														</span>
                                                <!--end:Info-->
													</span>
                                            <!--end:برچسب-->
                                            <!--begin:Input-->
                                            <span class="form-check form-check-custom form-check-solid">
														<input class="form-check-input" type="radio" checked="checked" name="framework" value="1" />
													</span>
                                            <!--end:Input-->
                                        </label>
                                        <!--end::Option-->
                                        <!--begin:Option-->
                                        <label class="d-flex flex-stack cursor-pointer mb-5">
                                            <!--begin:برچسب-->
                                            <span class="d-flex align-items-center me-2">
														<!--begin:Icon-->
														<span class="symbol symbol-50px me-6">
															<span class="symbol-label bg-light-success">
																<i class="ki-duotone ki-react fs-2x text-success">
																	<span class="path1"></span>
																	<span class="path2"></span>
																</i>
															</span>
														</span>
                                                <!--end:Icon-->
                                                <!--begin:Info-->
														<span class="d-flex flex-column">
															<span class="fw-bold fs-6">ReactJS</span>
															<span class="fs-7 text-muted">فریمویاک برنامه قوی و انعطاف پذیر</span>
														</span>
                                                <!--end:Info-->
													</span>
                                            <!--end:برچسب-->
                                            <!--begin:Input-->
                                            <span class="form-check form-check-custom form-check-solid">
														<input class="form-check-input" type="radio" name="framework" value="2" />
													</span>
                                            <!--end:Input-->
                                        </label>
                                        <!--end::Option-->
                                        <!--begin:Option-->
                                        <label class="d-flex flex-stack cursor-pointer mb-5">
                                            <!--begin:برچسب-->
                                            <span class="d-flex align-items-center me-2">
														<!--begin:Icon-->
														<span class="symbol symbol-50px me-6">
															<span class="symbol-label bg-light-danger">
																<i class="ki-duotone ki-angular fs-2x text-danger">
																	<span class="path1"></span>
																	<span class="path2"></span>
																	<span class="path3"></span>
																</i>
															</span>
														</span>
                                                <!--end:Icon-->
                                                <!--begin:Info-->
														<span class="d-flex flex-column">
															<span class="fw-bold fs-6">Angular</span>
															<span class="fs-7 text-muted">مدیریت قوی دیتاها</span>
														</span>
                                                <!--end:Info-->
													</span>
                                            <!--end:برچسب-->
                                            <!--begin:Input-->
                                            <span class="form-check form-check-custom form-check-solid">
														<input class="form-check-input" type="radio" name="framework" value="3" />
													</span>
                                            <!--end:Input-->
                                        </label>
                                        <!--end::Option-->
                                        <!--begin:Option-->
                                        <label class="d-flex flex-stack cursor-pointer">
                                            <!--begin:برچسب-->
                                            <span class="d-flex align-items-center me-2">
														<!--begin:Icon-->
														<span class="symbol symbol-50px me-6">
															<span class="symbol-label bg-light-primary">
																<i class="ki-duotone ki-vue fs-2x text-primary">
																	<span class="path1"></span>
																	<span class="path2"></span>
																</i>
															</span>
														</span>
                                                <!--end:Icon-->
                                                <!--begin:Info-->
														<span class="d-flex flex-column">
															<span class="fw-bold fs-6">Vue</span>
															<span class="fs-7 text-muted">چارچوب سبک و پاسخگو</span>
														</span>
                                                <!--end:Info-->
													</span>
                                            <!--end:برچسب-->
                                            <!--begin:Input-->
                                            <span class="form-check form-check-custom form-check-solid">
														<input class="form-check-input" type="radio" name="framework" value="4" />
													</span>
                                            <!--end:Input-->
                                        </label>
                                        <!--end::Option-->
                                    </div>
                                    <!--end::Input group-->
                                </div>
                            </div>
                            <!--end::مرحله 2-->
                            <!--begin::مرحله 3-->
                            <div data-kt-stepper-element="content">
                                <div class="w-100">
                                    <!--begin::Input group-->
                                    <div class="fv-row mb-10">
                                        <!--begin::Tags-->
                                        <label class="required fs-5 fw-semibold mb-2">نام دیتابیس</label>
                                        <!--end::Tags-->
                                        <!--begin::Input-->
                                        <input type="text" class="form-control form-control-lg form-control-solid" name="dbname" placeholder="" value="master_db" />
                                        <!--end::Input-->
                                    </div>
                                    <!--end::Input group-->
                                    <!--begin::Input group-->
                                    <div class="fv-row">
                                        <!--begin::Tags-->
                                        <label class="d-flex align-items-center fs-5 fw-semibold mb-4">
                                            <span class="required">انتخاب موتور دیتابیس</span>
                                            <span class="ms-1" data-bs-toggle="tooltip" title="انتخاب your app database engine">
														<i class="ki-duotone ki-information-5 text-gray-500 fs-6">
															<span class="path1"></span>
															<span class="path2"></span>
															<span class="path3"></span>
														</i>
													</span>
                                        </label>
                                        <!--end::Tags-->
                                        <!--begin:Option-->
                                        <label class="d-flex flex-stack cursor-pointer mb-5">
                                            <!--begin::Tags-->
                                            <span class="d-flex align-items-center me-2">
														<!--begin::Icon-->
														<span class="symbol symbol-50px me-6">
															<span class="symbol-label bg-light-success">
																<i class="ki-duotone ki-note text-success fs-2x">
																	<span class="path1"></span>
																	<span class="path2"></span>
																</i>
															</span>
														</span>
                                                <!--end::Icon-->
                                                <!--begin::Info-->
														<span class="d-flex flex-column">
															<span class="fw-bold fs-6">MYSQL</span>
															<span class="fs-7 text-muted">دیتابیس پایه</span>
														</span>
                                                <!--end::Info-->
													</span>
                                            <!--end::Tags-->
                                            <!--begin::Input-->
                                            <span class="form-check form-check-custom form-check-solid">
														<input class="form-check-input" type="radio" name="dbengine" checked="checked" value="1" />
													</span>
                                            <!--end::Input-->
                                        </label>
                                        <!--end::Option-->
                                        <!--begin:Option-->
                                        <label class="d-flex flex-stack cursor-pointer mb-5">
                                            <!--begin::Tags-->
                                            <span class="d-flex align-items-center me-2">
														<!--begin::Icon-->
														<span class="symbol symbol-50px me-6">
															<span class="symbol-label bg-light-danger">
																<i class="ki-duotone ki-google text-danger fs-2x">
																	<span class="path1"></span>
																	<span class="path2"></span>
																</i>
															</span>
														</span>
                                                <!--end::Icon-->
                                                <!--begin::Info-->
														<span class="d-flex flex-column">
															<span class="fw-bold fs-6">فایربیس</span>
															<span class="fs-7 text-muted">گوگل بیس</span>
														</span>
                                                <!--end::Info-->
													</span>
                                            <!--end::Tags-->
                                            <!--begin::Input-->
                                            <span class="form-check form-check-custom form-check-solid">
														<input class="form-check-input" type="radio" name="dbengine" value="2" />
													</span>
                                            <!--end::Input-->
                                        </label>
                                        <!--end::Option-->
                                        <!--begin:Option-->
                                        <label class="d-flex flex-stack cursor-pointer">
                                            <!--begin::Tags-->
                                            <span class="d-flex align-items-center me-2">
														<!--begin::Icon-->
														<span class="symbol symbol-50px me-6">
															<span class="symbol-label bg-light-warning">
																<i class="ki-duotone ki-microsoft text-warning fs-2x">
																	<span class="path1"></span>
																	<span class="path2"></span>
																	<span class="path3"></span>
																	<span class="path4"></span>
																</i>
															</span>
														</span>
                                                <!--end::Icon-->
                                                <!--begin::Info-->
														<span class="d-flex flex-column">
															<span class="fw-bold fs-6">DynamoDB</span>
															<span class="fs-7 text-muted">Microsoft Fast NoSQL دیتابیس</span>
														</span>
                                                <!--end::Info-->
													</span>
                                            <!--end::Tags-->
                                            <!--begin::Input-->
                                            <span class="form-check form-check-custom form-check-solid">
														<input class="form-check-input" type="radio" name="dbengine" value="3" />
													</span>
                                            <!--end::Input-->
                                        </label>
                                        <!--end::Option-->
                                    </div>
                                    <!--end::Input group-->
                                </div>
                            </div>
                            <!--end::مرحله 3-->
                            <!--begin::مرحله 4-->
                            <div data-kt-stepper-element="content">
                                <div class="w-100">
                                    <!--begin::Input group-->
                                    <div class="d-flex flex-column mb-7 fv-row">
                                        <!--begin::Tags-->
                                        <label class="d-flex align-items-center fs-6 fw-semibold form-label mb-2">
                                            <span class="required">نام بر روی کارت</span>
                                            <span class="ms-1" data-bs-toggle="tooltip" title="نام دارنده کارت را مشخص کنید">
														<i class="ki-duotone ki-information-5 text-gray-500 fs-6">
															<span class="path1"></span>
															<span class="path2"></span>
															<span class="path3"></span>
														</i>
													</span>
                                        </label>
                                        <!--end::Tags-->
                                        <input type="text" class="form-control form-control-solid" placeholder="" name="card_name" value="مکس اندره" />
                                    </div>
                                    <!--end::Input group-->
                                    <!--begin::Input group-->
                                    <div class="d-flex flex-column mb-7 fv-row">
                                        <!--begin::Tags-->
                                        <label class="required fs-6 fw-semibold form-label mb-2">شماره کارت</label>
                                        <!--end::Tags-->
                                        <!--begin::Input wrapper-->
                                        <div class="position-relative">
                                            <!--begin::Input-->
                                            <input type="text" class="form-control form-control-solid" placeholder="شماره کارت را وارد کنید" name="card_number" value="4111 1111 1111 1111" />
                                            <!--end::Input-->
                                            <!--begin::کارت logos-->
                                            <div class="position-absolute translate-middle-y top-50 end-0 me-5">
                                                <img src="theme/1/media/svg/card-logos/visa.svg" alt="" class="h-25px" />
                                                <img src="theme/1/media/svg/card-logos/mastercard.svg" alt="" class="h-25px" />
                                                <img src="theme/1/media/svg/card-logos/american-express.svg" alt="" class="h-25px" />
                                            </div>
                                            <!--end::کارت logos-->
                                        </div>
                                        <!--end::Input wrapper-->
                                    </div>
                                    <!--end::Input group-->
                                    <!--begin::Input group-->
                                    <div class="row mb-10">
                                        <!--begin::Col-->
                                        <div class="col-md-8 fv-row">
                                            <!--begin::Tags-->
                                            <label class="required fs-6 fw-semibold form-label mb-2">تاریخ انقضا</label>
                                            <!--end::Tags-->
                                            <!--begin::Row-->
                                            <div class="row fv-row">
                                                <!--begin::Col-->
                                                <div class="col-6">
                                                    <select name="card_expiry_month" class="form-select form-select-solid" data-control="select2" data-hide-search="true" data-placeholder="ماه">
                                                        <option></option>
                                                        <option value="1">1</option>
                                                        <option value="2">2</option>
                                                        <option value="3">3</option>
                                                        <option value="4">4</option>
                                                        <option value="5">5</option>
                                                        <option value="6">6</option>
                                                        <option value="7">7</option>
                                                        <option value="8">8</option>
                                                        <option value="9">9</option>
                                                        <option value="10">10</option>
                                                        <option value="11">11</option>
                                                        <option value="12">12</option>
                                                    </select>
                                                </div>
                                                <!--end::Col-->
                                                <!--begin::Col-->
                                                <div class="col-6">
                                                    <select name="card_expiry_year" class="form-select form-select-solid" data-control="select2" data-hide-search="true" data-placeholder="سال">
                                                        <option></option>
                                                        <option value="2024">2024</option>
                                                        <option value="2025">2025</option>
                                                        <option value="2026">2026</option>
                                                        <option value="2027">2027</option>
                                                        <option value="2028">2028</option>
                                                        <option value="2029">2029</option>
                                                        <option value="2030">2030</option>
                                                        <option value="2031">2031</option>
                                                        <option value="2032">2032</option>
                                                        <option value="2033">2033</option>
                                                        <option value="2034">2034</option>
                                                    </select>
                                                </div>
                                                <!--end::Col-->
                                            </div>
                                            <!--end::Row-->
                                        </div>
                                        <!--end::Col-->
                                        <!--begin::Col-->
                                        <div class="col-md-4 fv-row">
                                            <!--begin::Tags-->
                                            <label class="d-flex align-items-center fs-6 fw-semibold form-label mb-2">
                                                <span class="required">CVV</span>
                                                <span class="ms-1" data-bs-toggle="tooltip" title="Enter a card CVV code">
															<i class="ki-duotone ki-information-5 text-gray-500 fs-6">
																<span class="path1"></span>
																<span class="path2"></span>
																<span class="path3"></span>
															</i>
														</span>
                                            </label>
                                            <!--end::Tags-->
                                            <!--begin::Input wrapper-->
                                            <div class="position-relative">
                                                <!--begin::Input-->
                                                <input type="text" class="form-control form-control-solid" minlength="3" maxlength="4" placeholder="CVV" name="card_cvv" />
                                                <!--end::Input-->
                                                <!--begin::CVV icon-->
                                                <div class="position-absolute translate-middle-y top-50 end-0 me-3">
                                                    <i class="ki-duotone ki-credit-cart fs-2hx">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <!--end::CVV icon-->
                                            </div>
                                            <!--end::Input wrapper-->
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Input group-->
                                    <!--begin::Input group-->
                                    <div class="d-flex flex-stack">
                                        <!--begin::Tags-->
                                        <div class="me-5">
                                            <label class="fs-6 fw-semibold form-label">کارت را برای صورتحساب بیشتر ذخیره کنید؟</label>
                                            <div class="fs-7 fw-semibold text-muted">اگر به اطلاعات بیشتری نیاز دارید ، لطفا برنامه ریزی بودجه را بررسی کنید</div>
                                        </div>
                                        <!--end::Tags-->
                                        <!--begin::Switch-->
                                        <label class="form-check form-switch form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" value="1" checked="checked" />
                                            <span class="form-check-label fw-semibold text-muted">ذخیره کارت</span>
                                        </label>
                                        <!--end::Switch-->
                                    </div>
                                    <!--end::Input group-->
                                </div>
                            </div>
                            <!--end::مرحله 4-->
                            <!--begin::مرحله 5-->
                            <div data-kt-stepper-element="content">
                                <div class="w-100 text-center">
                                    <!--begin::Heading-->
                                    <h1 class="fw-bold text-gray-900 mb-3">ریلیز !</h1>
                                    <!--end::Heading-->
                                    <!--begin::توضیحات-->
                                    <div class="text-muted fw-semibold fs-3">برنامه خود را برای شروع پروژه خود ثبت کنید.</div>
                                    <!--end::توضیحات-->
                                    <!--begin::Illustration-->
                                    <div class="text-center px-4 py-15">
                                        <img src="theme/1/media/illustrations/sigma-1/9.png" alt="" class="mw-100 mh-300px" />
                                    </div>
                                    <!--end::Illustration-->
                                </div>
                            </div>
                            <!--end::مرحله 5-->
                            <!--begin::Actions-->
                            <div class="d-flex flex-stack pt-10">
                                <!--begin::Wrapper-->
                                <div class="me-2">
                                    <button type="button" class="btn btn-lg btn-light-primary me-3" data-kt-stepper-action="previous"><i class="ki-duotone ki-arrow-right fs-3 me-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>برگشت</button>
                                </div>
                                <!--end::Wrapper-->
                                <!--begin::Wrapper-->
                                <div>
                                    <button type="button" class="btn btn-lg btn-primary" data-kt-stepper-action="submit">
												<span class="indicator-label">ثبت
												<i class="ki-duotone ki-arrow-left fs-3 ms-2 me-0">
													<span class="path1"></span>
													<span class="path2"></span>
												</i></span>
                                        <span class="indicator-progress">لطفا صبر کنید...
												<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    </button>
                                    <button type="button" class="btn btn-lg btn-primary" data-kt-stepper-action="next">ادامه
                                        <i class="ki-duotone ki-arrow-left fs-3 ms-1 me-0">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i></button>
                                </div>
                                <!--end::Wrapper-->
                            </div>
                            <!--end::Actions-->
                        </form>
                        <!--end::form-->
                    </div>
                    <!--end::Content-->
                </div>
                <!--end::Stepper-->
            </div>
            <!--end::Modal body-->
        </div>
        <!--end::Modal content-->
    </div>
    <!--end::Modal dialog-->
</div>
<!--end::Modal - create app-->
<!--begin::Modal - create campaign-->
<div class="modal fade" id="kt_modal_create_campaign" tabindex="-1" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-fullscreen p-9">
        <!--begin::Modal content-->
        <div class="modal-content modal-rounded">
            <!--begin::Modal header-->
            <div class="modal-header py-7 d-flex justify-content-between">
                <!--begin::Modal title-->
                <h2>ساختن کمپین</h2>
                <!--end::Modal title-->
                <!--begin::Close-->
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
                <!--end::Close-->
            </div>
            <!--begin::Modal header-->
            <!--begin::Modal body-->
            <div class="modal-body scroll-y m-5">
                <!--begin::Stepper-->
                <div class="stepper stepper-links d-flex flex-column" id="kt_modal_create_campaign_stepper">
                    <!--begin::Nav-->
                    <div class="stepper-nav justify-content-center py-2">
                        <!--begin::مرحله 1-->
                        <div class="stepper-item me-5 me-md-15 current" data-kt-stepper-element="nav">
                            <h3 class="stepper-title">جزییات کمپین</h3>
                        </div>
                        <!--end::مرحله 1-->
                        <!--begin::مرحله 2-->
                        <div class="stepper-item me-5 me-md-15" data-kt-stepper-element="nav">
                            <h3 class="stepper-title">آپلودهای خلاقانه</h3>
                        </div>
                        <!--end::مرحله 2-->
                        <!--begin::مرحله 3-->
                        <div class="stepper-item me-5 me-md-15" data-kt-stepper-element="nav">
                            <h3 class="stepper-title">مخاطبان</h3>
                        </div>
                        <!--end::مرحله 3-->
                        <!--begin::مرحله 4-->
                        <div class="stepper-item me-5 me-md-15" data-kt-stepper-element="nav">
                            <h3 class="stepper-title">برآورد بودجه</h3>
                        </div>
                        <!--end::مرحله 4-->
                        <!--begin::مرحله 5-->
                        <div class="stepper-item" data-kt-stepper-element="nav">
                            <h3 class="stepper-title">کامل شد</h3>
                        </div>
                        <!--end::مرحله 5-->
                    </div>
                    <!--end::Nav-->
                    <!--begin::form-->
                    <form class="mx-auto w-100 mw-600px pt-15 pb-10" novalidate="novalidate" id="kt_modal_create_campaign_stepper_form">
                        <!--begin::مرحله 1-->
                        <div class="current" data-kt-stepper-element="content">
                            <!--begin::Wrapper-->
                            <div class="w-100">
                                <!--begin::Heading-->
                                <div class="pb-10 pb-lg-15">
                                    <!--begin::Title-->
                                    <h2 class="fw-bold d-flex align-items-center text-gray-900">راه اندازی جزییات کمپین
                                        <span class="ms-1" data-bs-toggle="tooltip" title="Campaign name will be used as reference within your campaign گزارشات">
												<i class="ki-duotone ki-information-5 text-gray-500 fs-6">
													<span class="path1"></span>
													<span class="path2"></span>
													<span class="path3"></span>
												</i>
											</span></h2>
                                    <!--end::Title-->
                                    <!--begin::Notice-->
                                    <div class="text-muted fw-semibold fs-6">اگر به اطلاعات بیشتری نیاز دارید ، لطفاً این مورد را بررسی کنید
                                        <a href="#" class="link-primary fw-bold">صفحه راهنما</a>.</div>
                                    <!--end::Notice-->
                                </div>
                                <!--end::Heading-->
                                <!--begin::Input group-->
                                <div class="mb-10 fv-row">
                                    <!--begin::Tags-->
                                    <label class="required form-label mb-3">نام کمپین</label>
                                    <!--end::Tags-->
                                    <!--begin::Input-->
                                    <input type="text" class="form-control form-control-lg form-control-solid" name="campaign_name" placeholder="" value="" />
                                    <!--end::Input-->
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div class="fv-row mb-10">
                                    <!--begin::Tags-->
                                    <label class="d-block fw-semibold fs-6 mb-5">
                                        <span class="required">لوگو شرکت</span>
                                        <span class="ms-1" data-bs-toggle="tooltip" title="E.g. انتخاب a logo to represent the company that's running the campaign.">
													<i class="ki-duotone ki-information-5 text-gray-500 fs-6">
														<span class="path1"></span>
														<span class="path2"></span>
														<span class="path3"></span>
													</i>
												</span>
                                    </label>
                                    <!--end::Tags-->
                                    <!--begin::Image input placeholder-->
                                    <style>.image-input-placeholder { background-image: url('{{asset('theme/1/media/svg/files/blank-image.svg')}}'); } [data-bs-theme="dark"] .image-input-placeholder { background-image: url('{{asset('theme/1/media/svg/files/blank-image-dark.svg')}}'); }</style>
                                    <!--end::Image input placeholder-->
                                    <!--begin::Image input-->
                                    <div class="image-input image-input-empty image-input-outline image-input-placeholder" data-kt-image-input="true">
                                        <!--begin::نمایش existing avatar-->
                                        <div class="image-input-wrapper w-125px h-125px"></div>
                                        <!--end::نمایش existing avatar-->
                                        <!--begin::Tags-->
                                        <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="تعویض آواتار">
                                            <i class="ki-duotone ki-pencil fs-7">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            <!--begin::Inputs-->
                                            <input type="file" name="avatar" accept=".png, .jpg, .jpeg" />
                                            <input type="hidden" name="avatar_remove" />
                                            <!--end::Inputs-->
                                        </label>
                                        <!--end::Tags-->
                                        <!--begin::انصراف-->
                                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="انصراف avatar">
													<i class="ki-duotone ki-cross fs-2">
														<span class="path1"></span>
														<span class="path2"></span>
													</i>
												</span>
                                        <!--end::انصراف-->
                                        <!--begin::حذف-->
                                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="حذف آواتار">
													<i class="ki-duotone ki-cross fs-2">
														<span class="path1"></span>
														<span class="path2"></span>
													</i>
												</span>
                                        <!--end::حذف-->
                                    </div>
                                    <!--end::Image input-->
                                    <!--begin::Hint-->
                                    <div class="form-text">همه بدهکار هستیم file types: png, jpg, jpeg.</div>
                                    <!--end::Hint-->
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div class="mb-10">
                                    <!--begin::Tags-->
                                    <label class="required fw-semibold fs-6 mb-5">هدف کمپین</label>
                                    <!--end::Tags-->
                                    <!--begin::سطح دسترسی-->
                                    <!--begin::Input row-->
                                    <div class="d-flex fv-row">
                                        <!--begin::رادیو-->
                                        <div class="form-check form-check-custom form-check-solid">
                                            <!--begin::Input-->
                                            <input class="form-check-input me-3" name="user_role" type="radio" value="0" id="kt_modal_update_role_option_0" checked='checked' />
                                            <!--end::Input-->
                                            <!--begin::Tags-->
                                            <label class="form-check-label" for="kt_modal_update_role_option_0">
                                                <div class="fw-bold text-gray-800">بازدیدکنندگان بیشتری داشته باشید</div>
                                                <div class="text-gray-600">ترافیک نمایش روی پلت فرم را افزایش دهید</div>
                                            </label>
                                            <!--end::Tags-->
                                        </div>
                                        <!--end::رادیو-->
                                    </div>
                                    <!--end::Input row-->
                                    <div class='separator separator-dashed my-5'></div>
                                    <!--begin::Input row-->
                                    <div class="d-flex fv-row">
                                        <!--begin::رادیو-->
                                        <div class="form-check form-check-custom form-check-solid">
                                            <!--begin::Input-->
                                            <input class="form-check-input me-3" name="user_role" type="radio" value="1" id="kt_modal_update_role_option_1" />
                                            <!--end::Input-->
                                            <!--begin::Tags-->
                                            <label class="form-check-label" for="kt_modal_update_role_option_1">
                                                <div class="fw-bold text-gray-800">در چت پیام های بیشتری دریافت کنید</div>
                                                <div class="text-gray-600">تعامل و ارتباطات اجتماعی را افزایش دهید</div>
                                            </label>
                                            <!--end::Tags-->
                                        </div>
                                        <!--end::رادیو-->
                                    </div>
                                    <!--end::Input row-->
                                    <div class='separator separator-dashed my-5'></div>
                                    <!--begin::Input row-->
                                    <div class="d-flex fv-row">
                                        <!--begin::رادیو-->
                                        <div class="form-check form-check-custom form-check-solid">
                                            <!--begin::Input-->
                                            <input class="form-check-input me-3" name="user_role" type="radio" value="2" id="kt_modal_update_role_option_2" />
                                            <!--end::Input-->
                                            <!--begin::Tags-->
                                            <label class="form-check-label" for="kt_modal_update_role_option_2">
                                                <div class="fw-bold text-gray-800">تماس های بیشتری دریافت کنید</div>
                                                <div class="text-gray-600">افزایش بازخورد مخابراتی برای ارائه اطلاعات دقیق و دقیق</div>
                                            </label>
                                            <!--end::Tags-->
                                        </div>
                                        <!--end::رادیو-->
                                    </div>
                                    <!--end::Input row-->
                                    <div class='separator separator-dashed my-5'></div>
                                    <!--begin::Input row-->
                                    <div class="d-flex fv-row">
                                        <!--begin::رادیو-->
                                        <div class="form-check form-check-custom form-check-solid">
                                            <!--begin::Input-->
                                            <input class="form-check-input me-3" name="user_role" type="radio" value="3" id="kt_modal_update_role_option_3" />
                                            <!--end::Input-->
                                            <!--begin::Tags-->
                                            <label class="form-check-label" for="kt_modal_update_role_option_3">
                                                <div class="fw-bold text-gray-800">برام لایک بگیر</div>
                                                <div class="text-gray-600">تعامل مثبت در پلتفرم های رسانه های اجتماعی را افزایش دهید</div>
                                            </label>
                                            <!--end::Tags-->
                                        </div>
                                        <!--end::رادیو-->
                                    </div>
                                    <!--end::Input row-->
                                    <div class='separator separator-dashed my-5'></div>
                                    <!--begin::Input row-->
                                    <div class="d-flex fv-row">
                                        <!--begin::رادیو-->
                                        <div class="form-check form-check-custom form-check-solid">
                                            <!--begin::Input-->
                                            <input class="form-check-input me-3" name="user_role" type="radio" value="4" id="kt_modal_update_role_option_4" />
                                            <!--end::Input-->
                                            <!--begin::Tags-->
                                            <label class="form-check-label" for="kt_modal_update_role_option_4">
                                                <div class="fw-bold text-gray-800">تولید سازنده</div>
                                                <div class="text-gray-600">اطلاعات تماس مشتریان بالقوه را جمع آوری کنید</div>
                                            </label>
                                            <!--end::Tags-->
                                        </div>
                                        <!--end::رادیو-->
                                    </div>
                                    <!--end::Input row-->
                                    <!--end::سطح دسترسی-->
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Wrapper-->
                        </div>
                        <!--end::مرحله 1-->
                        <!--begin::مرحله 2-->
                        <div data-kt-stepper-element="content">
                            <!--begin::Wrapper-->
                            <div class="w-100">
                                <!--begin::Heading-->
                                <div class="pb-10 pb-lg-12">
                                    <!--begin::Title-->
                                    <h1 class="fw-bold text-gray-900">آپلود فایل</h1>
                                    <!--end::Title-->
                                    <!--begin::توضیحات-->
                                    <div class="text-muted fw-semibold fs-4">اگر به اطلاعات لازم دارید ، لطفاً بررسی کنید
                                        <a href="#" class="link-primary">دستورالعمل های کمپین</a></div>
                                    <!--end::توضیحات-->
                                </div>
                                <!--end::Heading-->
                                <!--begin::Input group-->
                                <div class="fv-row mb-10">
                                    <!--begin::Dropzone-->
                                    <div class="dropzone" id="kt_modal_create_campaign_files_upload">
                                        <!--begin::Message-->
                                        <div class="dz-message needsclick">
                                            <!--begin::Icon-->
                                            <i class="ki-duotone ki-file-up fs-3hx text-primary">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            <!--end::Icon-->
                                            <!--begin::Info-->
                                            <div class="ms-4">
                                                <h3 class="dfs-3 fw-bold text-gray-900 mb-1">فایل های کمپین را اینجا رها کنید یا برای آپلود کلیک کنید.</h3>
                                                <span class="fw-semibold fs-4 text-muted">اپلود فایل بیش از 10 تا</span>
                                            </div>
                                            <!--end::Info-->
                                        </div>
                                    </div>
                                    <!--end::Dropzone-->
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div class="mb-10">
                                    <!--begin::Tags-->
                                    <label class="fs-6 fw-semibold mb-2">آپلود فایل</label>
                                    <!--End::Tags-->
                                    <!--begin::فایل ها-->
                                    <div class="mh-300px scroll-y me-n7 pe-7">
                                        <!--begin::File-->
                                        <div class="d-flex flex-stack py-4 border border-top-0 border-left-0 border-right-0 border-dashed">
                                            <div class="d-flex align-items-center">
                                                <!--begin::Avatar-->
                                                <div class="symbol symbol-35px">
                                                    <img src="theme/1/media/svg/files/pdf.svg" alt="icon" />
                                                </div>
                                                <!--end::Avatar-->
                                                <!--begin::Details-->
                                                <div class="ms-6">
                                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">مشخصات فنی</a>
                                                    <div class="fw-semibold text-muted">230kb</div>
                                                </div>
                                                <!--end::Details-->
                                            </div>
                                            <!--begin::Menu-->
                                            <div class="min-w-100px">
                                                <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true" data-placeholder="">
                                                    <option></option>
                                                    <option value="1">حذف</option>
                                                    <option value="2">تغییر</option>
                                                    <option value="3">انتخاب</option>
                                                </select>
                                            </div>
                                            <!--end::Menu-->
                                        </div>
                                        <!--end::File-->
                                        <!--begin::File-->
                                        <div class="d-flex flex-stack py-4 border border-top-0 border-left-0 border-right-0 border-dashed">
                                            <div class="d-flex align-items-center">
                                                <!--begin::Avatar-->
                                                <div class="symbol symbol-35px">
                                                    <img src="theme/1/media/svg/files/tif.svg" alt="icon" />
                                                </div>
                                                <!--end::Avatar-->
                                                <!--begin::Details-->
                                                <div class="ms-6">
                                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">پوستر خلاقانه کمپین</a>
                                                    <div class="fw-semibold text-muted">2.4mb</div>
                                                </div>
                                                <!--end::Details-->
                                            </div>
                                            <!--begin::Menu-->
                                            <div class="min-w-100px">
                                                <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true" data-placeholder="">
                                                    <option></option>
                                                    <option value="1">حذف</option>
                                                    <option value="2">تغییر</option>
                                                    <option value="3">انتخاب</option>
                                                </select>
                                            </div>
                                            <!--end::Menu-->
                                        </div>
                                        <!--end::File-->
                                        <!--begin::File-->
                                        <div class="d-flex flex-stack py-4 border border-top-0 border-left-0 border-right-0 border-dashed">
                                            <div class="d-flex align-items-center">
                                                <!--begin::Avatar-->
                                                <div class="symbol symbol-35px">
                                                    <img src="theme/1/media/svg/files/folder-document.svg" alt="icon" />
                                                </div>
                                                <!--end::Avatar-->
                                                <!--begin::Details-->
                                                <div class="ms-6">
                                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">منبع صفحه کمپین</a>
                                                    <div class="fw-semibold text-muted">1.12mb</div>
                                                </div>
                                                <!--end::Details-->
                                            </div>
                                            <!--begin::Menu-->
                                            <div class="min-w-100px">
                                                <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true" data-placeholder="">
                                                    <option></option>
                                                    <option value="1">حذف</option>
                                                    <option value="2">تغییر</option>
                                                    <option value="3">انتخاب</option>
                                                </select>
                                            </div>
                                            <!--end::Menu-->
                                        </div>
                                        <!--end::File-->
                                        <!--begin::File-->
                                        <div class="d-flex flex-stack py-4 border border-top-0 border-left-0 border-right-0 border-dashed">
                                            <div class="d-flex align-items-center">
                                                <!--begin::Avatar-->
                                                <div class="symbol symbol-35px">
                                                    <img src="theme/1/media/svg/files/css.svg" alt="icon" />
                                                </div>
                                                <!--end::Avatar-->
                                                <!--begin::Details-->
                                                <div class="ms-6">
                                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">طراحی صفحه</a>
                                                    <div class="fw-semibold text-muted">85kb</div>
                                                </div>
                                                <!--end::Details-->
                                            </div>
                                            <!--begin::Menu-->
                                            <div class="min-w-100px">
                                                <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true" data-placeholder="">
                                                    <option></option>
                                                    <option value="1">حذف</option>
                                                    <option value="2">تغییر</option>
                                                    <option value="3">انتخاب</option>
                                                </select>
                                            </div>
                                            <!--end::Menu-->
                                        </div>
                                        <!--end::File-->
                                        <!--begin::File-->
                                        <div class="d-flex flex-stack py-4 border border-top-0 border-left-0 border-right-0 border-dashed">
                                            <div class="d-flex align-items-center">
                                                <!--begin::Avatar-->
                                                <div class="symbol symbol-35px">
                                                    <img src="theme/1/media/svg/files/ai.svg" alt="icon" />
                                                </div>
                                                <!--end::Avatar-->
                                                <!--begin::Details-->
                                                <div class="ms-6">
                                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">طراحی</a>
                                                    <div class="fw-semibold text-muted">48mb</div>
                                                </div>
                                                <!--end::Details-->
                                            </div>
                                            <!--begin::Menu-->
                                            <div class="min-w-100px">
                                                <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true" data-placeholder="">
                                                    <option></option>
                                                    <option value="1">حذف</option>
                                                    <option value="2">تغییر</option>
                                                    <option value="3">انتخاب</option>
                                                </select>
                                            </div>
                                            <!--end::Menu-->
                                        </div>
                                        <!--end::File-->
                                        <!--begin::File-->
                                        <div class="d-flex flex-stack py-4">
                                            <div class="d-flex align-items-center">
                                                <!--begin::Avatar-->
                                                <div class="symbol symbol-35px">
                                                    <img src="theme/1/media/svg/files/doc.svg" alt="icon" />
                                                </div>
                                                <!--end::Avatar-->
                                                <!--begin::Details-->
                                                <div class="ms-6">
                                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">پلن کمپین</a>
                                                    <div class="fw-semibold text-muted">27kb</div>
                                                </div>
                                                <!--end::Details-->
                                            </div>
                                            <!--begin::Menu-->
                                            <div class="min-w-100px">
                                                <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true" data-placeholder="">
                                                    <option></option>
                                                    <option value="1">حذف</option>
                                                    <option value="2">تغییر</option>
                                                    <option value="3">انتخاب</option>
                                                </select>
                                            </div>
                                            <!--end::Menu-->
                                        </div>
                                        <!--end::File-->
                                    </div>
                                    <!--end::فایل ها-->
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Wrapper-->
                        </div>
                        <!--end::مرحله 2-->
                        <!--begin::مرحله 3-->
                        <div data-kt-stepper-element="content">
                            <!--begin::Wrapper-->
                            <div class="w-100">
                                <!--begin::Heading-->
                                <div class="pb-10 pb-lg-12">
                                    <!--begin::Title-->
                                    <h1 class="fw-bold text-gray-900">پیکربندی</h1>
                                    <!--end::Title-->
                                    <!--begin::توضیحات-->
                                    <div class="text-muted fw-semibold fs-4">اگر به اطلاعات لازم دارید ، لطفاً بررسی کنید
                                        <a href="#" class="link-primary">دستورالعمل های کمپین</a></div>
                                    <!--end::توضیحات-->
                                </div>
                                <!--end::Heading-->
                                <!--begin::Input group-->
                                <div class="fv-row mb-10">
                                    <!--begin::Tags-->
                                    <label class="fs-6 fw-semibold mb-2">جنسیت
                                        <span class="ms-1" data-bs-toggle="tooltip" title="مشاهده your ads to either men یا women, یا select 'همه' for both">
												<i class="ki-duotone ki-information-5 text-gray-500 fs-6">
													<span class="path1"></span>
													<span class="path2"></span>
													<span class="path3"></span>
												</i>
											</span></label>
                                    <!--End::Tags-->
                                    <!--begin::Row-->
                                    <div class="row g-9" data-kt-buttons="true" data-kt-buttons-target="[data-kt-button='true']">
                                        <!--begin::Col-->
                                        <div class="col">
                                            <!--begin::Option-->
                                            <label class="btn btn-outline btn-outline-dashed btn-active-light-primary active d-flex text-start p-6" data-kt-button="true">
                                                <!--begin::رادیو-->
                                                <span class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
															<input class="form-check-input" type="radio" name="campaign_gender" value="1" checked="checked" />
														</span>
                                                <!--end::رادیو-->
                                                <!--begin::Info-->
                                                <span class="ms-5">
															<span class="fs-4 fw-bold text-gray-800 d-block">همه</span>
														</span>
                                                <!--end::Info-->
                                            </label>
                                            <!--end::Option-->
                                        </div>
                                        <!--end::Col-->
                                        <!--begin::Col-->
                                        <div class="col">
                                            <!--begin::Option-->
                                            <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-6" data-kt-button="true">
                                                <!--begin::رادیو-->
                                                <span class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
															<input class="form-check-input" type="radio" name="campaign_gender" value="2" />
														</span>
                                                <!--end::رادیو-->
                                                <!--begin::Info-->
                                                <span class="ms-5">
															<span class="fs-4 fw-bold text-gray-800 d-block">مرد</span>
														</span>
                                                <!--end::Info-->
                                            </label>
                                            <!--end::Option-->
                                        </div>
                                        <!--end::Col-->
                                        <!--begin::Col-->
                                        <div class="col">
                                            <!--begin::Option-->
                                            <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-6" data-kt-button="true">
                                                <!--begin::رادیو-->
                                                <span class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
															<input class="form-check-input" type="radio" name="campaign_gender" value="3" />
														</span>
                                                <!--end::رادیو-->
                                                <!--begin::Info-->
                                                <span class="ms-5">
															<span class="fs-4 fw-bold text-gray-800 d-block">زن</span>
														</span>
                                                <!--end::Info-->
                                            </label>
                                            <!--end::Option-->
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div class="fv-row mb-10">
                                    <!--begin::Tags-->
                                    <label class="fs-6 fw-semibold mb-2">سن
                                        <span class="ms-1" data-bs-toggle="tooltip" title="انتخاب the minimum and maximum age of the people who will find your ad relevant.">
												<i class="ki-duotone ki-information-5 text-gray-500 fs-6">
													<span class="path1"></span>
													<span class="path2"></span>
													<span class="path3"></span>
												</i>
											</span></label>
                                    <!--End::Tags-->
                                    <!--begin::Slider-->
                                    <div class="d-flex flex-stack">
                                        <div id="kt_modal_create_campaign_age_min" class="fs-7 fw-semibold text-muted"></div>
                                        <div id="kt_modal_create_campaign_age_slider" class="noUi-sm w-100 ms-5 me-8"></div>
                                        <div id="kt_modal_create_campaign_age_max" class="fs-7 fw-semibold text-muted"></div>
                                    </div>
                                    <!--end::Slider-->
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div class="fv-row mb-10">
                                    <!--begin::Tags-->
                                    <label class="fs-6 fw-semibold mb-2">مکان
                                        <span class="ms-1" data-bs-toggle="tooltip" title="Enter one یا more location points for more specific targeting.">
												<i class="ki-duotone ki-information-5 text-gray-500 fs-6">
													<span class="path1"></span>
													<span class="path2"></span>
													<span class="path3"></span>
												</i>
											</span></label>
                                    <!--End::Tags-->
                                    <!--begin::Tagify-->
                                    <input class="form-control d-flex align-items-center" value="" id="kt_modal_create_campaign_location" data-kt-flags-path="theme/1/media/flags/" />
                                    <!--end::Tagify-->
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Wrapper-->
                        </div>
                        <!--end::مرحله 3-->
                        <!--begin::مرحله 4-->
                        <div data-kt-stepper-element="content">
                            <!--begin::Wrapper-->
                            <div class="w-100">
                                <!--begin::Heading-->
                                <div class="pb-10 pb-lg-12">
                                    <!--begin::Title-->
                                    <h1 class="fw-bold text-gray-900">برآورد بودجه</h1>
                                    <!--end::Title-->
                                    <!--begin::توضیحات-->
                                    <div class="text-muted fw-semibold fs-4">اگر به اطلاعات لازم دارید ، لطفاً بررسی کنید
                                        <a href="#" class="link-primary">دستورالعمل های کمپین</a></div>
                                    <!--end::توضیحات-->
                                </div>
                                <!--end::Heading-->
                                <!--begin::Input group-->
                                <div class="fv-row mb-10">
                                    <!--begin::Tags-->
                                    <label class="fs-6 fw-semibold mb-2">مدت زمان کمپین
                                        <span class="ms-1" data-bs-toggle="tooltip" title="Choose how long you want your ad to run for">
												<i class="ki-duotone ki-information-5 text-gray-500 fs-6">
													<span class="path1"></span>
													<span class="path2"></span>
													<span class="path3"></span>
												</i>
											</span></label>
                                    <!--end::Tags-->
                                    <!--begin::Duration option-->
                                    <div class="d-flex gap-9 mb-7">
                                        <!--begin::Button-->
                                        <button type="button" class="btn btn-outline btn-outline-dashed btn-active-light-primary active" id="kt_modal_create_campaign_duration_all">مدت زمان مستمر
                                            <br />
                                            <span class="fs-7">تبلیغ شما به طور مداوم برای یک بودجه روزانه اجرا می شود.</span></button>
                                        <!--end::Button-->
                                        <!--begin::Button-->
                                        <button type="button" class="btn btn-outline btn-outline-dashed btn-active-light-primary btn-outline-default" id="kt_modal_create_campaign_duration_fixed">مدت زمان ثابت
                                            <br />
                                            <span class="fs-7">آگهی شما فقط در تاریخ های مشخص شده اجرا خواهد شد.</span></button>
                                        <!--end::Button-->
                                    </div>
                                    <!--end::Duration option-->
                                    <!--begin::تاریخpicker-->
                                    <input class="form-control form-control-solid d-none" placeholder="انتخاب تاریخ & time" id="kt_modal_create_campaign_datepicker" />
                                    <!--end::تاریخpicker-->
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div class="fv-row mb-10">
                                    <!--begin::Tags-->
                                    <label class="fs-6 fw-semibold mb-2">بودجه روزانه
                                        <span class="ms-1" data-bs-toggle="tooltip" title="Choose the budget allocated for each day. Higher budget will generate better results">
												<i class="ki-duotone ki-information-5 text-gray-500 fs-6">
													<span class="path1"></span>
													<span class="path2"></span>
													<span class="path3"></span>
												</i>
											</span></label>
                                    <!--end::Tags-->
                                    <!--begin::Slider-->
                                    <div class="d-flex flex-column text-center">
                                        <div class="d-flex align-items-start justify-content-center mb-7">
                                            <span class="fw-bold fs-4 mt-1 me-2">$</span>
                                            <span class="fw-bold fs-3x" id="kt_modal_create_campaign_budget_label"></span>
                                            <span class="fw-bold fs-3x">.00</span>
                                        </div>
                                        <div id="kt_modal_create_campaign_budget_slider" class="noUi-sm"></div>
                                    </div>
                                    <!--end::Slider-->
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Wrapper-->
                        </div>
                        <!--end::مرحله 4-->
                        <!--begin::مرحله 5-->
                        <div data-kt-stepper-element="content">
                            <!--begin::Wrapper-->
                            <div class="w-100">
                                <!--begin::Heading-->
                                <div class="pb-12 text-center">
                                    <!--begin::Title-->
                                    <h1 class="fw-bold text-gray-900">کمپین زمان ایجاد!</h1>
                                    <!--end::Title-->
                                    <!--begin::توضیحات-->
                                    <div class="fw-semibold text-muted fs-4">شما یک ایمیل با خلاصه کمپین تازه ایجاد شده خود دریافت خواهید کرد!</div>
                                    <!--end::توضیحات-->
                                </div>
                                <!--end::Heading-->
                                <!--begin::Actions-->
                                <div class="d-flex flex-center pb-20">
                                    <button id="kt_modal_create_campaign_create_new" type="button" class="btn btn-lg btn-light me-3" data-kt-element="complete-start">کمپین ساختن جدید</button>
                                    <a href="" class="btn btn-lg btn-primary" data-bs-toggle="tooltip" title="بزودی">کمپین نمایش</a>
                                </div>
                                <!--end::Actions-->
                                <!--begin::Illustration-->
                                <div class="text-center px-4">
                                    <img src="theme/1/media/illustrations/sigma-1/9.png" alt="" class="mww-100 mh-350px" />
                                </div>
                                <!--end::Illustration-->
                            </div>
                        </div>
                        <!--end::مرحله 5-->
                        <!--begin::Actions-->
                        <div class="d-flex flex-stack pt-10">
                            <!--begin::Wrapper-->
                            <div class="me-2">
                                <button type="button" class="btn btn-lg btn-light-primary me-3" data-kt-stepper-action="previous" data-kt-stepper-state="hide-on-last-step">
                                    <i class="ki-duotone ki-arrow-left fs-3 me-1">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>برگشت</button>
                            </div>
                            <!--end::Wrapper-->
                            <!--begin::Wrapper-->
                            <div>
                                <button type="button" class="btn btn-lg btn-primary" data-kt-stepper-action="submit">
											<span class="indicator-label">ثبت
											<i class="ki-duotone ki-arrow-left fs-3 ms-2 me-0">
												<span class="path1"></span>
												<span class="path2"></span>
											</i></span>
                                    <span class="indicator-progress">لطفا صبر کنید...
											<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                                <button type="button" class="btn btn-lg btn-primary" data-kt-stepper-action="next">ادامه
                                    <i class="ki-duotone ki-arrow-left fs-3 ms-1 me-0">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i></button>
                            </div>
                            <!--end::Wrapper-->
                        </div>
                        <!--end::Actions-->
                    </form>
                    <!--end::form-->
                </div>
                <!--end::Stepper-->
            </div>
            <!--begin::Modal body-->
        </div>
    </div>
</div>
<!--end::Modal - create campaign-->
<!--end::Modals-->

<!--begin::Javascript-->
<script>
    var hostUrl = "theme/1/";
</script>
<!--begin::Global Javascript Bundle(mandatory for all pages)-->
<script src="{{asset('theme/1/plugins/global/plugins.bundle.js')}}"></script>
<script src="{{asset('theme/1/js/scripts.bundle.js')}}"></script>
<!--end::Global Javascript Bundle-->
<!--begin::Vendors Javascript(used for this page only)-->
<script src="{{asset('theme/1/plugins/custom/fullcalendar/fullcalendar.bundle.js')}}"></script>
<script src="{{asset('theme/1/plugins/custom/amcharts/index.js')}}"></script>
<script src="{{asset('theme/1/plugins/custom/amcharts/xy.js')}}"></script>
<script src="{{asset('theme/1/plugins/custom/amcharts/percent.js')}}"></script>
<script src="{{asset('theme/1/plugins/custom/amcharts/radar.js')}}"></script>
<script src="{{asset('theme/1/plugins/custom/amcharts/Animated.js')}}"></script>
<script src="{{asset('theme/1/plugins/custom/amcharts/map.js')}}"></script>
<script src="{{asset('theme/1/plugins/custom/amcharts/worldLow.js')}}"></script>
<script src="{{asset('theme/1/plugins/custom/amcharts/continentsLow.js')}}"></script>
<script src="{{asset('theme/1/plugins/custom/amcharts/usaLow.js')}}"></script>
<script src="{{asset('theme/1/plugins/custom/amcharts/worldTimeZonesLow.js')}}"></script>
<script src="{{asset('theme/1/plugins/custom/amcharts/worldTimeZoneAreasLow.js')}}"></script>
<script src="{{asset('theme/1/plugins/custom/datatables/datatables.bundle.js')}}"></script>
<!--end::Vendors Javascript-->
<!--begin::custom Javascript(used for this page only)-->
<script src="{{asset('theme/1/js/widgets.bundle.js')}}"></script>
<script src="{{asset('theme/1/js/custom/widgets.js')}}"></script>
<script src="{{asset('theme/1/js/custom/apps/chat/chat.js')}}"></script>
<script src="{{asset('theme/1/js/custom/utilities/modals/upgrade-plan.js')}}"></script>
<script src="{{asset('theme/1/js/custom/utilities/modals/create-app.js')}}"></script>
<script src="{{asset('theme/1/js/custom/utilities/modals/create-campaign.js')}}"></script>
<script src="{{asset('theme/1/js/custom/utilities/modals/users-search.js')}}"></script>
<!--end::custom Javascript-->
<!--end::Javascript-->

@livewireScripts
</body>
</html>
