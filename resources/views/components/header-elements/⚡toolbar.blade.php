<?php

use Livewire\Component;

new class extends Component {
    //
};
?>
<div class="topbar d-flex align-items-stretch flex-shrink-0">
    <!--begin::search-->
    {{--<livewire:header-elements.tools.search />--}}
    <!--end::search-->
    <!--begin::activities-->
    {{--<livewire:header-elements.tools.activities />--}}
    <!--end::activities-->
    <!--begin::notifications-->
    {{--<livewire:header-elements.tools.notifications />--}}
    <!--end::notifications-->
    <!--begin::chat-->
    <livewire:header-elements.tools.chat />
    <!--end::chat-->
    <!--begin::Quick links-->
    {{--<livewire:header-elements.tools.quick-links />--}}
    <!--end::Quick links-->
    <!--begin::Theme mode-->
    <livewire:header-elements.tools.theme-mode />
    <!--end::Theme mode-->
    <!--begin::Language switcher-->
    <livewire:header-elements.tools.language-switcher />
    <!--end::Language switcher-->
    <!--begin::User Access-->
    <livewire:header-elements.tools.user-access />
    <!--end::User Access -->

    <!--begin::side mobile toggle-->
    <!--end::side mobile toggle-->
</div>
