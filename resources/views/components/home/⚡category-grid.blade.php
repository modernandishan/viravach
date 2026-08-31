<?php

use Livewire\Component;

new class extends Component
{
    /** @var array<int, array<int|string, mixed>> */
    public array $categories = [];

};
?>

<section class="vv-section">
    <h2>{{ __('home.categories_title') }}</h2>
    <p class="vv-section-sub">{{ __('home.categories_subtitle') }}</p>

    <div class="vv-tile-grid">
        @foreach ($categories as $category)
            <a
                href="{{ route('companies.category', ['slug' => $category['slug']]) }}"
                class="vv-tile"
                wire:key="home-category-{{ $category['slug'] }}"
            >
                @if (! empty($category['icon']))
                    <span class="vv-cat-icon">
                        <img src="{{ $category['icon'] }}" alt="{{ $category['title'] }}" loading="lazy">
                    </span>
                @else
                    <span class="vv-cat-icon vv-cat-icon-fallback" role="img" aria-label="{{ $category['title'] }}">
                        <i class="ki-duotone ki-abstract-26"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                @endif
                <span class="vv-tile-title">{{ $category['title'] }}</span>
                <span class="vv-tile-count">{{ __('home.categories_count', ['count' => number_format($category['count'])]) }}</span>
            </a>
        @endforeach
    </div>
</section>

