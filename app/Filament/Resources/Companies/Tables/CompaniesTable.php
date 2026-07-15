<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Enums\CompanyStatus;
use App\Models\Company;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CompaniesTable
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
                    ->state(fn (Company $record) => $record->name)
                    ->searchable(false)
                    ->sortable(false),
                TextColumn::make('slug')
                    ->label('نامک (Slug)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user')
                    ->label('کاربر')
                    ->state(fn (Company $record) => trim($record->user->name.' '.$record->user->family))
                    ->searchable(false),
                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge(),
                IconColumn::make('is_verified')
                    ->label('تأیید شده')
                    ->boolean(),
                IconColumn::make('is_featured')
                    ->label('ویژه')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->jalaliDateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(CompanyStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('تأیید')
                    ->icon(Heroicon::CheckCircle)
                    ->color('success')
                    ->visible(fn (Company $record): bool => $record->status !== CompanyStatus::Approved)
                    ->authorize('update')
                    ->requiresConfirmation()
                    ->action(function (Company $record) {
                        $record->update([
                            'status' => CompanyStatus::Approved,
                            'published_at' => $record->published_at ?? now(),
                        ]);

                        Notification::make()
                            ->title('شرکت تأیید شد')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('رد')
                    ->icon(Heroicon::XCircle)
                    ->color('danger')
                    ->visible(fn (Company $record): bool => $record->status !== CompanyStatus::Rejected)
                    ->authorize('update')
                    ->schema([
                        Textarea::make('rejection_reason')
                            ->label('دلیل رد')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data, Company $record) {
                        $record->update([
                            'status' => CompanyStatus::Rejected,
                            'rejection_reason' => $data['rejection_reason'],
                        ]);

                        Notification::make()
                            ->title('شرکت رد شد')
                            ->success()
                            ->send();
                    }),
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
