<?php
// resources/views/components/header-elements/⚡heading1.blade.php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Livewire\Component;

new class extends Component
{
    public function with(): array
    {
        $routeName = request()->route()?->getName();

        return [
            'title' => match (true) {
                $routeName === 'home' => __('breadcrumbs.home_headline'),
                $routeName && Breadcrumbs::exists($routeName) => Breadcrumbs::generate($routeName, ...array_values(request()->route()->parameters()))->last()?->title,
                default => config('app.name'),
            },
        ];
    }
};
?>

<h1 class="d-flex text-white fw-bold my-1 fs-3">
    {{ $title }}
</h1>
