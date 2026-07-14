<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="row g-3 mb-9">
    <!--begin::Col-->
    <div class="col-md-6">
        <!--begin::گوگل link=-->
        <a href="#"
           class="btn btn-flex btn-outline btn-text-gray-700 btn-active-color-primary bg-state-light flex-center text-nowrap w-100">
            <img alt="Logo" src="{{asset('theme/1/media/svg/brand-logos/google-icon.svg')}}"
                 class="h-15px me-3"/>ورود از طریق گوگل</a>
        <!--end::گوگل link=-->
    </div>
    <!--end::Col-->
    <!--begin::Col-->
    <div class="col-md-6">
        <!--begin::گوگل link=-->
        <a href="#"
           class="btn btn-flex btn-outline btn-text-gray-700 btn-active-color-primary bg-state-light flex-center text-nowrap w-100">
            <img alt="Logo" src="{{asset('theme/1/media/svg/brand-logos/apple-black.svg')}}"
                 class="theme-light-show h-15px me-3"/>
            <img alt="Logo" src="{{asset('theme/1/media/svg/brand-logos/apple-black-dark.svg')}}"
                 class="theme-dark-show h-15px me-3"/>با اپلیکیشن وارد شوید</a>
        <!--end::گوگل link=-->
    </div>
    <!--end::Col-->
</div>
