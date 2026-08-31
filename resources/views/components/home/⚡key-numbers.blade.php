<?php

use Livewire\Component;

new class extends Component
{
    /** @var array{companies: int, categories: int, states: int, countries: int} */
    public array $numbers = ['companies' => 0, 'categories' => 0, 'states' => 0, 'countries' => 0];

};
?>

<section class="vv-numbers">
    <h2>{{ __('home.key_numbers_title') }}</h2>

    <div class="vv-numbers-grid">
        <div class="vv-number">
            <span class="vv-number-value">{{ number_format($numbers['companies']) }}</span>
            <span class="vv-number-label">{{ __('home.stats_companies') }}</span>
        </div>
        <div class="vv-number">
            <span class="vv-number-value">{{ number_format($numbers['categories']) }}</span>
            <span class="vv-number-label">{{ __('home.stats_categories') }}</span>
        </div>
        <div class="vv-number">
            <span class="vv-number-value">{{ number_format($numbers['states']) }}</span>
            <span class="vv-number-label">{{ __('home.stats_states') }}</span>
        </div>
        <div class="vv-number">
            <span class="vv-number-value">{{ number_format($numbers['countries']) }}</span>
            <span class="vv-number-label">{{ __('home.stats_export_countries') }}</span>
        </div>
    </div>
</section>

