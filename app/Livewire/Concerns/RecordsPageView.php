<?php

namespace App\Livewire\Concerns;

use App\Models\Page;
use Illuminate\Support\Facades\Cache;

trait RecordsPageView
{
    protected function recordPageView(Page $page): void
    {
        $cooldownKey = "page-view-cooldown:{$page->getKey()}:".request()->session()->getId();

        if (Cache::has($cooldownKey)) {
            return;
        }

        views($page)->record();

        Cache::put($cooldownKey, true, now()->addMinutes(60));
    }
}
