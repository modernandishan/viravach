<?php

namespace App\Filament\Resources\Countries\Schemas;

use App\Filament\Schemas\Components\SeoMetaSection;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        $locales = config('laravellocalization.supportedLocales');

        return $schema
            ->columns(1)
            ->components([
                Section::make('اطلاعات پایه')
                    ->schema([
                        TextInput::make('slug')
                            ->label('نامک (Slug)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('فقط انگلیسی — برای آدرس‌های سئوپسند'),
                        TextInput::make('iso2')
                            ->label('کد ISO2')
                            ->length(2)
                            ->unique(ignoreRecord: true),
                        TextInput::make('iso3')
                            ->label('کد ISO3')
                            ->length(3)
                            ->unique(ignoreRecord: true),
                        TextInput::make('numeric_code')
                            ->label('کد عددی')
                            ->length(3)
                            ->unique(ignoreRecord: true),
                        TextInput::make('phone_code')
                            ->label('پیش‌شماره تلفن')
                            ->required()
                            ->maxLength(10),
                        Toggle::make('is_active')
                            ->label('فعال')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('واحد پول')
                    ->schema([
                        TextInput::make('currency')
                            ->label('کد واحد پول')
                            ->required()
                            ->length(3),
                        TextInput::make('currency_symbol')
                            ->label('نماد واحد پول')
                            ->required()
                            ->maxLength(10),
                    ])
                    ->columns(2),

                Section::make('اطلاعات جغرافیایی')
                    ->schema([
                        TextInput::make('tld')
                            ->label('دامنه (TLD)')
                            ->maxLength(20),
                        TextInput::make('region')
                            ->label('منطقه')
                            ->maxLength(100),
                        TextInput::make('subregion')
                            ->label('زیرمنطقه')
                            ->maxLength(100),
                        TextInput::make('latitude')
                            ->label('عرض جغرافیایی')
                            ->numeric(),
                        TextInput::make('longitude')
                            ->label('طول جغرافیایی')
                            ->numeric(),
                        TextInput::make('area')
                            ->label('مساحت (کیلومتر مربع)')
                            ->numeric(),
                        TextInput::make('population')
                            ->label('جمعیت')
                            ->numeric(),
                        Textarea::make('bounding_box')
                            ->label('محدوده مرزی (JSON)')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('به‌صورت JSON وارد شود')
                            ->afterStateHydrated(fn ($component, $state) => $component->state(
                                is_array($state) ? json_encode($state) : $state
                            ))
                            ->dehydrateStateUsing(fn (?string $state) => $state ? json_decode($state, true) : null),
                    ])
                    ->columns(2),

                Section::make('پرچم')
                    ->schema([
                        TextInput::make('flag_emoji')
                            ->label('اموجی پرچم')
                            ->maxLength(10),
                        SpatieMediaLibraryFileUpload::make('flag')
                            ->label('فایل پرچم')
                            ->collection('flag')
                            ->disk('s3')
                            ->visibility('public')
                            ->image()
                            ->imageEditor(),
                    ])
                    ->columns(2),

                Tabs::make('translations')
                    ->tabs(
                        collect($locales)->map(
                            fn ($data, string $code) => Tab::make($code)
                                ->label($data['native'])
                                ->schema([
                                    TextInput::make("name.{$code}")
                                        ->label('نام')
                                        ->required($code === config('app.fallback_locale'))
                                        ->maxLength(255),
                                    TextInput::make("official_name.{$code}")
                                        ->label('نام رسمی')
                                        ->required($code === config('app.fallback_locale'))
                                        ->maxLength(255),
                                    TextInput::make("capital.{$code}")
                                        ->label('پایتخت')
                                        ->maxLength(255),
                                    TextInput::make("currency_name.{$code}")
                                        ->label('نام واحد پول')
                                        ->maxLength(255),
                                ])
                                ->columns(2)
                        )->values()->all()
                    )
                    ->columnSpanFull(),

                SeoMetaSection::make(),
            ]);
    }
}
