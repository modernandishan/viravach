<?php

use Livewire\Component;

new class extends Component
{
    /** @var array<int, array<string, int|string>> */
    public array $states = [];

};
?>

<section class="vv-states">
    <h2>{{ __('home.states_title') }}</h2>
    <p class="vv-states-sub">{{ __('home.states_subtitle') }}</p>

    <div class="vv-states-grid">
        @foreach ($states as $state)
            <a
                href="{{ route('companies.state', ['slug' => $state['slug']]) }}"
                class="vv-state-link"
                wire:key="home-state-{{ $state['slug'] }}"
            >
                <span>{{ $state['name'] }}</span>
                <span class="vv-state-count">{{ __('home.states_count', ['count' => number_format($state['count'])]) }}</span>
            </a>
        @endforeach
    </div>
</section>

