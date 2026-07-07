<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageGeneralSettings extends SettingsPage
{
    protected static string $settings = GeneralSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = "تنظیمات عمومی";

    protected static ?string $title = "تنظیمات عمومی";

    protected static string|UnitEnum|null $navigationGroup = "تنظیمات";

    public function form(Schema $schema): Schema
    {
        $locales = config("laravellocalization.supportedLocales");

        $orderedLocaleCodes = collect([
            config("app.locale"),
            "fa",
            "ar",
            "ru",
            "tr",
        ])
            ->unique()
            ->filter(
                fn(string $localeCode): bool => array_key_exists(
                    $localeCode,
                    $locales,
                ),
            )
            ->values();

        return $schema->columns(1)->components([
            Tabs::make("site_name")->tabs(
                $orderedLocaleCodes
                    ->map(
                        fn(string $localeCode): Tab => Tab::make($localeCode)
                            ->label($locales[$localeCode]["native"])
                            ->schema([
                                TextInput::make("site_name.{$localeCode}")
                                    ->label("نام سایت")
                                    ->required(
                                        $localeCode ===
                                            config("app.fallback_locale"),
                                    )
                                    ->maxLength(255),
                            ]),
                    )
                    ->all(),
            ),
        ]);
    }
}
