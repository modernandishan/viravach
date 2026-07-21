<?php

use Livewire\Component;

new class extends Component
{
    public string $heading1;
};
?>

<div class="toolbar py-5 pb-lg-15" id="kt_toolbar">
    <!--begin::Container-->
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <!--begin::Page title-->
        <div class="page-title d-flex flex-column me-3">
            <!--begin::Title-->
            <livewire:header-elements.heading1 />
            <!--end::Title-->
            <!--begin::Breadcrumb-->
            <livewire:header-elements.breadcrumb/>
            <!--end::Breadcrumb-->
        </div>
        <!--end::Page title-->
    </div>
    <!--end::Container-->
</div>
