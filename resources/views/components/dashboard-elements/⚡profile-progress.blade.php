<?php

use Livewire\Component;

new class extends Component
{
    public function percentage(): int
    {
        return auth()->user()->profileCompletionPercentage();
    }
};
?>

<div class="d-flex align-items-center w-200px w-sm-300px flex-column mt-3">
    <div class="d-flex justify-content-between w-100 mt-auto mb-2">
        <span class="fw-semibold fs-6 text-gray-500">
            {{ __('menu.complete_profile') }}
        </span>
        <span class="fw-bold fs-6">{{ $this->percentage() }}%</span>
    </div>
    <div class="h-5px mx-3 w-100 bg-light mb-3">
        <div class="bg-success rounded h-5px" role="progressbar" style="width: {{ $this->percentage() }}%;" aria-valuenow="{{ $this->percentage() }}" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
</div>
