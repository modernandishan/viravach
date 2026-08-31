<?php

use App\Models\CompanyPublication;
use Livewire\Component;

new class extends Component
{
    /** @var array<int, CompanyPublication> */
    public array $companies = [];

};
?>

<section class="vv-recent">
    <h2>{{ __('home.featured_title') }}</h2>
    <p class="vv-recent-sub">{{ __('home.featured_subtitle') }}</p>

    <div class="row g-4">
        @foreach ($companies as $company)
            <div class="col-12 col-sm-6 col-lg-4" wire:key="home-company-{{ $company->id }}">
                <livewire:company-elements.company-card :company="$company"/>
            </div>
        @endforeach
    </div>
</section>

