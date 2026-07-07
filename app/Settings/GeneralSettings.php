<?php

namespace App\Settings;

use Illuminate\Support\Arr;
use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{

    public array $site_name;

    public static function group(): string
    {
        return "general";
    }

    public function getSiteName(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->site_name[$locale] ??
            ($this->site_name[config("app.fallback_locale")] ??
                Arr::first($this->site_name));
    }
}
