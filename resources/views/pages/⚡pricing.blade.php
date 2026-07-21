<?php

use App\Models\Plan;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts::landing')]
class extends Component
{
    #[Computed]
    public function plans(): Collection
    {
        return Plan::query()->where('is_active', true)->orderBy('sort_order')->with('features')->get();
    }

    public function render()
    {
        return $this->view()->title(__('subscriptions.pricing_page_title').' | '.__('globals.viravach'));
    }
};
?>

<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid">
        <livewire:subscription-elements.plan-comparison-table />

    </div>
</div>
