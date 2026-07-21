<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Enums\CompanyReviewStatus;
use App\Models\Company;
use App\Services\CompanyPublicationService;
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
                TextColumn::make('review_status')
                    ->label('وضعیت بررسی')
                    ->badge(),
                TextColumn::make('plan')
                    ->label('پلن')
                    ->state(fn (Company $record) => $record->activeSubscription()?->plan?->name ?? '—'),
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
                SelectFilter::make('review_status')
                    ->label('وضعیت بررسی')
                    ->options(CompanyReviewStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                static::approveAction(),
                static::rejectAction(),
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

    /**
     * Approving stamps the review fields and (re)publishes the public
     * snapshot. Guarded by the Approve:Company Shield permission via
     * CompanyPolicy::approve().
     */
    public static function approveAction(): Action
    {
        return Action::make('approve')
            ->label('تأیید')
            ->icon(Heroicon::CheckCircle)
            ->color('success')
            ->visible(fn (Company $record): bool => $record->review_status === CompanyReviewStatus::PendingReview)
            ->authorize('approve')
            ->requiresConfirmation()
            ->modalHeading('تأیید شرکت')
            ->modalDescription('با تأیید، نسخه فعلی شرکت به‌عنوان نسخه عمومی منتشر می‌شود.')
            ->action(function (Company $record) {
                $record->update([
                    'review_status' => CompanyReviewStatus::Approved,
                    'reviewed_at' => now(),
                    'rejection_reason' => null,
                ]);

                app(CompanyPublicationService::class)->publish($record);

                Notification::make()
                    ->title('شرکت تأیید و منتشر شد')
                    ->success()
                    ->send();
            });
    }

    /**
     * Rejecting only flags the draft; an already published snapshot keeps
     * serving publicly. Guarded by the Reject:Company Shield permission via
     * CompanyPolicy::reject().
     */
    public static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('رد')
            ->icon(Heroicon::XCircle)
            ->color('danger')
            ->visible(fn (Company $record): bool => $record->review_status === CompanyReviewStatus::PendingReview)
            ->authorize('reject')
            ->schema([
                Textarea::make('rejection_reason')
                    ->label('دلیل رد')
                    ->required()
                    ->rows(3),
            ])
            ->action(function (array $data, Company $record) {
                $record->update([
                    'review_status' => CompanyReviewStatus::Rejected,
                    'reviewed_at' => now(),
                    'rejection_reason' => $data['rejection_reason'],
                ]);

                Notification::make()
                    ->title('شرکت رد شد')
                    ->success()
                    ->send();
            });
    }
}
