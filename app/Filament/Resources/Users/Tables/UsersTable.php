<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')
                    ->label('آواتار')
                    ->collection('avatar')
                    ->conversion('webp')
                    ->visibility('public')
                    ->circular(),
                TextColumn::make('name')
                    ->label('نام و نام خانوادگی')
                    // name/family are translatable JSON, so they are resolved
                    // through the model rather than selected as raw columns.
                    ->state(fn (User $record): string => trim($record->name.' '.$record->family))
                    ->searchable(false)
                    ->sortable(false),
                TextColumn::make('email')
                    ->label('ایمیل')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('تلفن همراه')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('نقش‌ها')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("roles.{$state}")),
                TextColumn::make('companies_count')
                    ->label('تعداد شرکت‌ها')
                    ->counts('companies')
                    ->sortable(),
                IconColumn::make('email_verified_at')
                    ->label('ایمیل تأییدشده')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => $record->email_verified_at !== null),
                IconColumn::make('phone_verified_at')
                    ->label('تلفن تأییدشده')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => $record->phone_verified_at !== null),
                TextColumn::make('deactivated_at')
                    // Named for what the flag actually does. It is NOT an
                    // account suspension — the user keeps full dashboard
                    // access either way (see User::scopePubliclyVisible()).
                    ->label('نمایش عمومی')
                    ->badge()
                    ->state(fn (User $record): string => $record->deactivated_at === null ? 'نمایش داده می‌شود' : 'پنهان')
                    ->color(fn (User $record): string => $record->deactivated_at === null ? 'success' : 'gray'),
                TextColumn::make('created_at')
                    ->label('تاریخ ثبت‌نام')
                    ->jalaliDateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('roles')
                    ->label('نقش')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
                TernaryFilter::make('deactivated_at')
                    ->label('نمایش در سایت عمومی')
                    ->placeholder('همه')
                    ->trueLabel('نمایش داده می‌شود')
                    ->falseLabel('پنهان از سایت عمومی')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('deactivated_at'),
                        false: fn (Builder $query) => $query->whereNotNull('deactivated_at'),
                        blank: fn (Builder $query) => $query,
                    ),
                TernaryFilter::make('email_verified_at')
                    ->label('تأیید ایمیل')
                    ->placeholder('همه')
                    ->trueLabel('تأییدشده')
                    ->falseLabel('تأییدنشده')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('email_verified_at'),
                        false: fn (Builder $query) => $query->whereNull('email_verified_at'),
                        blank: fn (Builder $query) => $query,
                    ),
                TernaryFilter::make('phone_verified_at')
                    ->label('تأیید تلفن')
                    ->placeholder('همه')
                    ->trueLabel('تأییدشده')
                    ->falseLabel('تأییدنشده')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('phone_verified_at'),
                        false: fn (Builder $query) => $query->whereNull('phone_verified_at'),
                        blank: fn (Builder $query) => $query,
                    ),
                Filter::make('has_companies')
                    ->label('دارای شرکت')
                    ->query(fn (Builder $query): Builder => $query->whereHas('companies')),
            ])
            ->recordActions([
                EditAction::make(),
            ]);

        /*
         * No DeleteAction, DeleteBulkAction, ForceDelete or Restore here, on
         * purpose:
         *
         *  - User has no SoftDeletes, so any delete is permanent.
         *  - companies.user_id, profiles.user_id and invoices.user_id are all
         *    cascadeOnDelete, so deleting one user hard-deletes their
         *    companies (bypassing Company's own SoftDeletes, since the cascade
         *    happens in the database), their profile, and every invoice —
         *    financial records — while company_publications.company_id is
         *    nullOnDelete, leaving the public snapshots live but ownerless,
         *    and plan_subscriptions uses morphs() with no FK, leaving those
         *    rows orphaned.
         *
         * Reported rather than shipped. Deactivation is the reversible action
         * available in the form.
         */
    }
}
