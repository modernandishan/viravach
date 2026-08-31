<?php

use App\Models\Page;
use Livewire\Component;

new class extends Component
{
    public Page $page;

};
?>

<section class="vv-hero">
    <h1>{{ $page->getTranslation('h1', app()->getLocale()) }}</h1>

    @if (trim((string) $page->getTranslation('subheading', app()->getLocale())) !== '')
        <p class="vv-hero-subheading">{{ $page->getTranslation('subheading', app()->getLocale()) }}</p>
    @endif

    {{-- TODO: the search form's submit is a no-op (home.advance-search's
         search() already carries the TODO for the future search results
         route) — nothing to point the action at yet, and the form is
         non-breaking by design. --}}
    <livewire:home.advance-search/>
</section>

