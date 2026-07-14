<?php

namespace App\Filament\Resources\Plans\Tables;

use App\Models\Plan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('image')
                    ->conversion('webp')
                    ->visibility('public')
                    ->label('تصویر')
                    ->square(),
                TextColumn::make('name')
                    ->label('نام')
                    ->state(fn (Plan $record) => $record->name)
                    ->searchable(false)
                    ->sortable(false),
                TextColumn::make('slug')
                    ->label('نامک (Slug)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('قیمت')
                    ->formatStateUsing(fn (Plan $record) => $record->isFree()
                        ? 'رایگان'
                        : number_format((float) $record->price, 2).' '.$record->currency)
                    ->sortable(),
                IconColumn::make('trial_period')
                    ->label('دوره آزمایشی')
                    ->boolean()
                    ->state(fn (Plan $record) => $record->hasTrial()),
                IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('ترتیب نمایش')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->jalaliDateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('فعال'),
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
