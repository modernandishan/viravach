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
<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">
        <livewire:dashboard-elements.infobar />
    </div>
</div>
