<?php

use App\Models\Company;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts::landing')]
class extends Component {
    /**
     * The only query this page owns. Every widget below is self-contained
     * and fetches its own data; this decides between the first-run state and
     * the full set, which is a page-level layout decision rather than any
     * one widget's concern.
     */
    #[Computed]
    public function hasCompanies(): bool
    {
        return Company::query()->where('user_id', auth()->id())->exists();
    }

    public function render()
    {
        return $this->view()->title(__('auth.user-dashboard') . ' | ' . __('globals.viravach'));
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">
        <livewire:dashboard-elements.infobar />

        @if (! $this->hasCompanies)
            {{-- Widget 0 replaces the whole set: with no companies every other
                 widget would be an empty box, and five empty boxes say less
                 than one clear next step. --}}
            <livewire:dashboard-elements.widget-empty-state />
        @else
            {{-- Bootstrap's grid mirrors under RTL on its own, so the row/col
                 structure needs no directional overrides. g-6 keeps the
                 24px gutter DESIGN.md §4 specifies between cards. --}}
            <div class="row g-6">
                <div class="col-12 col-xl-8">
                    <livewire:dashboard-elements.widget-views-chart />
                </div>
                <div class="col-12 col-xl-4">
                    <livewire:dashboard-elements.widget-top-companies />
                </div>

                <div class="col-12 col-lg-6">
                    <livewire:dashboard-elements.widget-companies />
                </div>
                <div class="col-12 col-lg-6">
                    <livewire:dashboard-elements.widget-subscriptions />
                </div>

                <div class="col-12">
                    <livewire:dashboard-elements.widget-invoices />
                </div>
            </div>
        @endif
    </div>
</div>
