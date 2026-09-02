<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts::landing')]
class extends Component {
    public function mount(): void
    {
        //
    }

    public function render()
    {
        return $this->view()->title(__('countries.title').' | '.__('globals.viravach'));
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">

        {{-- The toolbar (⚡after-menu) renders the breadcrumb for this route. --}}
        <div class="card mb-6 mb-xl-9">
            <div class="card-body">
                <h1 class="fs-2 fw-bold text-gray-900 mb-0">{{ __('countries.title') }}</h1>
            </div>
        </div>

        {{-- Country listing: the globe component's server-rendered list is
             the directory index — real <a> links with company counts, not
             hidden from crawlers. The dataset (active countries + counts +
             localized URLs) lives in that component's cached computed, so
             the query is not duplicated here. --}}
        <div style="max-inline-size: 720px; margin-inline: auto;">
            <livewire:maps.world-globe/>
        </div>

    </div>
</div>
