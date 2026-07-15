<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Models\City;
use App\Models\CompanyAddress;
use App\Models\Country;
use App\Models\State;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'آدرس‌ها';

    public function form(Schema $schema): Schema
    {
        $locales = config('laravellocalization.supportedLocales');

        return $schema
            ->columns(2)
            ->components([
                Select::make('country_id')
                    ->label('کشور')
                    ->relationship('country', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Country $record): string => $record->name)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),
                Select::make('state_id')
                    ->label('استان')
                    ->relationship('state', 'name')
                    ->getOptionLabelFromRecordUsing(fn (State $record): string => $record->name)
                    ->searchable()
                    ->preload()
                    ->native(false),
                Select::make('city_id')
                    ->label('شهر')
                    ->relationship('city', 'name')
                    ->getOptionLabelFromRecordUsing(fn (City $record): string => $record->name)
                    ->searchable()
                    ->preload()
                    ->native(false),
                Select::make('type')
                    ->label('نوع')
                    ->options([
                        'office' => 'دفتر',
                        'warehouse' => 'انبار',
                        'factory' => 'کارخانه',
                        'showroom' => 'نمایشگاه',
                    ])
                    ->default('office')
                    ->native(false)
                    ->required(),
                TextInput::make('postal_code')
                    ->label('کد پستی')
                    ->maxLength(255),
                Toggle::make('is_primary')
                    ->label('آدرس اصلی'),
                TextInput::make('latitude')
                    ->label('عرض جغرافیایی')
                    ->numeric(),
                TextInput::make('longitude')
                    ->label('طول جغرافیایی')
                    ->numeric(),

                Tabs::make('translations')
                    ->tabs(
                        collect($locales)->map(
                            fn ($data, string $code) => Tab::make($code)
                                ->label($data['native'])
                                ->schema([
                                    TextInput::make("address_line.{$code}")
                                        ->label('آدرس')
                                        ->required($code === config('app.fallback_locale'))
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                ])
                        )->values()->all()
                    )
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('address_line')
            ->columns([
                TextColumn::make('address_line')
                    ->label('آدرس')
                    ->state(fn (CompanyAddress $record) => $record->address_line)
                    ->searchable(false)
                    ->limit(50),
                TextColumn::make('country.name')
                    ->label('کشور')
                    ->state(fn (CompanyAddress $record) => $record->country?->name),
                TextColumn::make('state.name')
                    ->label('استان')
                    ->state(fn (CompanyAddress $record) => $record->state?->name)
                    ->placeholder('—'),
                TextColumn::make('city.name')
                    ->label('شهر')
                    ->state(fn (CompanyAddress $record) => $record->city?->name)
                    ->placeholder('—'),
                TextColumn::make('type')
                    ->label('نوع')
                    ->badge(),
                IconColumn::make('is_primary')
                    ->label('اصلی')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
