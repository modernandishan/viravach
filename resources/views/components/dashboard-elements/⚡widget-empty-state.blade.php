<?php

use Livewire\Component;

/**
 * Widget 0 — first run. The dashboard page renders this INSTEAD of the whole
 * widget set when the user owns no companies: every other widget would
 * otherwise be an empty box, and five empty boxes say less than one clear
 * next step. Owns no query of its own; the page already knows the count.
 */
new class extends Component
{
    //
};
?>

<div class="card card-flush">
    <div class="card-body py-20">
        <div class="vv-dash-empty">
            <i class="ki-duotone ki-briefcase vv-dash-empty-icon">
                <span class="path1"></span><span class="path2"></span>
            </i>
            <h2 class="vv-dash-empty-title">{{ __('dashboard.empty_no_companies_title') }}</h2>
            <p class="vv-dash-empty-body">{{ __('dashboard.empty_no_companies_body') }}</p>
            <a href="{{ route('create.company') }}" class="btn btn-primary">
                {{ __('dashboard.empty_no_companies_cta') }}
            </a>
        </div>
    </div>
</div>
