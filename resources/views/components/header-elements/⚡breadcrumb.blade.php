<?php
// resources/views/components/header-elements/⚡breadcrumbs.blade.php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Livewire\Component;

new class extends Component
{
    public function with(): array
    {
        $routeName = request()->route()?->getName();

        $breadcrumbs = ($routeName && Breadcrumbs::exists($routeName))
            ? Breadcrumbs::generate($routeName, ...array_values(request()->route()->parameters()))
            : collect();

        return [
            // مسیر تک‌عضوی ارزش نمایشی ندارد → خالی برگردان
            'breadcrumbs' => $breadcrumbs->count() > 1 ? $breadcrumbs : collect(),
        ];
    }
};
?>

<ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-1 {{ $breadcrumbs->isEmpty() ? 'd-none' : '' }}">
    @foreach ($breadcrumbs as $breadcrumb)
        <li class="breadcrumb-item text-white opacity-75">
            @if ($breadcrumb->url && !$loop->last)
                <a href="{{ $breadcrumb->url }}" class="text-white text-hover-primary">{{ $breadcrumb->title }}</a>
            @else
                {{ $breadcrumb->title }}
            @endif
        </li>

        @unless ($loop->last)
            <li class="breadcrumb-item">
                <span class="bullet bg-white opacity-75 w-5px h-2px"></span>
            </li>
        @endunless
    @endforeach
</ul>
