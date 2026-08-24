<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<form action="#">
    <!--begin::کارت-->
    <div class="card mb-7">
        <!--begin::کارت body-->
        <div class="card-body">
            <!--begin::Compact form-->
            <div class="d-flex align-items-center">
                <!--begin::Input group-->
                <div class="position-relative w-md-400px me-md-2">
                    <i class="ki-duotone ki-magnifier fs-3 text-gray-500 position-absolute top-50 translate-middle ms-6">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <input type="text" class="form-control form-control-solid ps-10" name="search" value="" placeholder="جستجو">
                </div>
                <!--end::Input group-->
                <!--begin:عملیات-->
                <div class="d-flex align-items-center">
                    <button type="submit" class="btn btn-primary me-5">جستجو</button>
                    <a href="#" id="kt_hیاizontal_search_advanced_link" class="btn btn-link" data-bs-toggle="collapse" data-bs-target="#kt_advanced_search_form">پیشرفته جستجو</a>
                </div>
                <!--end:عملیات-->
            </div>
            <!--end::Compact form-->
            <!--begin::Advance form-->
            <div class="collapse" id="kt_advanced_search_form">
                <!--begin::separator-->
                <div class="separator separator-dashed mt-9 mb-6"></div>
                <!--end::separator-->
                <!--begin::Row-->
                <div class="row g-8 mb-8">
                    <!--begin::Col-->
                    <div class="col-xxl-7">
                        <label class="fs-6 form-label fw-bold text-gray-900">برچسب ها</label>
                        <input type="text" class="form-control form-control form-control-solid" name="tags" value="products, users, events">
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-xxl-5">
                        <!--begin::Row-->
                        <div class="row g-8">
                            <!--begin::Col-->
                            <div class="col-lg-6">
                                <label class="fs-6 form-label fw-bold text-gray-900">تیم نوع</label>
                                <!--begin::انتخاب-->
                                <select class="form-select form-select-solid select2-hidden-accessible" data-control="select2" data-placeholder="درحال پردازش" data-hide-search="true" data-select2-id="select2-data-9-faj1" tabindex="-1" aria-hidden="true" data-kt-initialized="1">
                                    <option value=""></option>
                                    <option value="1">Not started</option>
                                    <option value="2" selected="selected" data-select2-id="select2-data-11-y6rv">درحال پردازش</option>
                                    <option value="3">انجام شده</option>
                                </select><span class="select2 select2-container select2-container--bootstrap5" dir="rtl" data-select2-id="select2-data-10-sqmq" style="width: 100%;"><span class="selection"><span class="select2-selection select2-selection--single form-select form-select-solid" role="combobox" aria-haspopup="true" aria-expanded="false" tabindex="0" aria-disabled="false" aria-labelledby="select2-qpbx-container" aria-controls="select2-qpbx-container"><span class="select2-selection__rendered" id="select2-qpbx-container" role="textbox" aria-readonly="true" title="درحال پردازش">درحال پردازش</span><span class="select2-selection__arrow" role="presentation"><b role="presentation"></b></span></span></span><span class="dropdown-wrapper" aria-hidden="true"></span></span>
                                <!--end::انتخاب-->
                            </div>
                            <!--end::Col-->
                            <!--begin::Col-->
                            <div class="col-lg-6">
                                <label class="fs-6 form-label fw-bold text-gray-900">انتخاب گروه</label>
                                <!--begin::رادیو group-->
                                <div class="nav-group nav-group-fluid">
                                    <!--begin::Option-->
                                    <label>
                                        <input type="radio" class="btn-check" name="type" value="has" checked="checked">
                                        <span class="btn btn-sm btn-color-muted btn-active btn-active-primary fw-bold px-4">همه</span>
                                    </label>
                                    <!--end::Option-->
                                    <!--begin::Option-->
                                    <label>
                                        <input type="radio" class="btn-check" name="type" value="users">
                                        <span class="btn btn-sm btn-color-muted btn-active btn-active-primary fw-bold px-4">کاربران</span>
                                    </label>
                                    <!--end::Option-->
                                    <!--begin::Option-->
                                    <label>
                                        <input type="radio" class="btn-check" name="type" value="orders">
                                        <span class="btn btn-sm btn-color-muted btn-active btn-active-primary fw-bold px-4">سفارشات</span>
                                    </label>
                                    <!--end::Option-->
                                </div>
                                <!--end::رادیو group-->
                            </div>
                            <!--end::Col-->
                        </div>
                        <!--end::Row-->
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
                <!--begin::Row-->
                <div class="row g-8">
                    <!--begin::Col-->
                    <div class="col-xxl-7">
                        <!--begin::Row-->
                        <div class="row g-8">
                            <!--begin::Col-->
                            <div class="col-lg-4">
                                <label class="fs-6 form-label fw-bold text-gray-900">حداقل مقدار</label>
                                <!--begin::Dialer-->
                                <div class="position-relative" data-kt-dialer="true" data-kt-dialer-min="1000" data-kt-dialer-max="50000" data-kt-dialer-step="1000" data-kt-dialer-prefix="$" data-kt-dialer-decimals="2">
                                    <!--begin::Decrease control-->
                                    <button type="button" class="btn btn-icon btn-active-color-gray-700 position-absolute translate-middle-y top-50 start-0" data-kt-dialer-control="decrease">
                                        <i class="ki-duotone ki-minus-circle fs-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </button>
                                    <!--end::Decrease control-->
                                    <!--begin::Input control-->
                                    <input type="text" class="form-control form-control-solid border-0 ps-12" data-kt-dialer-control="input" placeholder="مقدار" name="manageبودجه" readonly="readonly" value="$50">
                                    <!--end::Input control-->
                                    <!--begin::Increase control-->
                                    <button type="button" class="btn btn-icon btn-active-color-gray-700 position-absolute translate-middle-y top-50 end-0" data-kt-dialer-control="increase">
                                        <i class="ki-duotone ki-plus-circle fs-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </button>
                                    <!--end::Increase control-->
                                </div>
                                <!--end::Dialer-->
                            </div>
                            <!--end::Col-->
                            <!--begin::Col-->
                            <div class="col-lg-4">
                                <label class="fs-6 form-label fw-bold text-gray-900">حداکثر مقدار</label>
                                <!--begin::Dialer-->
                                <div class="position-relative" data-kt-dialer="true" data-kt-dialer-min="1000" data-kt-dialer-max="50000" data-kt-dialer-step="1000" data-kt-dialer-prefix="$" data-kt-dialer-decimals="2">
                                    <!--begin::Decrease control-->
                                    <button type="button" class="btn btn-icon btn-active-color-gray-700 position-absolute translate-middle-y top-50 start-0" data-kt-dialer-control="decrease">
                                        <i class="ki-duotone ki-minus-circle fs-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </button>
                                    <!--end::Decrease control-->
                                    <!--begin::Input control-->
                                    <input type="text" class="form-control form-control-solid border-0 ps-12" data-kt-dialer-control="input" placeholder="مقدار" name="manageبودجه" readonly="readonly" value="$100">
                                    <!--end::Input control-->
                                    <!--begin::Increase control-->
                                    <button type="button" class="btn btn-icon btn-active-color-gray-700 position-absolute translate-middle-y top-50 end-0" data-kt-dialer-control="increase">
                                        <i class="ki-duotone ki-plus-circle fs-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </button>
                                    <!--end::Increase control-->
                                </div>
                                <!--end::Dialer-->
                            </div>
                            <!--end::Col-->
                            <!--begin::Col-->
                            <div class="col-lg-4">
                                <label class="fs-6 form-label fw-bold text-gray-900">تیم </label>
                                <input type="text" class="form-control form-control form-control-solid" name="city">
                            </div>
                            <!--end::Col-->
                        </div>
                        <!--end::Row-->
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-xxl-5">
                        <!--begin::Row-->
                        <div class="row g-8">
                            <!--begin::Col-->
                            <div class="col-lg-6">
                                <label class="fs-6 form-label fw-bold text-gray-900">دسته بندی</label>
                                <!--begin::انتخاب-->
                                <select class="form-select form-select-solid select2-hidden-accessible" data-control="select2" data-placeholder="درحال پردازش" data-hide-search="true" data-select2-id="select2-data-12-pq99" tabindex="-1" aria-hidden="true" data-kt-initialized="1">
                                    <option value=""></option>
                                    <option value="1">Not started</option>
                                    <option value="2" selected="selected" data-select2-id="select2-data-14-rsno">انتخاب</option>
                                    <option value="3">انجام شده</option>
                                </select><span class="select2 select2-container select2-container--bootstrap5" dir="rtl" data-select2-id="select2-data-13-5if2" style="width: 100%;"><span class="selection"><span class="select2-selection select2-selection--single form-select form-select-solid" role="combobox" aria-haspopup="true" aria-expanded="false" tabindex="0" aria-disabled="false" aria-labelledby="select2-l3q9-container" aria-controls="select2-l3q9-container"><span class="select2-selection__rendered" id="select2-l3q9-container" role="textbox" aria-readonly="true" title="انتخاب">انتخاب</span><span class="select2-selection__arrow" role="presentation"><b role="presentation"></b></span></span></span><span class="dropdown-wrapper" aria-hidden="true"></span></span>
                                <!--end::انتخاب-->
                            </div>
                            <!--end::Col-->
                            <!--begin::Col-->
                            <div class="col-lg-6">
                                <label class="fs-6 form-label fw-bold text-gray-900">وضعیت</label>
                                <div class="form-check form-switch form-check-custom form-check-solid mt-1">
                                    <input class="form-check-input" type="checkbox" value="" id="flexSwitchChecked" checked="checked">
                                    <label class="form-check-label" for="flexSwitchChecked">فعال</label>
                                </div>
                            </div>
                            <!--end::Col-->
                        </div>
                        <!--end::Row-->
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
            </div>
            <!--end::Advance form-->
        </div>
        <!--end::کارت body-->
    </div>
    <!--end::کارت-->
</form>
