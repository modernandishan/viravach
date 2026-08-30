<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Enums\CompanyContentStatus;
use App\Enums\CompanyReviewStatus;
use App\Models\Company;
use App\Services\Ai\ContentGenerationService;
use App\Services\CompanyPublicationService;
use App\Settings\ContentSettings;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                TextColumn::make('content_status')
                    ->label('وضعیت محتوا')
                    ->badge()
                    ->state(fn (Company $record) => $record->contentRecord?->status?->getLabel() ?? 'بدون محتوا')
                    ->color(fn (Company $record) => $record->contentRecord?->status?->getColor() ?? 'gray'),
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
                SelectFilter::make('content_status')
                    ->label('وضعیت محتوا')
                    ->options(['none' => 'بدون محتوا'] + collect(CompanyContentStatus::cases())
                        ->mapWithKeys(fn (CompanyContentStatus $status) => [$status->value => $status->getLabel()])
                        ->all())
                    ->query(function (Builder $query, array $data): void {
                        $status = $data['value'] ?? null;

                        if (blank($status)) {
                            return;
                        }

                        if ($status === 'none') {
                            $query->whereDoesntHave('contentRecord');

                            return;
                        }

                        $query->whereHas('contentRecord', fn (Builder $query) => $query->where('status', $status));
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                static::approveAction(),
                static::rejectAction(),
                static::republishAction(),
                static::generateContentAction(),
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
                ]);

                app(CompanyPublicationService::class)->publish($record);

                Notification::make()
                    ->title('شرکت تأیید و منتشر شد')
                    ->success()
                    ->send();
            });
    }

    /**
     * Republishing replaces the live snapshot with the company's CURRENT
     * data (including AI-generated content and regenerated SEO). Guarded by
     * the same Approve:Company Shield permission as the approve action, via
     * closure-free ->authorize('approve') so shield:generate --all cannot
     * break it.
     */
    public static function republishAction(): Action
    {
        return Action::make('republish')
            ->label('انتشار مجدد')
            ->icon(Heroicon::ArrowPath)
            ->color('warning')
            ->visible(fn (Company $record): bool => $record->publication()->exists()
                && $record->review_status === CompanyReviewStatus::Approved)
            ->authorize('approve')
            ->requiresConfirmation()
            ->modalHeading('انتشار مجدد شرکت')
            ->modalDescription('نسخه منتشرشده با اطلاعات فعلی جایگزین می‌شود. این کار برگشت‌پذیر نیست.')
            ->action(function (Company $record) {
                $publication = app(CompanyPublicationService::class)->publish($record);

                Notification::make()
                    ->title('نسخه عمومی با موفقیت جایگزین شد')
                    ->body(__('companies.publication_slug', ['slug' => $publication->slug]))
                    ->success()
                    ->send();
            });
    }

    /**
     * Manual (re)generation trigger. Authorization mirrors the approve
     * action (same Approve:Company permission) via a closure, so it does
     * not depend on shield-generated policy stubs. Reports WHY a request
     * was rejected: feature disabled, run in progress, or unchanged input.
     */
    public static function generateContentAction(): Action
    {
        return Action::make('generate_content')
            ->label('تولید محتوا')
            ->icon(Heroicon::Sparkles)
            ->color('info')
            ->authorize(fn (Company $record): bool => auth()->user()?->can('Approve:Company') ?? false)
            ->requiresConfirmation()
            ->modalHeading('تولید محتوا')
            ->modalDescription('درخواست تولید/بازتولید محتوای هوش مصنوعی برای این شرکت ارسال می‌شود.')
            ->action(function (Company $record) {
                $service = app(ContentGenerationService::class);

                if (! app(ContentSettings::class)->enabled) {
                    Notification::make()
                        ->title('تولید محتوا انجام نشد')
                        ->body('تولید محتوا در تنظیمات غیرفعال است.')
                        ->warning()
                        ->send();

                    return;
                }

                if ($service->request($record)) {
                    Notification::make()
                        ->title('تولید محتوا آغاز شد')
                        ->body('شرکت به صف تولید محتوا اضافه شد.')
                        ->success()
                        ->send();

                    return;
                }

                $reason = $record->contentRecord?->status?->isProcessing()
                    ? 'تولید محتوای این شرکت هم‌اکنون در جریان است.'
                    : 'محتوای ذخیره‌شده با آخرین ورودی‌ها یکسان است؛ نیازی به تولید مجدد نیست.';

                Notification::make()
                    ->title('تولید محتوا انجام نشد')
                    ->body($reason)
                    ->warning()
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
            ->requiresConfirmation()
            ->modalHeading('رد شرکت')
            ->modalDescription('با رد، پیش‌نویس فعلی به وضعیت «رد شده» می‌رود؛ نسخه منتشرشده قبلی (در صورت وجود) همچنان نمایش داده می‌شود.')
            ->action(function (Company $record) {
                $record->update([
                    'review_status' => CompanyReviewStatus::Rejected,
                    'reviewed_at' => now(),
                ]);

                Notification::make()
                    ->title('شرکت رد شد')
                    ->success()
                    ->send();
            });
    }
}
