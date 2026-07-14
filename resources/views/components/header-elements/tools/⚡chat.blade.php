<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="d-flex align-items-center ms-1 ms-lg-3">
    <!--begin::Menu wrapper-->
    <div class="position-relative btn btn-icon btn-active-light-primary btn-custom w-30px h-30px w-md-40px h-md-40px" id="kt_drawer_chat_toggle">
        <i class="ki-duotone ki-message-text-2 fs-1">
            <span class="path1"></span>
            <span class="path2"></span>
            <span class="path3"></span>
        </i>
        <span class="bullet bullet-dot bg-success h-6px w-6px position-absolute translate-middle top-0 start-50 animation-blink"></span>
    </div>
    <!--end::Menu wrapper-->
    <!--begin::chat drawer-->
    <div id="kt_drawer_chat" class="bg-body" data-kt-drawer="true" data-kt-drawer-name="chat" data-kt-drawer-activate="true" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'300px', 'md': '500px'}" data-kt-drawer-direction="end" data-kt-drawer-toggle="#kt_drawer_chat_toggle" data-kt-drawer-close="#kt_drawer_chat_close">
        <!--begin::Messenger-->
        <div class="card w-100 border-0 rounded-0" id="kt_drawer_chat_messenger">
            <!--begin::کارت header-->
            <div class="card-header pe-5" id="kt_drawer_chat_messenger_header">
                <!--begin::Title-->
                <div class="card-title">
                    <!--begin::user-->
                    <div class="d-flex justify-content-center flex-column me-3">
                        <a href="#" class="fs-4 fw-bold text-gray-900 text-hover-primary me-1 mb-2 lh-1">رضا علی ابادی</a>
                        <!--begin::Info-->
                        <div class="mb-0 lh-1">
                            <span class="badge badge-success badge-circle w-10px h-10px me-1"></span>
                            <span class="fs-7 fw-semibold text-muted">فعال</span>
                        </div>
                        <!--end::Info-->
                    </div>
                    <!--end::user-->
                </div>
                <!--end::Title-->
                <!--begin::کارت toolbar-->
                <div class="card-toolbar">
                    <!--begin::Menu-->
                    <div class="me-0">
                        <button class="btn btn-sm btn-icon btn-active-color-primary" data-kt-menu-trigger="click" data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'bottom-end' : 'bottom-start' }}">
                            <i class="ki-duotone ki-dots-square fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                        </button>
                        <!--begin::Menu 3-->
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-3" data-kt-menu="true">
                            <!--begin::Heading-->
                            <div class="menu-item px-3">
                                <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">مخاطبین</div>
                            </div>
                            <!--end::Heading-->
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-bs-toggle="modal" data-bs-target="#kt_modal_users_search">افزودن مخاطب</a>
                            </div>
                            <!--end::Menu item-->
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link flex-stack px-3" data-bs-toggle="modal" data-bs-target="#kt_modal_invite_friends">دعوت مخاطبین
                                    <span class="ms-2" data-bs-toggle="tooltip" title="برای ارسال دعوت نامه یک ایمیل تماس مشخص کنید">
										<i class="ki-duotone ki-information fs-7">
											<span class="path1"></span>
											<span class="path2"></span>
											<span class="path3"></span>
										</i>
									</span></a>
                            </div>
                            <!--end::Menu item-->
                            <!--begin::Menu item-->
                            <div class="menu-item px-3" data-kt-menu-trigger="hover" data-kt-menu-placement="{{ LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'left-start' : 'right-start' }}">
                                <a href="#" class="menu-link px-3">
                                    <span class="menu-title">گروه ها</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <!--begin::Menu sub-->
                                <div class="menu-sub menu-sub-dropdown w-175px py-4">
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <a href="#" class="menu-link px-3" data-bs-toggle="tooltip" title="بزودی">ساختن گروه</a>
                                    </div>
                                    <!--end::Menu item-->
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <a href="#" class="menu-link px-3" data-bs-toggle="tooltip" title="بزودی">دعوت کاربران</a>
                                    </div>
                                    <!--end::Menu item-->
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <a href="#" class="menu-link px-3" data-bs-toggle="tooltip" title="بزودی">تنظیمات</a>
                                    </div>
                                    <!--end::Menu item-->
                                </div>
                                <!--end::Menu sub-->
                            </div>
                            <!--end::Menu item-->
                            <!--begin::Menu item-->
                            <div class="menu-item px-3 my-1">
                                <a href="#" class="menu-link px-3" data-bs-toggle="tooltip" title="بزودی">تنظیمات</a>
                            </div>
                            <!--end::Menu item-->
                        </div>
                        <!--end::Menu 3-->
                    </div>
                    <!--end::Menu-->
                    <!--begin::Close-->
                    <div class="btn btn-sm btn-icon btn-active-color-primary" id="kt_drawer_chat_close">
                        <i class="ki-duotone ki-cross-square fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::کارت toolbar-->
            </div>
            <!--end::کارت header-->
            <!--begin::کارت body-->
            <div class="card-body" id="kt_drawer_chat_messenger_body">
                <!--begin::پیام ها-->
                <div class="scroll-y me-n5 pe-5" data-kt-element="messages" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_drawer_chat_messenger_header, #kt_drawer_chat_messenger_footer" data-kt-scroll-wrappers="#kt_drawer_chat_messenger_body" data-kt-scroll-offset="0px">
                    <!--begin::پیام(in)-->
                    <div class="d-flex justify-content-start mb-10">
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-column align-items-start">
                            <!--begin::user-->
                            <div class="d-flex align-items-center mb-2">
                                <!--begin::Avatar-->
                                <div class="symbol symbol-35px symbol-circle">
                                    <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-25.jpg') }}" />
                                </div>
                                <!--end::Avatar-->
                                <!--begin::Details-->
                                <div class="ms-3">
                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary me-1">رضا علی ابادی</a>
                                    <span class="text-muted fs-7 mb-1">دو دقیقه پیش</span>
                                </div>
                                <!--end::Details-->
                            </div>
                            <!--end::user-->
                            <!--begin::Text-->
                            <div class="p-5 rounded bg-light-info text-gray-900 fw-semibold mw-lg-400px text-start" data-kt-element="message-text">چقدر احتمال دارد که شرکت ما را به دوستان و خانواده خود پیشنهاد دهید </div>
                            <!--end::Text-->
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::پیام(in)-->
                    <!--begin::پیام(out)-->
                    <div class="d-flex justify-content-end mb-10">
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-column align-items-end">
                            <!--begin::user-->
                            <div class="d-flex align-items-center mb-2">
                                <!--begin::Details-->
                                <div class="me-3">
                                    <span class="text-muted fs-7 mb-1">5دقیقه پیش</span>
                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary ms-1">شما</a>
                                </div>
                                <!--end::Details-->
                                <!--begin::Avatar-->
                                <div class="symbol symbol-35px symbol-circle">
                                    <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-1.jpg') }}" />
                                </div>
                                <!--end::Avatar-->
                            </div>
                            <!--end::user-->
                            <!--begin::Text-->
                            <div class="p-5 rounded bg-light-primary text-gray-900 fw-semibold mw-lg-400px text-end" data-kt-element="message-text">سلام ، ما فقط در حال نوشتن هستیم تا به شما اطلاع دهیم که در مخزن گیت هاب مشترک شده اید.</div>
                            <!--end::Text-->
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::پیام(out)-->
                    <!--begin::پیام(in)-->
                    <div class="d-flex justify-content-start mb-10">
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-column align-items-start">
                            <!--begin::user-->
                            <div class="d-flex align-items-center mb-2">
                                <!--begin::Avatar-->
                                <div class="symbol symbol-35px symbol-circle">
                                    <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-25.jpg') }}" />
                                </div>
                                <!--end::Avatar-->
                                <!--begin::Details-->
                                <div class="ms-3">
                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary me-1">رضا علی ابادی</a>
                                    <span class="text-muted fs-7 mb-1">یکساعت پیش</span>
                                </div>
                                <!--end::Details-->
                            </div>
                            <!--end::user-->
                            <!--begin::Text-->
                            <div class="p-5 rounded bg-light-info text-gray-900 fw-semibold mw-lg-400px text-start" data-kt-element="message-text">بله فهمیدم</div>
                            <!--end::Text-->
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::پیام(in)-->
                    <!--begin::پیام(out)-->
                    <div class="d-flex justify-content-end mb-10">
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-column align-items-end">
                            <!--begin::user-->
                            <div class="d-flex align-items-center mb-2">
                                <!--begin::Details-->
                                <div class="me-3">
                                    <span class="text-muted fs-7 mb-1">2 ساعت</span>
                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary ms-1">شما</a>
                                </div>
                                <!--end::Details-->
                                <!--begin::Avatar-->
                                <div class="symbol symbol-35px symbol-circle">
                                    <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-1.jpg') }}" />
                                </div>
                                <!--end::Avatar-->
                            </div>
                            <!--end::user-->
                            <!--begin::Text-->
                            <div class="p-5 rounded bg-light-primary text-gray-900 fw-semibold mw-lg-400px text-end" data-kt-element="message-text">شما برای همه موارد اعلان دریافت خواهید کرد ، درخواستها را بکشید!</div>
                            <!--end::Text-->
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::پیام(out)-->
                    <!--begin::پیام(in)-->
                    <div class="d-flex justify-content-start mb-10">
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-column align-items-start">
                            <!--begin::user-->
                            <div class="d-flex align-items-center mb-2">
                                <!--begin::Avatar-->
                                <div class="symbol symbol-35px symbol-circle">
                                    <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-25.jpg') }}" />
                                </div>
                                <!--end::Avatar-->
                                <!--begin::Details-->
                                <div class="ms-3">
                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary me-1">رضا علی ابادی</a>
                                    <span class="text-muted fs-7 mb-1">3 ساعت</span>
                                </div>
                                <!--end::Details-->
                            </div>
                            <!--end::user-->
                            <!--begin::Text-->
                            <div class="p-5 rounded bg-light-info text-gray-900 fw-semibold mw-lg-400px text-start" data-kt-element="message-text">شما می توانید با کلیک بر روی اینجا فوراً این مخزن را تماشا کنید:
                                <a href="https://keenthemes.com">satrasweb.ir</a></div>
                            <!--end::Text-->
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::پیام(in)-->
                    <!--begin::پیام(out)-->
                    <div class="d-flex justify-content-end mb-10">
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-column align-items-end">
                            <!--begin::user-->
                            <div class="d-flex align-items-center mb-2">
                                <!--begin::Details-->
                                <div class="me-3">
                                    <span class="text-muted fs-7 mb-1">4 ساعت</span>
                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary ms-1">شما</a>
                                </div>
                                <!--end::Details-->
                                <!--begin::Avatar-->
                                <div class="symbol symbol-35px symbol-circle">
                                    <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-1.jpg') }}" />
                                </div>
                                <!--end::Avatar-->
                            </div>
                            <!--end::user-->
                            <!--begin::Text-->
                            <div class="p-5 rounded bg-light-primary text-gray-900 fw-semibold mw-lg-400px text-end" data-kt-element="message-text">بیشتر دوره های بازرگانی خریداری شده در طول این فروش!</div>
                            <!--end::Text-->
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::پیام(out)-->
                    <!--begin::پیام(in)-->
                    <div class="d-flex justify-content-start mb-10">
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-column align-items-start">
                            <!--begin::user-->
                            <div class="d-flex align-items-center mb-2">
                                <!--begin::Avatar-->
                                <div class="symbol symbol-35px symbol-circle">
                                    <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-25.jpg') }}" />
                                </div>
                                <!--end::Avatar-->
                                <!--begin::Details-->
                                <div class="ms-3">
                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary me-1">رضا علی ابادی</a>
                                    <span class="text-muted fs-7 mb-1">5 ساعت</span>
                                </div>
                                <!--end::Details-->
                            </div>
                            <!--end::user-->
                            <!--begin::Text-->
                            <div class="p-5 rounded bg-light-info text-gray-900 fw-semibold mw-lg-400px text-start" data-kt-element="message-text">شرکت BBQ برای جشن گرفتن دستاوردها و اهداف سه ماهه آخر. غذا و نوشیدنی ارائه شده است</div>
                            <!--end::Text-->
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::پیام(in)-->
                    <!--begin::پیام(template for out)-->
                    <div class="d-flex justify-content-end mb-10 d-none" data-kt-element="template-out">
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-column align-items-end">
                            <!--begin::user-->
                            <div class="d-flex align-items-center mb-2">
                                <!--begin::Details-->
                                <div class="me-3">
                                    <span class="text-muted fs-7 mb-1">فقط</span>
                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary ms-1">شما</a>
                                </div>
                                <!--end::Details-->
                                <!--begin::Avatar-->
                                <div class="symbol symbol-35px symbol-circle">
                                    <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-1.jpg') }}" />
                                </div>
                                <!--end::Avatar-->
                            </div>
                            <!--end::user-->
                            <!--begin::Text-->
                            <div class="p-5 rounded bg-light-primary text-gray-900 fw-semibold mw-lg-400px text-end" data-kt-element="message-text"></div>
                            <!--end::Text-->
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::پیام(template for out)-->
                    <!--begin::پیام(template for in)-->
                    <div class="d-flex justify-content-start mb-10 d-none" data-kt-element="template-in">
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-column align-items-start">
                            <!--begin::user-->
                            <div class="d-flex align-items-center mb-2">
                                <!--begin::Avatar-->
                                <div class="symbol symbol-35px symbol-circle">
                                    <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-25.jpg') }}" />
                                </div>
                                <!--end::Avatar-->
                                <!--begin::Details-->
                                <div class="ms-3">
                                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary me-1">رضا علی ابادی</a>
                                    <span class="text-muted fs-7 mb-1">فقط</span>
                                </div>
                                <!--end::Details-->
                            </div>
                            <!--end::user-->
                            <!--begin::Text-->
                            <div class="p-5 rounded bg-light-info text-gray-900 fw-semibold mw-lg-400px text-start" data-kt-element="message-text">Right before vacation season we have the next Bigمعامله for you.</div>
                            <!--end::Text-->
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::پیام(template for in)-->
                </div>
                <!--end::پیام ها-->
            </div>
            <!--end::کارت body-->
            <!--begin::کارت footer-->
            <div class="card-footer pt-4" id="kt_drawer_chat_messenger_footer">
                <!--begin::Input-->
                <textarea class="form-control form-control-flush mb-3" rows="1" data-kt-element="input" placeholder="نوشتن پیام"></textarea>
                <!--end::Input-->
                <!--begin:Toolbar-->
                <div class="d-flex flex-stack">
                    <!--begin::Actions-->
                    <div class="d-flex align-items-center me-2">
                        <button class="btn btn-sm btn-icon btn-active-light-primary me-1" type="button" data-bs-toggle="tooltip" title="بزودی">
                            <i class="ki-duotone ki-paper-clip fs-3"></i>
                        </button>
                        <button class="btn btn-sm btn-icon btn-active-light-primary me-1" type="button" data-bs-toggle="tooltip" title="بزودی">
                            <i class="ki-duotone ki-cloud-add fs-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </button>
                    </div>
                    <!--end::Actions-->
                    <!--begin::ارسال-->
                    <button class="btn btn-primary" type="button" data-kt-element="send">ارسال</button>
                    <!--end::ارسال-->
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::کارت footer-->
        </div>
        <!--end::Messenger-->
    </div>
    <!--end::chat drawer-->
    <!--begin::chat drawer-->
    <div id="kt_shopping_cart" class="bg-body" data-kt-drawer="true" data-kt-drawer-name="cart" data-kt-drawer-activate="true" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'300px', 'md': '500px'}" data-kt-drawer-direction="end" data-kt-drawer-toggle="#kt_drawer_shopping_cart_toggle" data-kt-drawer-close="#kt_drawer_shopping_cart_close">
        <!--begin::Messenger-->
        <div class="card card-flush w-100 rounded-0">
            <!--begin::کارت header-->
            <div class="card-header">
                <!--begin::Title-->
                <h3 class="card-title text-gray-900 fw-bold">سبد خرید</h3>
                <!--end::Title-->
                <!--begin::کارت toolbar-->
                <div class="card-toolbar">
                    <!--begin::Close-->
                    <div class="btn btn-sm btn-icon btn-active-light-primary" id="kt_drawer_shopping_cart_close">
                        <i class="ki-duotone ki-cross fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::کارت toolbar-->
            </div>
            <!--end::کارت header-->
            <!--begin::کارت body-->
            <div class="card-body hover-scroll-overlay-y h-400px pt-5">
                <!--begin::item-->
                <div class="d-flex flex-stack">
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-column me-3">
                        <!--begin::Section-->
                        <div class="mb-3">
                            <a href="apps/ecommerce/sales/details.html" class="text-gray-800 text-hover-primary fs-4 fw-bold">ایبلندر</a>
                            <span class="text-gray-500 fw-semibold d-block">بهترین گجت آشپزخانه در سال 2022</span>
                        </div>
                        <!--end::Section-->
                        <!--begin::Section-->
                        <div class="d-flex align-items-center">
                            <span class="fw-bold text-gray-800 fs-5">$ 350</span>
                            <span class="text-muted mx-2">برای</span>
                            <span class="fw-bold text-gray-800 fs-5 me-3">5</span>
                            <a href="#" class="btn btn-sm btn-light-success btn-icon-success btn-icon w-25px h-25px me-2">
                                <i class="ki-duotone ki-minus fs-4"></i>
                            </a>
                            <a href="#" class="btn btn-sm btn-light-success btn-icon w-25px h-25px">
                                <i class="ki-duotone ki-plus fs-4"></i>
                            </a>
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::Wrapper-->
                    <!--begin::Pic-->
                    <div class="symbol symbol-70px symbol-2by3 flex-shrink-0">
                        <img src="{{ asset('theme/1/media/stock/600x400/img-1.jpg') }}" alt="" />
                    </div>
                    <!--end::Pic-->
                </div>
                <!--end::item-->
                <!--begin::separator-->
                <div class="separator separator-dashed my-6"></div>
                <!--end::separator-->
                <!--begin::item-->
                <div class="d-flex flex-stack">
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-column me-3">
                        <!--begin::Section-->
                        <div class="mb-3">
                            <a href="apps/ecommerce/sales/details.html" class="text-gray-800 text-hover-primary fs-4 fw-bold">پاک کننده هوشمند</a>
                            <span class="text-gray-500 fw-semibold d-block">ابزار هوشمند برای پخت و پز</span>
                        </div>
                        <!--end::Section-->
                        <!--begin::Section-->
                        <div class="d-flex align-items-center">
                            <span class="fw-bold text-gray-800 fs-5">$ 650</span>
                            <span class="text-muted mx-2">برای</span>
                            <span class="fw-bold text-gray-800 fs-5 me-3">4</span>
                            <a href="#" class="btn btn-sm btn-light-success btn-icon-success btn-icon w-25px h-25px me-2">
                                <i class="ki-duotone ki-minus fs-4"></i>
                            </a>
                            <a href="#" class="btn btn-sm btn-light-success btn-icon w-25px h-25px">
                                <i class="ki-duotone ki-plus fs-4"></i>
                            </a>
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::Wrapper-->
                    <!--begin::Pic-->
                    <div class="symbol symbol-70px symbol-2by3 flex-shrink-0">
                        <img src="{{ asset('theme/1/media/stock/600x400/img-3.jpg') }}" alt="" />
                    </div>
                    <!--end::Pic-->
                </div>
                <!--end::item-->
                <!--begin::separator-->
                <div class="separator separator-dashed my-6"></div>
                <!--end::separator-->
                <!--begin::item-->
                <div class="d-flex flex-stack">
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-column me-3">
                        <!--begin::Section-->
                        <div class="mb-3">
                            <a href="apps/ecommerce/sales/details.html" class="text-gray-800 text-hover-primary fs-4 fw-bold">دوربین</a>
                            <span class="text-gray-500 fw-semibold d-block">دوربین حرفه ای حرفه ای برای لبه</span>
                        </div>
                        <!--end::Section-->
                        <!--begin::Section-->
                        <div class="d-flex align-items-center">
                            <span class="fw-bold text-gray-800 fs-5">$ 150</span>
                            <span class="text-muted mx-2">برای</span>
                            <span class="fw-bold text-gray-800 fs-5 me-3">3</span>
                            <a href="#" class="btn btn-sm btn-light-success btn-icon-success btn-icon w-25px h-25px me-2">
                                <i class="ki-duotone ki-minus fs-4"></i>
                            </a>
                            <a href="#" class="btn btn-sm btn-light-success btn-icon w-25px h-25px">
                                <i class="ki-duotone ki-plus fs-4"></i>
                            </a>
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::Wrapper-->
                    <!--begin::Pic-->
                    <div class="symbol symbol-70px symbol-2by3 flex-shrink-0">
                        <img src="{{ asset('theme/1/media/stock/600x400/img-8.jpg') }}" alt="" />
                    </div>
                    <!--end::Pic-->
                </div>
                <!--end::item-->
                <!--begin::separator-->
                <div class="separator separator-dashed my-6"></div>
                <!--end::separator-->
                <!--begin::item-->
                <div class="d-flex flex-stack">
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-column me-3">
                        <!--begin::Section-->
                        <div class="mb-3">
                            <a href="apps/ecommerce/sales/details.html" class="text-gray-800 text-hover-primary fs-4 fw-bold">$D پرینتer</a>
                            <span class="text-gray-500 fw-semibold d-block">ساخت اشیاء منحصر به فرد</span>
                        </div>
                        <!--end::Section-->
                        <!--begin::Section-->
                        <div class="d-flex align-items-center">
                            <span class="fw-bold text-gray-800 fs-5">$ 1450</span>
                            <span class="text-muted mx-2">برای</span>
                            <span class="fw-bold text-gray-800 fs-5 me-3">7</span>
                            <a href="#" class="btn btn-sm btn-light-success btn-icon-success btn-icon w-25px h-25px me-2">
                                <i class="ki-duotone ki-minus fs-4"></i>
                            </a>
                            <a href="#" class="btn btn-sm btn-light-success btn-icon w-25px h-25px">
                                <i class="ki-duotone ki-plus fs-4"></i>
                            </a>
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::Wrapper-->
                    <!--begin::Pic-->
                    <div class="symbol symbol-70px symbol-2by3 flex-shrink-0">
                        <img src="{{ asset('theme/1/media/stock/600x400/img-26.jpg') }}" alt="" />
                    </div>
                    <!--end::Pic-->
                </div>
                <!--end::item-->
                <!--begin::separator-->
                <div class="separator separator-dashed my-6"></div>
                <!--end::separator-->
                <!--begin::item-->
                <div class="d-flex flex-stack">
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-column me-3">
                        <!--begin::Section-->
                        <div class="mb-3">
                            <a href="apps/ecommerce/sales/details.html" class="text-gray-800 text-hover-primary fs-4 fw-bold">MotionWire</a>
                            <span class="text-gray-500 fw-semibold d-block">Perfect animation tool</span>
                        </div>
                        <!--end::Section-->
                        <!--begin::Section-->
                        <div class="d-flex align-items-center">
                            <span class="fw-bold text-gray-800 fs-5">$ 650</span>
                            <span class="text-muted mx-2">برای</span>
                            <span class="fw-bold text-gray-800 fs-5 me-3">7</span>
                            <a href="#" class="btn btn-sm btn-light-success btn-icon-success btn-icon w-25px h-25px me-2">
                                <i class="ki-duotone ki-minus fs-4"></i>
                            </a>
                            <a href="#" class="btn btn-sm btn-light-success btn-icon w-25px h-25px">
                                <i class="ki-duotone ki-plus fs-4"></i>
                            </a>
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::Wrapper-->
                    <!--begin::Pic-->
                    <div class="symbol symbol-70px symbol-2by3 flex-shrink-0">
                        <img src="{{ asset('theme/1/media/stock/600x400/img-21.jpg') }}" alt="" />
                    </div>
                    <!--end::Pic-->
                </div>
                <!--end::item-->
                <!--begin::separator-->
                <div class="separator separator-dashed my-6"></div>
                <!--end::separator-->
                <!--begin::item-->
                <div class="d-flex flex-stack">
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-column me-3">
                        <!--begin::Section-->
                        <div class="mb-3">
                            <a href="apps/ecommerce/sales/details.html" class="text-gray-800 text-hover-primary fs-4 fw-bold">Samsung</a>
                            <span class="text-gray-500 fw-semibold d-block">پروفایل info,تایم لاین etc</span>
                        </div>
                        <!--end::Section-->
                        <!--begin::Section-->
                        <div class="d-flex align-items-center">
                            <span class="fw-bold text-gray-800 fs-5">$ 720</span>
                            <span class="text-muted mx-2">برای</span>
                            <span class="fw-bold text-gray-800 fs-5 me-3">6</span>
                            <a href="#" class="btn btn-sm btn-light-success btn-icon-success btn-icon w-25px h-25px me-2">
                                <i class="ki-duotone ki-minus fs-4"></i>
                            </a>
                            <a href="#" class="btn btn-sm btn-light-success btn-icon w-25px h-25px">
                                <i class="ki-duotone ki-plus fs-4"></i>
                            </a>
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::Wrapper-->
                    <!--begin::Pic-->
                    <div class="symbol symbol-70px symbol-2by3 flex-shrink-0">
                        <img src="{{ asset('theme/1/media/stock/600x400/img-34.jpg') }}" alt="" />
                    </div>
                    <!--end::Pic-->
                </div>
                <!--end::item-->
                <!--begin::separator-->
                <div class="separator separator-dashed my-6"></div>
                <!--end::separator-->
                <!--begin::item-->
                <div class="d-flex flex-stack">
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-column me-3">
                        <!--begin::Section-->
                        <div class="mb-3">
                            <a href="apps/ecommerce/sales/details.html" class="text-gray-800 text-hover-primary fs-4 fw-bold">$D پرینتer</a>
                            <span class="text-gray-500 fw-semibold d-block">ساخت اشیاء منحصر به فرد</span>
                        </div>
                        <!--end::Section-->
                        <!--begin::Section-->
                        <div class="d-flex align-items-center">
                            <span class="fw-bold text-gray-800 fs-5">$ 430</span>
                            <span class="text-muted mx-2">برای</span>
                            <span class="fw-bold text-gray-800 fs-5 me-3">8</span>
                            <a href="#" class="btn btn-sm btn-light-success btn-icon-success btn-icon w-25px h-25px me-2">
                                <i class="ki-duotone ki-minus fs-4"></i>
                            </a>
                            <a href="#" class="btn btn-sm btn-light-success btn-icon w-25px h-25px">
                                <i class="ki-duotone ki-plus fs-4"></i>
                            </a>
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::Wrapper-->
                    <!--begin::Pic-->
                    <div class="symbol symbol-70px symbol-2by3 flex-shrink-0">
                        <img src="{{ asset('theme/1/media/stock/600x400/img-27.jpg') }}" alt="" />
                    </div>
                    <!--end::Pic-->
                </div>
                <!--end::item-->
            </div>
            <!--end::کارت body-->
            <!--begin::کارت footer-->
            <div class="card-footer">
                <!--begin::item-->
                <div class="d-flex flex-stack">
                    <span class="fw-bold text-gray-600">کل</span>
                    <span class="text-gray-800 fw-bolder fs-5">$ 1840.00</span>
                </div>
                <!--end::item-->
                <!--begin::item-->
                <div class="d-flex flex-stack">
                    <span class="fw-bold text-gray-600">زیر مجموع</span>
                    <span class="text-primary fw-bolder fs-5">$ 246.35</span>
                </div>
                <!--end::item-->
                <!--end::Actions-->
                <div class="d-flex justify-content-end mt-9">
                    <a href="#" class="btn btn-primary d-flex justify-content-end">ثبت سفارش</a>
                </div>
                <!--end::Actions-->
            </div>
            <!--end::کارت footer-->
        </div>
        <!--end::Messenger-->
    </div>
    <!--end::chat drawer-->
    <!--begin::Modal - invite-->
    <div class="modal fade" id="kt_modal_invite_friends" tabindex="-1" aria-hidden="true">
        <!--begin::Modal dialog-->
        <div class="modal-dialog mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header pb-0 border-0 justify-content-end">
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
                <div class="modal-body scroll-y mx-5 mx-xl-18 pt-0 pb-15">
                    <!--begin::Heading-->
                    <div class="text-center mb-13">
                        <!--begin::Title-->
                        <h1 class="mb-3">دعوت از دوستان</h1>
                        <!--end::Title-->
                        <!--begin::توضیحات-->
                        <div class="text-muted fw-semibold fs-5">اگر به اطلاعات بیشتری نیاز دارید ، لطفاً این مورد را بررسی کنید
                            <a href="#" class="link-primary fw-bold">صفحه سوالات متداول</a>.</div>
                        <!--end::توضیحات-->
                    </div>
                    <!--end::Heading-->
                    <!--begin::Google Contacts دعوت-->
                    <div class="btn btn-light-primary fw-bold w-100 mb-8">
                        <img alt="Logo" src="{{ asset('theme/1/media/svg/brand-logos/google-icon.svg') }}" class="h-20px me-3" />دعوت از مخاطبین جمیل</div>
                    <!--end::Google Contacts دعوت-->
                    <!--begin::separator-->
                    <div class="separator d-flex flex-center mb-8">
                        <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3">یا</span>
                    </div>
                    <!--end::separator-->
                    <!--begin::Textarea-->
                    <textarea class="form-control form-control-solid mb-8" rows="3" placeholder="ایمیل خود را وارد کنید"></textarea>
                    <!--end::Textarea-->
                    <!--begin::users-->
                    <div class="mb-10">
                        <!--begin::Heading-->
                        <div class="fs-6 fw-semibold mb-2">دعوت از کاربران</div>
                        <!--end::Heading-->
                        <!--begin::لیست-->
                        <div class="mh-300px scroll-y me-n7 pe-7">
                            <!--begin::user-->
                            <div class="d-flex flex-stack py-4 border-bottom border-gray-300 border-bottom-dashed">
                                <!--begin::Details-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Avatar-->
                                    <div class="symbol symbol-35px symbol-circle">
                                        <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-6.jpg') }}" />
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::Details-->
                                    <div class="ms-5">
                                        <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">مرادی نیا</a>
                                        <div class="fw-semibold text-muted">smith@kpmg.com</div>
                                    </div>
                                    <!--end::Details-->
                                </div>
                                <!--end::Details-->
                                <!--begin::Access menu-->
                                <div class="ms-2 w-100px">
                                    <select class="form-select form-select-solid form-select-sm" data-control="select2" data-dropdown-parent="#kt_modal_invite_friends" data-hide-search="true">
                                        <option value="1">مهمان</option>
                                        <option value="2" selected="selected">مدیر</option>
                                        <option value="3">متفرقه</option>
                                    </select>
                                </div>
                                <!--end::Access menu-->
                            </div>
                            <!--end::user-->
                            <!--begin::user-->
                            <div class="d-flex flex-stack py-4 border-bottom border-gray-300 border-bottom-dashed">
                                <!--begin::Details-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Avatar-->
                                    <div class="symbol symbol-35px symbol-circle">
                                        <span class="symbol-label bg-light-danger text-danger fw-semibold">M</span>
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::Details-->
                                    <div class="ms-5">
                                        <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">میلاد مرادی</a>
                                        <div class="fw-semibold text-muted">melody@altbox.com</div>
                                    </div>
                                    <!--end::Details-->
                                </div>
                                <!--end::Details-->
                                <!--begin::Access menu-->
                                <div class="ms-2 w-100px">
                                    <select class="form-select form-select-solid form-select-sm" data-control="select2" data-dropdown-parent="#kt_modal_invite_friends" data-hide-search="true">
                                        <option value="1" selected="selected">مهمان</option>
                                        <option value="2">مدیر</option>
                                        <option value="3">متفرقه</option>
                                    </select>
                                </div>
                                <!--end::Access menu-->
                            </div>
                            <!--end::user-->
                            <!--begin::user-->
                            <div class="d-flex flex-stack py-4 border-bottom border-gray-300 border-bottom-dashed">
                                <!--begin::Details-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Avatar-->
                                    <div class="symbol symbol-35px symbol-circle">
                                        <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-1.jpg') }}" />
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::Details-->
                                    <div class="ms-5">
                                        <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">جلالی</a>
                                        <div class="fw-semibold text-muted">max@kt.com</div>
                                    </div>
                                    <!--end::Details-->
                                </div>
                                <!--end::Details-->
                                <!--begin::Access menu-->
                                <div class="ms-2 w-100px">
                                    <select class="form-select form-select-solid form-select-sm" data-control="select2" data-dropdown-parent="#kt_modal_invite_friends" data-hide-search="true">
                                        <option value="1">مهمان</option>
                                        <option value="2">مدیر</option>
                                        <option value="3" selected="selected">متفرقه </option>
                                    </select>
                                </div>
                                <!--end::Access menu-->
                            </div>
                            <!--end::user-->
                            <!--begin::user-->
                            <div class="d-flex flex-stack py-4 border-bottom border-gray-300 border-bottom-dashed">
                                <!--begin::Details-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Avatar-->
                                    <div class="symbol symbol-35px symbol-circle">
                                        <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-5.jpg') }}" />
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::Details-->
                                    <div class="ms-5">
                                        <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">محسن برومند</a>
                                        <div class="fw-semibold text-muted">sean@dellito.com</div>
                                    </div>
                                    <!--end::Details-->
                                </div>
                                <!--end::Details-->
                                <!--begin::Access menu-->
                                <div class="ms-2 w-100px">
                                    <select class="form-select form-select-solid form-select-sm" data-control="select2" data-dropdown-parent="#kt_modal_invite_friends" data-hide-search="true">
                                        <option value="1">مهمان</option>
                                        <option value="2" selected="selected">مدیر</option>
                                        <option value="3">متفرقه</option>
                                    </select>
                                </div>
                                <!--end::Access menu-->
                            </div>
                            <!--end::user-->
                            <!--begin::user-->
                            <div class="d-flex flex-stack py-4 border-bottom border-gray-300 border-bottom-dashed">
                                <!--begin::Details-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Avatar-->
                                    <div class="symbol symbol-35px symbol-circle">
                                        <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-25.jpg') }}" />
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::Details-->
                                    <div class="ms-5">
                                        <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">رضا علی ابادی</a>
                                        <div class="fw-semibold text-muted">brian@exchange.com</div>
                                    </div>
                                    <!--end::Details-->
                                </div>
                                <!--end::Details-->
                                <!--begin::Access menu-->
                                <div class="ms-2 w-100px">
                                    <select class="form-select form-select-solid form-select-sm" data-control="select2" data-dropdown-parent="#kt_modal_invite_friends" data-hide-search="true">
                                        <option value="1">مهمان</option>
                                        <option value="2">مدیر</option>
                                        <option value="3" selected="selected">متفرقه </option>
                                    </select>
                                </div>
                                <!--end::Access menu-->
                            </div>
                            <!--end::user-->
                            <!--begin::user-->
                            <div class="d-flex flex-stack py-4 border-bottom border-gray-300 border-bottom-dashed">
                                <!--begin::Details-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Avatar-->
                                    <div class="symbol symbol-35px symbol-circle">
                                        <span class="symbol-label bg-light-warning text-warning fw-semibold">C</span>
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::Details-->
                                    <div class="ms-5">
                                        <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">میکائیل کرمانی</a>
                                        <div class="fw-semibold text-muted">mik@pex.com</div>
                                    </div>
                                    <!--end::Details-->
                                </div>
                                <!--end::Details-->
                                <!--begin::Access menu-->
                                <div class="ms-2 w-100px">
                                    <select class="form-select form-select-solid form-select-sm" data-control="select2" data-dropdown-parent="#kt_modal_invite_friends" data-hide-search="true">
                                        <option value="1">مهمان</option>
                                        <option value="2" selected="selected">مدیر</option>
                                        <option value="3">متفرقه</option>
                                    </select>
                                </div>
                                <!--end::Access menu-->
                            </div>
                            <!--end::user-->
                            <!--begin::user-->
                            <div class="d-flex flex-stack py-4 border-bottom border-gray-300 border-bottom-dashed">
                                <!--begin::Details-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Avatar-->
                                    <div class="symbol symbol-35px symbol-circle">
                                        <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-9.jpg') }}" />
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::Details-->
                                    <div class="ms-5">
                                        <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">محمد رصایی</a>
                                        <div class="fw-semibold text-muted">f.mit@kpmg.com</div>
                                    </div>
                                    <!--end::Details-->
                                </div>
                                <!--end::Details-->
                                <!--begin::Access menu-->
                                <div class="ms-2 w-100px">
                                    <select class="form-select form-select-solid form-select-sm" data-control="select2" data-dropdown-parent="#kt_modal_invite_friends" data-hide-search="true">
                                        <option value="1">مهمان</option>
                                        <option value="2">مدیر</option>
                                        <option value="3" selected="selected">متفرقه </option>
                                    </select>
                                </div>
                                <!--end::Access menu-->
                            </div>
                            <!--end::user-->
                            <!--begin::user-->
                            <div class="d-flex flex-stack py-4 border-bottom border-gray-300 border-bottom-dashed">
                                <!--begin::Details-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Avatar-->
                                    <div class="symbol symbol-35px symbol-circle">
                                        <span class="symbol-label bg-light-danger text-danger fw-semibold">O</span>
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::Details-->
                                    <div class="ms-5">
                                        <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">امید وحیدی</a>
                                        <div class="fw-semibold text-muted">olivia@corpmail.com</div>
                                    </div>
                                    <!--end::Details-->
                                </div>
                                <!--end::Details-->
                                <!--begin::Access menu-->
                                <div class="ms-2 w-100px">
                                    <select class="form-select form-select-solid form-select-sm" data-control="select2" data-dropdown-parent="#kt_modal_invite_friends" data-hide-search="true">
                                        <option value="1">مهمان</option>
                                        <option value="2" selected="selected">مدیر</option>
                                        <option value="3">متفرقه</option>
                                    </select>
                                </div>
                                <!--end::Access menu-->
                            </div>
                            <!--end::user-->
                            <!--begin::user-->
                            <div class="d-flex flex-stack py-4 border-bottom border-gray-300 border-bottom-dashed">
                                <!--begin::Details-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Avatar-->
                                    <div class="symbol symbol-35px symbol-circle">
                                        <span class="symbol-label bg-light-primary text-primary fw-semibold">N</span>
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::Details-->
                                    <div class="ms-5">
                                        <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">محسن برومند</a>
                                        <div class="fw-semibold text-muted">owen.neil@gmail.com</div>
                                    </div>
                                    <!--end::Details-->
                                </div>
                                <!--end::Details-->
                                <!--begin::Access menu-->
                                <div class="ms-2 w-100px">
                                    <select class="form-select form-select-solid form-select-sm" data-control="select2" data-dropdown-parent="#kt_modal_invite_friends" data-hide-search="true">
                                        <option value="1" selected="selected">مهمان</option>
                                        <option value="2">مدیر</option>
                                        <option value="3">متفرقه</option>
                                    </select>
                                </div>
                                <!--end::Access menu-->
                            </div>
                            <!--end::user-->
                            <!--begin::user-->
                            <div class="d-flex flex-stack py-4 border-bottom border-gray-300 border-bottom-dashed">
                                <!--begin::Details-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Avatar-->
                                    <div class="symbol symbol-35px symbol-circle">
                                        <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-23.jpg') }}" />
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::Details-->
                                    <div class="ms-5">
                                        <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">علی کاربر</a>
                                        <div class="fw-semibold text-muted">dam@consilting.com</div>
                                    </div>
                                    <!--end::Details-->
                                </div>
                                <!--end::Details-->
                                <!--begin::Access menu-->
                                <div class="ms-2 w-100px">
                                    <select class="form-select form-select-solid form-select-sm" data-control="select2" data-dropdown-parent="#kt_modal_invite_friends" data-hide-search="true">
                                        <option value="1">مهمان</option>
                                        <option value="2">مدیر</option>
                                        <option value="3" selected="selected">متفرقه </option>
                                    </select>
                                </div>
                                <!--end::Access menu-->
                            </div>
                            <!--end::user-->
                            <!--begin::user-->
                            <div class="d-flex flex-stack py-4 border-bottom border-gray-300 border-bottom-dashed">
                                <!--begin::Details-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Avatar-->
                                    <div class="symbol symbol-35px symbol-circle">
                                        <span class="symbol-label bg-light-danger text-danger fw-semibold">E</span>
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::Details-->
                                    <div class="ms-5">
                                        <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">الهام بارانی</a>
                                        <div class="fw-semibold text-muted">emma@intenso.com</div>
                                    </div>
                                    <!--end::Details-->
                                </div>
                                <!--end::Details-->
                                <!--begin::Access menu-->
                                <div class="ms-2 w-100px">
                                    <select class="form-select form-select-solid form-select-sm" data-control="select2" data-dropdown-parent="#kt_modal_invite_friends" data-hide-search="true">
                                        <option value="1">مهمان</option>
                                        <option value="2" selected="selected">مدیر</option>
                                        <option value="3">متفرقه</option>
                                    </select>
                                </div>
                                <!--end::Access menu-->
                            </div>
                            <!--end::user-->
                            <!--begin::user-->
                            <div class="d-flex flex-stack py-4 border-bottom border-gray-300 border-bottom-dashed">
                                <!--begin::Details-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Avatar-->
                                    <div class="symbol symbol-35px symbol-circle">
                                        <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-12.jpg') }}" />
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::Details-->
                                    <div class="ms-5">
                                        <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">آنا کوهی</a>
                                        <div class="fw-semibold text-muted">ana.cf@limtel.com</div>
                                    </div>
                                    <!--end::Details-->
                                </div>
                                <!--end::Details-->
                                <!--begin::Access menu-->
                                <div class="ms-2 w-100px">
                                    <select class="form-select form-select-solid form-select-sm" data-control="select2" data-dropdown-parent="#kt_modal_invite_friends" data-hide-search="true">
                                        <option value="1" selected="selected">مهمان</option>
                                        <option value="2">مدیر</option>
                                        <option value="3">متفرقه</option>
                                    </select>
                                </div>
                                <!--end::Access menu-->
                            </div>
                            <!--end::user-->
                            <!--begin::user-->
                            <div class="d-flex flex-stack py-4 border-bottom border-gray-300 border-bottom-dashed">
                                <!--begin::Details-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Avatar-->
                                    <div class="symbol symbol-35px symbol-circle">
                                        <span class="symbol-label bg-light-info text-info fw-semibold">A</span>
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::Details-->
                                    <div class="ms-5">
                                        <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">رابرت دو</a>
                                        <div class="fw-semibold text-muted">robert@benko.com</div>
                                    </div>
                                    <!--end::Details-->
                                </div>
                                <!--end::Details-->
                                <!--begin::Access menu-->
                                <div class="ms-2 w-100px">
                                    <select class="form-select form-select-solid form-select-sm" data-control="select2" data-dropdown-parent="#kt_modal_invite_friends" data-hide-search="true">
                                        <option value="1">مهمان</option>
                                        <option value="2">مدیر</option>
                                        <option value="3" selected="selected">متفرقه </option>
                                    </select>
                                </div>
                                <!--end::Access menu-->
                            </div>
                            <!--end::user-->
                            <!--begin::user-->
                            <div class="d-flex flex-stack py-4 border-bottom border-gray-300 border-bottom-dashed">
                                <!--begin::Details-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Avatar-->
                                    <div class="symbol symbol-35px symbol-circle">
                                        <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-13.jpg') }}" />
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::Details-->
                                    <div class="ms-5">
                                        <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">جواد مولای</a>
                                        <div class="fw-semibold text-muted">miller@mapple.com</div>
                                    </div>
                                    <!--end::Details-->
                                </div>
                                <!--end::Details-->
                                <!--begin::Access menu-->
                                <div class="ms-2 w-100px">
                                    <select class="form-select form-select-solid form-select-sm" data-control="select2" data-dropdown-parent="#kt_modal_invite_friends" data-hide-search="true">
                                        <option value="1">مهمان</option>
                                        <option value="2">مدیر</option>
                                        <option value="3" selected="selected">متفرقه </option>
                                    </select>
                                </div>
                                <!--end::Access menu-->
                            </div>
                            <!--end::user-->
                            <!--begin::user-->
                            <div class="d-flex flex-stack py-4 border-bottom border-gray-300 border-bottom-dashed">
                                <!--begin::Details-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Avatar-->
                                    <div class="symbol symbol-35px symbol-circle">
                                        <span class="symbol-label bg-light-success text-success fw-semibold">L</span>
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::Details-->
                                    <div class="ms-5">
                                        <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">لقمان کامرانی</a>
                                        <div class="fw-semibold text-muted">lucy.m@fentech.com</div>
                                    </div>
                                    <!--end::Details-->
                                </div>
                                <!--end::Details-->
                                <!--begin::Access menu-->
                                <div class="ms-2 w-100px">
                                    <select class="form-select form-select-solid form-select-sm" data-control="select2" data-dropdown-parent="#kt_modal_invite_friends" data-hide-search="true">
                                        <option value="1">مهمان</option>
                                        <option value="2" selected="selected">مدیر</option>
                                        <option value="3">متفرقه</option>
                                    </select>
                                </div>
                                <!--end::Access menu-->
                            </div>
                            <!--end::user-->
                            <!--begin::user-->
                            <div class="d-flex flex-stack py-4 border-bottom border-gray-300 border-bottom-dashed">
                                <!--begin::Details-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Avatar-->
                                    <div class="symbol symbol-35px symbol-circle">
                                        <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-21.jpg') }}" />
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::Details-->
                                    <div class="ms-5">
                                        <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">احسان ورزقانی</a>
                                        <div class="fw-semibold text-muted">ethan@loop.com.au</div>
                                    </div>
                                    <!--end::Details-->
                                </div>
                                <!--end::Details-->
                                <!--begin::Access menu-->
                                <div class="ms-2 w-100px">
                                    <select class="form-select form-select-solid form-select-sm" data-control="select2" data-dropdown-parent="#kt_modal_invite_friends" data-hide-search="true">
                                        <option value="1" selected="selected">مهمان</option>
                                        <option value="2">مدیر</option>
                                        <option value="3">متفرقه</option>
                                    </select>
                                </div>
                                <!--end::Access menu-->
                            </div>
                            <!--end::user-->
                            <!--begin::user-->
                            <div class="d-flex flex-stack py-4">
                                <!--begin::Details-->
                                <div class="d-flex align-items-center">
                                    <!--begin::Avatar-->
                                    <div class="symbol symbol-35px symbol-circle">
                                        <span class="symbol-label bg-light-info text-info fw-semibold">A</span>
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::Details-->
                                    <div class="ms-5">
                                        <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">رابرت دو</a>
                                        <div class="fw-semibold text-muted">robert@benko.com</div>
                                    </div>
                                    <!--end::Details-->
                                </div>
                                <!--end::Details-->
                                <!--begin::Access menu-->
                                <div class="ms-2 w-100px">
                                    <select class="form-select form-select-solid form-select-sm" data-control="select2" data-dropdown-parent="#kt_modal_invite_friends" data-hide-search="true">
                                        <option value="1">مهمان</option>
                                        <option value="2">مدیر</option>
                                        <option value="3" selected="selected">متفرقه </option>
                                    </select>
                                </div>
                                <!--end::Access menu-->
                            </div>
                            <!--end::user-->
                        </div>
                        <!--end::لیست-->
                    </div>
                    <!--end::users-->
                    <!--begin::Notice-->
                    <div class="d-flex flex-stack">
                        <!--begin::Tags-->
                        <div class="me-5 fw-semibold">
                            <label class="fs-6">افزودن کاربران</label>
                            <div class="fs-7 text-muted">اگر به اطلاعات بیشتری نیاز دارید ، لطفا برنامه ریزی بودجه را بررسی کنید</div>
                        </div>
                        <!--end::Tags-->
                        <!--begin::Switch-->
                        <label class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" value="1" checked="checked" />
                            <span class="form-check-label fw-semibold text-muted">همه بدهکار هستیم</span>
                        </label>
                        <!--end::Switch-->
                    </div>
                    <!--end::Notice-->
                </div>
                <!--end::Modal body-->
            </div>
            <!--end::Modal content-->
        </div>
        <!--end::Modal dialog-->
    </div>
    <!--end::Modal - invite-->
    <!--begin::Modal - Users search-->
    <div class="modal fade" id="kt_modal_users_search" tabindex="-1" aria-hidden="true">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header pb-0 border-0 justify-content-end">
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
                <div class="modal-body scroll-y mx-5 mx-xl-18 pt-0 pb-15">
                    <!--begin::Content-->
                    <div class="text-center mb-13">
                        <h1 class="mb-3">جستجو کاربران</h1>
                        <div class="text-muted fw-semibold fs-5">دعوت از همکاران به پروژه شما</div>
                    </div>
                    <!--end::Content-->
                    <!--begin::جستجو-->
                    <div id="kt_modal_users_search_handler" data-kt-search-keypress="true" data-kt-search-min-length="2" data-kt-search-enter="enter" data-kt-search-layout="inline">
                        <!--begin::form-->
                        <form data-kt-search-element="form" class="w-100 position-relative mb-5" autocomplete="off">
                            <!--begin::Hidden input(افزودنed to disable form autocomplete)-->
                            <input type="hidden" />
                            <!--end::Hidden input-->
                            <!--begin::Icon-->
                            <i class="ki-duotone ki-magnifier fs-2 fs-lg-1 text-gray-500 position-absolute top-50 ms-5 translate-middle-y">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <!--end::Icon-->
                            <!--begin::Input-->
                            <input type="text" class="form-control form-control-lg form-control-solid px-15" name="search" value="" placeholder="جستجو..." data-kt-search-element="input" />
                            <!--end::Input-->
                            <!--begin::Spinner-->
                            <span class="position-absolute top-50 end-0 translate-middle-y lh-0 d-none me-5" data-kt-search-element="spinner">
									<span class="spinner-border h-15px w-15px align-middle text-muted"></span>
								</span>
                            <!--end::Spinner-->
                            <!--begin::ریست-->
                            <span class="btn btn-flush btn-active-color-primary position-absolute top-50 end-0 translate-middle-y lh-0 me-5 d-none" data-kt-search-element="clear">
									<i class="ki-duotone ki-cross fs-2 fs-lg-1 me-0">
										<span class="path1"></span>
										<span class="path2"></span>
									</i>
								</span>
                            <!--end::ریست-->
                        </form>
                        <!--end::form-->
                        <!--begin::Wrapper-->
                        <div class="py-5">
                            <!--begin::پیشنهادات-->
                            <div data-kt-search-element="suggestions">
                                <!--begin::Heading-->
                                <h3 class="fw-semibold mb-5">اخیرly searched:</h3>
                                <!--end::Heading-->
                                <!--begin::users-->
                                <div class="mh-375px scroll-y me-n7 pe-7">
                                    <!--begin::user-->
                                    <a href="#" class="d-flex align-items-center p-3 rounded bg-state-light bg-state-opacity-50 mb-1">
                                        <!--begin::Avatar-->
                                        <div class="symbol symbol-35px symbol-circle me-5">
                                            <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-6.jpg') }}" />
                                        </div>
                                        <!--end::Avatar-->
                                        <!--begin::Info-->
                                        <div class="fw-semibold">
                                            <span class="fs-6 text-gray-800 me-2">مرادی نیا</span>
                                            <span class="badge badge-light">کارگردان هنری</span>
                                        </div>
                                        <!--end::Info-->
                                    </a>
                                    <!--end::user-->
                                    <!--begin::user-->
                                    <a href="#" class="d-flex align-items-center p-3 rounded bg-state-light bg-state-opacity-50 mb-1">
                                        <!--begin::Avatar-->
                                        <div class="symbol symbol-35px symbol-circle me-5">
                                            <span class="symbol-label bg-light-danger text-danger fw-semibold">M</span>
                                        </div>
                                        <!--end::Avatar-->
                                        <!--begin::Info-->
                                        <div class="fw-semibold">
                                            <span class="fs-6 text-gray-800 me-2">میلاد مرادی</span>
                                            <span class="badge badge-light">بازاریابی تحلیلی</span>
                                        </div>
                                        <!--end::Info-->
                                    </a>
                                    <!--end::user-->
                                    <!--begin::user-->
                                    <a href="#" class="d-flex align-items-center p-3 rounded bg-state-light bg-state-opacity-50 mb-1">
                                        <!--begin::Avatar-->
                                        <div class="symbol symbol-35px symbol-circle me-5">
                                            <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-1.jpg') }}" />
                                        </div>
                                        <!--end::Avatar-->
                                        <!--begin::Info-->
                                        <div class="fw-semibold">
                                            <span class="fs-6 text-gray-800 me-2">جلالی</span>
                                            <span class="badge badge-light">مهندس نرم افزار</span>
                                        </div>
                                        <!--end::Info-->
                                    </a>
                                    <!--end::user-->
                                    <!--begin::user-->
                                    <a href="#" class="d-flex align-items-center p-3 rounded bg-state-light bg-state-opacity-50 mb-1">
                                        <!--begin::Avatar-->
                                        <div class="symbol symbol-35px symbol-circle me-5">
                                            <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-5.jpg') }}" />
                                        </div>
                                        <!--end::Avatar-->
                                        <!--begin::Info-->
                                        <div class="fw-semibold">
                                            <span class="fs-6 text-gray-800 me-2">محسن برومند</span>
                                            <span class="badge badge-light">توسعه دهنده وب</span>
                                        </div>
                                        <!--end::Info-->
                                    </a>
                                    <!--end::user-->
                                    <!--begin::user-->
                                    <a href="#" class="d-flex align-items-center p-3 rounded bg-state-light bg-state-opacity-50 mb-1">
                                        <!--begin::Avatar-->
                                        <div class="symbol symbol-35px symbol-circle me-5">
                                            <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-25.jpg') }}" />
                                        </div>
                                        <!--end::Avatar-->
                                        <!--begin::Info-->
                                        <div class="fw-semibold">
                                            <span class="fs-6 text-gray-800 me-2">رضا علی ابادی</span>
                                            <span class="badge badge-light">طراح یو ای و یوایکس</span>
                                        </div>
                                        <!--end::Info-->
                                    </a>
                                    <!--end::user-->
                                </div>
                                <!--end::users-->
                            </div>
                            <!--end::پیشنهادات-->
                            <!--begin::Results(add d-none to below element to hide the users list by default)-->
                            <div data-kt-search-element="results" class="d-none">
                                <!--begin::users-->
                                <div class="mh-375px scroll-y me-n7 pe-7">
                                    <!--begin::user-->
                                    <div class="rounded d-flex flex-stack bg-active-lighten p-4" data-user-id="0">
                                        <!--begin::Details-->
                                        <div class="d-flex align-items-center">
                                            <!--begin::Checkbox-->
                                            <label class="form-check form-check-custom form-check-solid me-5">
                                                <input class="form-check-input" type="checkbox" name="users" data-kt-check="true" data-kt-check-target="[data-user-id='0']" value="0" />
                                            </label>
                                            <!--end::Checkbox-->
                                            <!--begin::Avatar-->
                                            <div class="symbol symbol-35px symbol-circle">
                                                <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-6.jpg') }}" />
                                            </div>
                                            <!--end::Avatar-->
                                            <!--begin::Details-->
                                            <div class="ms-5">
                                                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">مرادی نیا</a>
                                                <div class="fw-semibold text-muted">smith@kpmg.com</div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Details-->
                                        <!--begin::Access menu-->
                                        <div class="ms-2 w-100px">
                                            <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true">
                                                <option value="1">مهمان</option>
                                                <option value="2" selected="selected">مدیر</option>
                                                <option value="3">متفرقه</option>
                                            </select>
                                        </div>
                                        <!--end::Access menu-->
                                    </div>
                                    <!--end::user-->
                                    <!--begin::separator-->
                                    <div class="border-bottom border-gray-300 border-bottom-dashed"></div>
                                    <!--end::separator-->
                                    <!--begin::user-->
                                    <div class="rounded d-flex flex-stack bg-active-lighten p-4" data-user-id="1">
                                        <!--begin::Details-->
                                        <div class="d-flex align-items-center">
                                            <!--begin::Checkbox-->
                                            <label class="form-check form-check-custom form-check-solid me-5">
                                                <input class="form-check-input" type="checkbox" name="users" data-kt-check="true" data-kt-check-target="[data-user-id='1']" value="1" />
                                            </label>
                                            <!--end::Checkbox-->
                                            <!--begin::Avatar-->
                                            <div class="symbol symbol-35px symbol-circle">
                                                <span class="symbol-label bg-light-danger text-danger fw-semibold">M</span>
                                            </div>
                                            <!--end::Avatar-->
                                            <!--begin::Details-->
                                            <div class="ms-5">
                                                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">میلاد مرادی</a>
                                                <div class="fw-semibold text-muted">melody@altbox.com</div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Details-->
                                        <!--begin::Access menu-->
                                        <div class="ms-2 w-100px">
                                            <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true">
                                                <option value="1" selected="selected">مهمان</option>
                                                <option value="2">مدیر</option>
                                                <option value="3">متفرقه</option>
                                            </select>
                                        </div>
                                        <!--end::Access menu-->
                                    </div>
                                    <!--end::user-->
                                    <!--begin::separator-->
                                    <div class="border-bottom border-gray-300 border-bottom-dashed"></div>
                                    <!--end::separator-->
                                    <!--begin::user-->
                                    <div class="rounded d-flex flex-stack bg-active-lighten p-4" data-user-id="2">
                                        <!--begin::Details-->
                                        <div class="d-flex align-items-center">
                                            <!--begin::Checkbox-->
                                            <label class="form-check form-check-custom form-check-solid me-5">
                                                <input class="form-check-input" type="checkbox" name="users" data-kt-check="true" data-kt-check-target="[data-user-id='2']" value="2" />
                                            </label>
                                            <!--end::Checkbox-->
                                            <!--begin::Avatar-->
                                            <div class="symbol symbol-35px symbol-circle">
                                                <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-1.jpg') }}" />
                                            </div>
                                            <!--end::Avatar-->
                                            <!--begin::Details-->
                                            <div class="ms-5">
                                                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">جلالی</a>
                                                <div class="fw-semibold text-muted">max@kt.com</div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Details-->
                                        <!--begin::Access menu-->
                                        <div class="ms-2 w-100px">
                                            <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true">
                                                <option value="1">مهمان</option>
                                                <option value="2">مدیر</option>
                                                <option value="3" selected="selected">متفرقه </option>
                                            </select>
                                        </div>
                                        <!--end::Access menu-->
                                    </div>
                                    <!--end::user-->
                                    <!--begin::separator-->
                                    <div class="border-bottom border-gray-300 border-bottom-dashed"></div>
                                    <!--end::separator-->
                                    <!--begin::user-->
                                    <div class="rounded d-flex flex-stack bg-active-lighten p-4" data-user-id="3">
                                        <!--begin::Details-->
                                        <div class="d-flex align-items-center">
                                            <!--begin::Checkbox-->
                                            <label class="form-check form-check-custom form-check-solid me-5">
                                                <input class="form-check-input" type="checkbox" name="users" data-kt-check="true" data-kt-check-target="[data-user-id='3']" value="3" />
                                            </label>
                                            <!--end::Checkbox-->
                                            <!--begin::Avatar-->
                                            <div class="symbol symbol-35px symbol-circle">
                                                <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-5.jpg') }}" />
                                            </div>
                                            <!--end::Avatar-->
                                            <!--begin::Details-->
                                            <div class="ms-5">
                                                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">محسن برومند</a>
                                                <div class="fw-semibold text-muted">sean@dellito.com</div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Details-->
                                        <!--begin::Access menu-->
                                        <div class="ms-2 w-100px">
                                            <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true">
                                                <option value="1">مهمان</option>
                                                <option value="2" selected="selected">مدیر</option>
                                                <option value="3">متفرقه</option>
                                            </select>
                                        </div>
                                        <!--end::Access menu-->
                                    </div>
                                    <!--end::user-->
                                    <!--begin::separator-->
                                    <div class="border-bottom border-gray-300 border-bottom-dashed"></div>
                                    <!--end::separator-->
                                    <!--begin::user-->
                                    <div class="rounded d-flex flex-stack bg-active-lighten p-4" data-user-id="4">
                                        <!--begin::Details-->
                                        <div class="d-flex align-items-center">
                                            <!--begin::Checkbox-->
                                            <label class="form-check form-check-custom form-check-solid me-5">
                                                <input class="form-check-input" type="checkbox" name="users" data-kt-check="true" data-kt-check-target="[data-user-id='4']" value="4" />
                                            </label>
                                            <!--end::Checkbox-->
                                            <!--begin::Avatar-->
                                            <div class="symbol symbol-35px symbol-circle">
                                                <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-25.jpg') }}" />
                                            </div>
                                            <!--end::Avatar-->
                                            <!--begin::Details-->
                                            <div class="ms-5">
                                                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">رضا علی ابادی</a>
                                                <div class="fw-semibold text-muted">brian@exchange.com</div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Details-->
                                        <!--begin::Access menu-->
                                        <div class="ms-2 w-100px">
                                            <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true">
                                                <option value="1">مهمان</option>
                                                <option value="2">مدیر</option>
                                                <option value="3" selected="selected">متفرقه </option>
                                            </select>
                                        </div>
                                        <!--end::Access menu-->
                                    </div>
                                    <!--end::user-->
                                    <!--begin::separator-->
                                    <div class="border-bottom border-gray-300 border-bottom-dashed"></div>
                                    <!--end::separator-->
                                    <!--begin::user-->
                                    <div class="rounded d-flex flex-stack bg-active-lighten p-4" data-user-id="5">
                                        <!--begin::Details-->
                                        <div class="d-flex align-items-center">
                                            <!--begin::Checkbox-->
                                            <label class="form-check form-check-custom form-check-solid me-5">
                                                <input class="form-check-input" type="checkbox" name="users" data-kt-check="true" data-kt-check-target="[data-user-id='5']" value="5" />
                                            </label>
                                            <!--end::Checkbox-->
                                            <!--begin::Avatar-->
                                            <div class="symbol symbol-35px symbol-circle">
                                                <span class="symbol-label bg-light-warning text-warning fw-semibold">C</span>
                                            </div>
                                            <!--end::Avatar-->
                                            <!--begin::Details-->
                                            <div class="ms-5">
                                                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">میکائیل کرمانی</a>
                                                <div class="fw-semibold text-muted">mik@pex.com</div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Details-->
                                        <!--begin::Access menu-->
                                        <div class="ms-2 w-100px">
                                            <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true">
                                                <option value="1">مهمان</option>
                                                <option value="2" selected="selected">مدیر</option>
                                                <option value="3">متفرقه</option>
                                            </select>
                                        </div>
                                        <!--end::Access menu-->
                                    </div>
                                    <!--end::user-->
                                    <!--begin::separator-->
                                    <div class="border-bottom border-gray-300 border-bottom-dashed"></div>
                                    <!--end::separator-->
                                    <!--begin::user-->
                                    <div class="rounded d-flex flex-stack bg-active-lighten p-4" data-user-id="6">
                                        <!--begin::Details-->
                                        <div class="d-flex align-items-center">
                                            <!--begin::Checkbox-->
                                            <label class="form-check form-check-custom form-check-solid me-5">
                                                <input class="form-check-input" type="checkbox" name="users" data-kt-check="true" data-kt-check-target="[data-user-id='6']" value="6" />
                                            </label>
                                            <!--end::Checkbox-->
                                            <!--begin::Avatar-->
                                            <div class="symbol symbol-35px symbol-circle">
                                                <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-9.jpg') }}" />
                                            </div>
                                            <!--end::Avatar-->
                                            <!--begin::Details-->
                                            <div class="ms-5">
                                                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">محمد رصایی</a>
                                                <div class="fw-semibold text-muted">f.mit@kpmg.com</div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Details-->
                                        <!--begin::Access menu-->
                                        <div class="ms-2 w-100px">
                                            <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true">
                                                <option value="1">مهمان</option>
                                                <option value="2">مدیر</option>
                                                <option value="3" selected="selected">متفرقه </option>
                                            </select>
                                        </div>
                                        <!--end::Access menu-->
                                    </div>
                                    <!--end::user-->
                                    <!--begin::separator-->
                                    <div class="border-bottom border-gray-300 border-bottom-dashed"></div>
                                    <!--end::separator-->
                                    <!--begin::user-->
                                    <div class="rounded d-flex flex-stack bg-active-lighten p-4" data-user-id="7">
                                        <!--begin::Details-->
                                        <div class="d-flex align-items-center">
                                            <!--begin::Checkbox-->
                                            <label class="form-check form-check-custom form-check-solid me-5">
                                                <input class="form-check-input" type="checkbox" name="users" data-kt-check="true" data-kt-check-target="[data-user-id='7']" value="7" />
                                            </label>
                                            <!--end::Checkbox-->
                                            <!--begin::Avatar-->
                                            <div class="symbol symbol-35px symbol-circle">
                                                <span class="symbol-label bg-light-danger text-danger fw-semibold">O</span>
                                            </div>
                                            <!--end::Avatar-->
                                            <!--begin::Details-->
                                            <div class="ms-5">
                                                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">امید وحیدی</a>
                                                <div class="fw-semibold text-muted">olivia@corpmail.com</div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Details-->
                                        <!--begin::Access menu-->
                                        <div class="ms-2 w-100px">
                                            <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true">
                                                <option value="1">مهمان</option>
                                                <option value="2" selected="selected">مدیر</option>
                                                <option value="3">متفرقه</option>
                                            </select>
                                        </div>
                                        <!--end::Access menu-->
                                    </div>
                                    <!--end::user-->
                                    <!--begin::separator-->
                                    <div class="border-bottom border-gray-300 border-bottom-dashed"></div>
                                    <!--end::separator-->
                                    <!--begin::user-->
                                    <div class="rounded d-flex flex-stack bg-active-lighten p-4" data-user-id="8">
                                        <!--begin::Details-->
                                        <div class="d-flex align-items-center">
                                            <!--begin::Checkbox-->
                                            <label class="form-check form-check-custom form-check-solid me-5">
                                                <input class="form-check-input" type="checkbox" name="users" data-kt-check="true" data-kt-check-target="[data-user-id='8']" value="8" />
                                            </label>
                                            <!--end::Checkbox-->
                                            <!--begin::Avatar-->
                                            <div class="symbol symbol-35px symbol-circle">
                                                <span class="symbol-label bg-light-primary text-primary fw-semibold">N</span>
                                            </div>
                                            <!--end::Avatar-->
                                            <!--begin::Details-->
                                            <div class="ms-5">
                                                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">محسن برومند</a>
                                                <div class="fw-semibold text-muted">owen.neil@gmail.com</div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Details-->
                                        <!--begin::Access menu-->
                                        <div class="ms-2 w-100px">
                                            <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true">
                                                <option value="1" selected="selected">مهمان</option>
                                                <option value="2">مدیر</option>
                                                <option value="3">متفرقه</option>
                                            </select>
                                        </div>
                                        <!--end::Access menu-->
                                    </div>
                                    <!--end::user-->
                                    <!--begin::separator-->
                                    <div class="border-bottom border-gray-300 border-bottom-dashed"></div>
                                    <!--end::separator-->
                                    <!--begin::user-->
                                    <div class="rounded d-flex flex-stack bg-active-lighten p-4" data-user-id="9">
                                        <!--begin::Details-->
                                        <div class="d-flex align-items-center">
                                            <!--begin::Checkbox-->
                                            <label class="form-check form-check-custom form-check-solid me-5">
                                                <input class="form-check-input" type="checkbox" name="users" data-kt-check="true" data-kt-check-target="[data-user-id='9']" value="9" />
                                            </label>
                                            <!--end::Checkbox-->
                                            <!--begin::Avatar-->
                                            <div class="symbol symbol-35px symbol-circle">
                                                <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-23.jpg') }}" />
                                            </div>
                                            <!--end::Avatar-->
                                            <!--begin::Details-->
                                            <div class="ms-5">
                                                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">علی کاربر</a>
                                                <div class="fw-semibold text-muted">dam@consilting.com</div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Details-->
                                        <!--begin::Access menu-->
                                        <div class="ms-2 w-100px">
                                            <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true">
                                                <option value="1">مهمان</option>
                                                <option value="2">مدیر</option>
                                                <option value="3" selected="selected">متفرقه </option>
                                            </select>
                                        </div>
                                        <!--end::Access menu-->
                                    </div>
                                    <!--end::user-->
                                    <!--begin::separator-->
                                    <div class="border-bottom border-gray-300 border-bottom-dashed"></div>
                                    <!--end::separator-->
                                    <!--begin::user-->
                                    <div class="rounded d-flex flex-stack bg-active-lighten p-4" data-user-id="10">
                                        <!--begin::Details-->
                                        <div class="d-flex align-items-center">
                                            <!--begin::Checkbox-->
                                            <label class="form-check form-check-custom form-check-solid me-5">
                                                <input class="form-check-input" type="checkbox" name="users" data-kt-check="true" data-kt-check-target="[data-user-id='10']" value="10" />
                                            </label>
                                            <!--end::Checkbox-->
                                            <!--begin::Avatar-->
                                            <div class="symbol symbol-35px symbol-circle">
                                                <span class="symbol-label bg-light-danger text-danger fw-semibold">E</span>
                                            </div>
                                            <!--end::Avatar-->
                                            <!--begin::Details-->
                                            <div class="ms-5">
                                                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">الهام بارانی</a>
                                                <div class="fw-semibold text-muted">emma@intenso.com</div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Details-->
                                        <!--begin::Access menu-->
                                        <div class="ms-2 w-100px">
                                            <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true">
                                                <option value="1">مهمان</option>
                                                <option value="2" selected="selected">مدیر</option>
                                                <option value="3">متفرقه</option>
                                            </select>
                                        </div>
                                        <!--end::Access menu-->
                                    </div>
                                    <!--end::user-->
                                    <!--begin::separator-->
                                    <div class="border-bottom border-gray-300 border-bottom-dashed"></div>
                                    <!--end::separator-->
                                    <!--begin::user-->
                                    <div class="rounded d-flex flex-stack bg-active-lighten p-4" data-user-id="11">
                                        <!--begin::Details-->
                                        <div class="d-flex align-items-center">
                                            <!--begin::Checkbox-->
                                            <label class="form-check form-check-custom form-check-solid me-5">
                                                <input class="form-check-input" type="checkbox" name="users" data-kt-check="true" data-kt-check-target="[data-user-id='11']" value="11" />
                                            </label>
                                            <!--end::Checkbox-->
                                            <!--begin::Avatar-->
                                            <div class="symbol symbol-35px symbol-circle">
                                                <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-12.jpg') }}" />
                                            </div>
                                            <!--end::Avatar-->
                                            <!--begin::Details-->
                                            <div class="ms-5">
                                                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">آنا کوهی</a>
                                                <div class="fw-semibold text-muted">ana.cf@limtel.com</div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Details-->
                                        <!--begin::Access menu-->
                                        <div class="ms-2 w-100px">
                                            <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true">
                                                <option value="1" selected="selected">مهمان</option>
                                                <option value="2">مدیر</option>
                                                <option value="3">متفرقه</option>
                                            </select>
                                        </div>
                                        <!--end::Access menu-->
                                    </div>
                                    <!--end::user-->
                                    <!--begin::separator-->
                                    <div class="border-bottom border-gray-300 border-bottom-dashed"></div>
                                    <!--end::separator-->
                                    <!--begin::user-->
                                    <div class="rounded d-flex flex-stack bg-active-lighten p-4" data-user-id="12">
                                        <!--begin::Details-->
                                        <div class="d-flex align-items-center">
                                            <!--begin::Checkbox-->
                                            <label class="form-check form-check-custom form-check-solid me-5">
                                                <input class="form-check-input" type="checkbox" name="users" data-kt-check="true" data-kt-check-target="[data-user-id='12']" value="12" />
                                            </label>
                                            <!--end::Checkbox-->
                                            <!--begin::Avatar-->
                                            <div class="symbol symbol-35px symbol-circle">
                                                <span class="symbol-label bg-light-info text-info fw-semibold">A</span>
                                            </div>
                                            <!--end::Avatar-->
                                            <!--begin::Details-->
                                            <div class="ms-5">
                                                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">رابرت دو</a>
                                                <div class="fw-semibold text-muted">robert@benko.com</div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Details-->
                                        <!--begin::Access menu-->
                                        <div class="ms-2 w-100px">
                                            <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true">
                                                <option value="1">مهمان</option>
                                                <option value="2">مدیر</option>
                                                <option value="3" selected="selected">متفرقه </option>
                                            </select>
                                        </div>
                                        <!--end::Access menu-->
                                    </div>
                                    <!--end::user-->
                                    <!--begin::separator-->
                                    <div class="border-bottom border-gray-300 border-bottom-dashed"></div>
                                    <!--end::separator-->
                                    <!--begin::user-->
                                    <div class="rounded d-flex flex-stack bg-active-lighten p-4" data-user-id="13">
                                        <!--begin::Details-->
                                        <div class="d-flex align-items-center">
                                            <!--begin::Checkbox-->
                                            <label class="form-check form-check-custom form-check-solid me-5">
                                                <input class="form-check-input" type="checkbox" name="users" data-kt-check="true" data-kt-check-target="[data-user-id='13']" value="13" />
                                            </label>
                                            <!--end::Checkbox-->
                                            <!--begin::Avatar-->
                                            <div class="symbol symbol-35px symbol-circle">
                                                <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-13.jpg') }}" />
                                            </div>
                                            <!--end::Avatar-->
                                            <!--begin::Details-->
                                            <div class="ms-5">
                                                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">جواد مولای</a>
                                                <div class="fw-semibold text-muted">miller@mapple.com</div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Details-->
                                        <!--begin::Access menu-->
                                        <div class="ms-2 w-100px">
                                            <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true">
                                                <option value="1">مهمان</option>
                                                <option value="2">مدیر</option>
                                                <option value="3" selected="selected">متفرقه </option>
                                            </select>
                                        </div>
                                        <!--end::Access menu-->
                                    </div>
                                    <!--end::user-->
                                    <!--begin::separator-->
                                    <div class="border-bottom border-gray-300 border-bottom-dashed"></div>
                                    <!--end::separator-->
                                    <!--begin::user-->
                                    <div class="rounded d-flex flex-stack bg-active-lighten p-4" data-user-id="14">
                                        <!--begin::Details-->
                                        <div class="d-flex align-items-center">
                                            <!--begin::Checkbox-->
                                            <label class="form-check form-check-custom form-check-solid me-5">
                                                <input class="form-check-input" type="checkbox" name="users" data-kt-check="true" data-kt-check-target="[data-user-id='14']" value="14" />
                                            </label>
                                            <!--end::Checkbox-->
                                            <!--begin::Avatar-->
                                            <div class="symbol symbol-35px symbol-circle">
                                                <span class="symbol-label bg-light-success text-success fw-semibold">L</span>
                                            </div>
                                            <!--end::Avatar-->
                                            <!--begin::Details-->
                                            <div class="ms-5">
                                                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">لقمان کامرانی</a>
                                                <div class="fw-semibold text-muted">lucy.m@fentech.com</div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Details-->
                                        <!--begin::Access menu-->
                                        <div class="ms-2 w-100px">
                                            <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true">
                                                <option value="1">مهمان</option>
                                                <option value="2" selected="selected">مدیر</option>
                                                <option value="3">متفرقه</option>
                                            </select>
                                        </div>
                                        <!--end::Access menu-->
                                    </div>
                                    <!--end::user-->
                                    <!--begin::separator-->
                                    <div class="border-bottom border-gray-300 border-bottom-dashed"></div>
                                    <!--end::separator-->
                                    <!--begin::user-->
                                    <div class="rounded d-flex flex-stack bg-active-lighten p-4" data-user-id="15">
                                        <!--begin::Details-->
                                        <div class="d-flex align-items-center">
                                            <!--begin::Checkbox-->
                                            <label class="form-check form-check-custom form-check-solid me-5">
                                                <input class="form-check-input" type="checkbox" name="users" data-kt-check="true" data-kt-check-target="[data-user-id='15']" value="15" />
                                            </label>
                                            <!--end::Checkbox-->
                                            <!--begin::Avatar-->
                                            <div class="symbol symbol-35px symbol-circle">
                                                <img alt="Pic" src="{{ asset('theme/1/media/avatars/300-21.jpg') }}" />
                                            </div>
                                            <!--end::Avatar-->
                                            <!--begin::Details-->
                                            <div class="ms-5">
                                                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">احسان ورزقانی</a>
                                                <div class="fw-semibold text-muted">ethan@loop.com.au</div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Details-->
                                        <!--begin::Access menu-->
                                        <div class="ms-2 w-100px">
                                            <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true">
                                                <option value="1" selected="selected">مهمان</option>
                                                <option value="2">مدیر</option>
                                                <option value="3">متفرقه</option>
                                            </select>
                                        </div>
                                        <!--end::Access menu-->
                                    </div>
                                    <!--end::user-->
                                    <!--begin::separator-->
                                    <div class="border-bottom border-gray-300 border-bottom-dashed"></div>
                                    <!--end::separator-->
                                    <!--begin::user-->
                                    <div class="rounded d-flex flex-stack bg-active-lighten p-4" data-user-id="16">
                                        <!--begin::Details-->
                                        <div class="d-flex align-items-center">
                                            <!--begin::Checkbox-->
                                            <label class="form-check form-check-custom form-check-solid me-5">
                                                <input class="form-check-input" type="checkbox" name="users" data-kt-check="true" data-kt-check-target="[data-user-id='16']" value="16" />
                                            </label>
                                            <!--end::Checkbox-->
                                            <!--begin::Avatar-->
                                            <div class="symbol symbol-35px symbol-circle">
                                                <span class="symbol-label bg-light-danger text-danger fw-semibold">M</span>
                                            </div>
                                            <!--end::Avatar-->
                                            <!--begin::Details-->
                                            <div class="ms-5">
                                                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">میلاد مرادی</a>
                                                <div class="fw-semibold text-muted">melody@altbox.com</div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Details-->
                                        <!--begin::Access menu-->
                                        <div class="ms-2 w-100px">
                                            <select class="form-select form-select-solid form-select-sm" data-control="select2" data-hide-search="true">
                                                <option value="1">مهمان</option>
                                                <option value="2">مدیر</option>
                                                <option value="3" selected="selected">متفرقه </option>
                                            </select>
                                        </div>
                                        <!--end::Access menu-->
                                    </div>
                                    <!--end::user-->
                                </div>
                                <!--end::users-->
                                <!--begin::Actions-->
                                <div class="d-flex flex-center mt-15">
                                    <button type="reset" id="kt_modal_users_search_reset" data-bs-dismiss="modal" class="btn btn-active-light me-3">انصراف</button>
                                    <button type="submit" id="kt_modal_users_search_submit" class="btn btn-primary">کاربران انتخاب شده را اضافه کنید</button>
                                </div>
                                <!--end::Actions-->
                            </div>
                            <!--end::Results-->
                            <!--begin::Empty-->
                            <div data-kt-search-element="empty" class="text-center d-none">
                                <!--begin::Message-->
                                <div class="fw-semibold py-10">
                                    <div class="text-gray-600 fs-3 mb-2">کاربری پیدا نشد</div>
                                    <div class="text-muted fs-6">سعی کنید با نام کاربری، نام کامل یا ایمیل جستجو کنید...</div>
                                </div>
                                <!--end::Message-->
                                <!--begin::Illustration-->
                                <div class="text-center px-5">
                                    <img src="{{ asset('theme/1/media/illustrations/sigma-1/1.png') }}" alt="" class="w-100 h-200px h-sm-325px" />
                                </div>
                                <!--end::Illustration-->
                            </div>
                            <!--end::Empty-->
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::جستجو-->
                </div>
                <!--end::Modal body-->
            </div>
            <!--end::Modal content-->
        </div>
        <!--end::Modal dialog-->
    </div>
    <!--end::Modal - Users search-->
</div>
