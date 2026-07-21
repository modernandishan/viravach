<?php

namespace App\Filament\Resources\CompanyPublications\Tables;

use App\Models\CompanyPublication;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompanyPublicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('logo')
                    ->collection('logo')
                    ->conversion('webp')
                    ->visibility('public')
                    ->label('لوگو')
                    ->circular(),
                TextColumn::make('name')
                    ->label('نام')
                    ->state(fn (CompanyPublication $record) => $record->name)
                    ->searchable(false)
                    ->sortable(false),
                TextColumn::make('slug')
                    ->label('نامک (Slug)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company.slug')
                    ->label('شرکت مبدأ')
                    ->placeholder('حذف‌شده'),
                IconColumn::make('is_verified')
                    ->label('تأیید شده')
                    ->boolean(),
                IconColumn::make('is_featured')
                    ->label('ویژه')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label('تاریخ انتشار')
                    ->jalaliDateTime()
                    ->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
