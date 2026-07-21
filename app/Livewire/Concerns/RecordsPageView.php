<?php

namespace App\Livewire\Concerns;

use CyrildeWit\EloquentViewable\Contracts\Viewable;
use Illuminate\Support\Facades\Cache;

trait RecordsPageView
{
    protected function recordPageView(Viewable $viewable): void
    {
        // Livewire::test() mounts components directly, without the
        // StartSession middleware that a real HTTP request goes through, so
        // request()->session() isn't always available. Fall back to the IP
        // for the cooldown discriminator in that case.
        $visitor = request()->hasSession() ? request()->session()->getId() : request()->ip();

        $cooldownKey = 'page-view-cooldown:'.get_class($viewable).":{$viewable->getKey()}:{$visitor}";

        if (Cache::has($cooldownKey)) {
            return;
        }

        views($viewable)->record();

        Cache::put($cooldownKey, true, now()->addMinutes(60));
    }
}
