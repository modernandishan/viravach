<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="header-logo me-5 me-md-10 flex-grow-1 flex-lg-grow-0">
    <a href="{{route('home')}}">
        <img alt="Logo" src="{{asset('theme/1/media/logos/ViraVach-logo-1.png')}}" class="logo-default h-25px" />
        <img alt="Logo" src="{{asset('theme/1/media/logos/ViraVach-logo-2.png')}}" class="logo-sticky h-25px" />
    </a>
</div>
