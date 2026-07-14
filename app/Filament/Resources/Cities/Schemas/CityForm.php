<?php

namespace App\Filament\Resources\Cities\Schemas;

use App\Models\State;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class CityForm
{
    public static function configure(Schema $schema): Schema
    {
        $locales = config('laravellocalization.supportedLocales');

        return $schema
            ->columns(1)
            ->components([
                Section::make('اطلاعات پایه')
                    ->schema([
                        Select::make('state_id')
                            ->label('استان')
                            ->relationship('state', 'name')
                            ->getOptionLabelFromRecordUsing(fn (State $record): string => $record->name)
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required(),
                        TextInput::make('slug')
                            ->label('نامک (Slug)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('فقط انگلیسی — برای آدرس‌های سئوپسند'),
                        TextInput::make('latitude')
                            ->label('عرض جغرافیایی')
                            ->numeric(),
                        TextInput::make('longitude')
                            ->label('طول جغرافیایی')
                            ->numeric(),
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
                                    TextInput::make("name.{$code}")
                                        ->label('نام')
                                        ->required($code === config('app.fallback_locale'))
                                        ->maxLength(255),
                                ])
                                ->columns(2)
                        )->values()->all()
                    )
                    ->columnSpanFull(),
            ]);
    }
}
