<?php
// resources/views/components/header-elements/⚡heading1.blade.php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Livewire\Component;

new class extends Component
{
    /**
     * Pages that render their own <h1> in the body — the toolbar must not
     * emit a second one (two h1 elements on a page is an SEO fault). Other
     * pages rely on this toolbar heading as their only h1, so it stays.
     */
    private const SELF_HEADING_ROUTES = ['companies.show', 'home', 'companies.countries', 'companies.country', 'companies.state'];

    public function with(): array
    {
        $routeName = request()->route()?->getName();

        return [
            'title' => match (true) {
                $routeName === 'home' => __('breadcrumbs.home_headline'),
                $routeName && Breadcrumbs::exists($routeName) => Breadcrumbs::generate($routeName, ...array_values(request()->route()->parameters()))->last()?->title,
                default => config('app.name'),
            },
            'demoted' => in_array($routeName, self::SELF_HEADING_ROUTES, true),
        ];
    }
};
?>

@php $tag = $demoted ? 'span' : 'h1'; @endphp

<{{ $tag }} class="d-flex text-white fw-bold my-1 fs-3">
    {{ $title }}
</{{ $tag }}>
