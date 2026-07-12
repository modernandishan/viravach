<?php

namespace App\Filament\Resources\Countries\Tables;

use App\Models\Country;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CountriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('flag')
                    ->collection('flag')
                    ->conversion('webp')
                    ->visibility('public')
                    ->label('پرچم')
                    ->circular(),
                TextColumn::make('flag_emoji')
                    ->label('اموجی پرچم'),
                TextColumn::make('name')
                    ->label('نام')
                    ->state(fn ($record) => $record->name)
                    ->searchable(false)
                    ->sortable(false),
                TextColumn::make('slug')
                    ->label('نامک (Slug)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('iso2')
                    ->label('ISO2')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('iso3')
                    ->label('ISO3')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('region')
                    ->label('منطقه')
                    ->sortable(),
                TextColumn::make('phone_code')
                    ->label('پیش‌شماره تلفن'),
                IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->jalaliDateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('slug')
            ->filters([
                SelectFilter::make('region')
                    ->label('منطقه')
                    ->options(fn () => Country::query()
                        ->whereNotNull('region')
                        ->distinct()
                        ->pluck('region', 'region')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
