<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts::landing')]
class extends Component {

    public function render()
    {
        return $this->view()->title(__('auth.user-dashboard') . ' | ' . __('globals.viravach'));
    }
};
?>

<div>
    {{-- Happiness is not something readymade. It comes from your own actions. - Dalai Lama --}}
</div>
