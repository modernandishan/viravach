<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Filament\Schemas\Components\SeoMetaSection;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        $locales = config('laravellocalization.supportedLocales');

        return $schema
            ->columns(1)
            ->components([
                Section::make('اطلاعات صفحه')
                    ->schema([
                        TextInput::make('slug')
                            ->label('نامک (Slug)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('فقط انگلیسی — برای آدرس‌های سئوپسند'),
                        TextInput::make('sort_order')
                            ->label('ترتیب نمایش')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        DateTimePicker::make('published_at')
                            ->label('تاریخ انتشار')
                            ->jalali(),
                        Toggle::make('is_active')
                            ->label('فعال')
                            ->default(true),
                    ])
                    ->columns(2),

                Tabs::make('translations')
                    ->tabs(
                        collect($locales)->map(
                            fn ($data, string $code) => Tab::make($code)
                                ->label($data['native'])
                                ->schema([
                                    TextInput::make("title.{$code}")
                                        ->label('عنوان')
                                        ->required($code === config('app.fallback_locale'))
                                        ->maxLength(255),
                                ])
                        )->values()->all()
                    )
                    ->columnSpanFull(),

                SeoMetaSection::make(),
            ]);
    }
}
