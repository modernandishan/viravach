<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Enums\CompanyContentStatus;
use App\Enums\CompanyReviewStatus;
use App\Jobs\Ai\GenerateFeaturedImage;
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
use Laravelcm\Subscriptions\Models\Subscription;

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
                static::resetContentQuotaAction(),
                static::allowContentRegenerationAction(),
                static::generateFeaturedImageAction(),
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

                $reason = match (true) {
                    $service->alreadyGenerated($record) => 'این شرکت پیش‌تر یک‌بار محتوا تولید کرده است. برای تولید مجدد، ابتدا از عملیات «اجازه تولید مجدد محتوا» استفاده کنید.',
                    $record->contentRecord?->status?->isProcessing() => 'تولید محتوای این شرکت هم‌اکنون در جریان است.',
                    default => 'محتوای ذخیره‌شده با آخرین ورودی‌ها یکسان است؛ نیازی به تولید مجدد نیست.',
                };

                Notification::make()
                    ->title('تولید محتوا انجام نشد')
                    ->body($reason)
                    ->warning()
                    ->send();
            });
    }

    /**
     * Gives a company back its monthly AI-content-generation quota — e.g.
     * when a queued run later failed for a technical reason (bad model
     * response, network drop) and the company is otherwise stuck until next
     * month's reset. Authorization mirrors generateContentAction's (same
     * Approve:Company permission) via a closure, so it does not depend on
     * shield-generated policy stubs.
     */
    public static function resetContentQuotaAction(): Action
    {
        return Action::make('reset_content_quota')
            ->label('بازگرداندن سهمیه تولید محتوا')
            ->icon(Heroicon::ArrowUturnLeft)
            ->color('gray')
            ->visible(function (Company $record): bool {
                $subscription = $record->activeSubscription();
                $featureSlug = static::contentQuotaFeatureSlug($subscription);

                return $featureSlug !== null && $subscription->getFeatureUsage($featureSlug) > 0;
            })
            ->authorize(fn (Company $record): bool => auth()->user()?->can('Approve:Company') ?? false)
            ->requiresConfirmation()
            ->modalHeading('بازگرداندن سهمیه تولید محتوا')
            ->modalDescription('با این کار، سهمیه تولید محتوای هوش مصنوعی این شرکت برای این ماه بازگردانده می‌شود و شرکت می‌تواند دوباره محتوا تولید کند.')
            ->action(function (Company $record) {
                $subscription = $record->activeSubscription();
                $featureSlug = static::contentQuotaFeatureSlug($subscription);

                if ($featureSlug !== null) {
                    $subscription->resetFeatureUsage($featureSlug);
                }

                Notification::make()
                    ->title('سهمیه تولید محتوا بازگردانده شد')
                    ->success()
                    ->send();
            });
    }

    /**
     * Lifts the ONE-GENERATION-PER-COMPANY lock (see
     * ContentGenerationService::request()) — separate from
     * resetContentQuotaAction(), which only restores the monthly PLAN
     * quota. An admin needs this one when a generation completed but
     * produced poor output and the company must be allowed to generate
     * once more. Authorization mirrors the other content actions (same
     * Approve:Company permission) via a closure.
     */
    public static function allowContentRegenerationAction(): Action
    {
        return Action::make('allow_content_regeneration')
            ->label('اجازه تولید مجدد محتوا')
            ->icon(Heroicon::ArrowPathRoundedSquare)
            ->color('warning')
            ->visible(fn (Company $record): bool => ($record->contentRecord?->generations_count ?? 0) >= 1)
            ->authorize(fn (Company $record): bool => auth()->user()?->can('Approve:Company') ?? false)
            ->requiresConfirmation()
            ->modalHeading('اجازه تولید مجدد محتوا')
            ->modalDescription('این شرکت یک‌بار دیگر می‌تواند محتوای هوش مصنوعی تولید کند و محتوای فعلی با نتیجه تولید جدید جایگزین خواهد شد.')
            ->action(function (Company $record) {
                $record->contentRecord?->update(['generations_count' => 0]);

                Notification::make()
                    ->title('اجازه تولید مجدد محتوا داده شد')
                    ->success()
                    ->send();
            });
    }

    /**
     * On-demand trigger for GenerateFeaturedImage — e.g. re-running it after
     * a failed attempt, or generating an image for content that predates the
     * feature. Authorization mirrors generateContentAction's (same
     * Approve:Company permission) via a closure. The job itself still skips
     * when there is already a featured image or no English payload; this
     * action only gates on the feature being enabled at all.
     */
    public static function generateFeaturedImageAction(): Action
    {
        return Action::make('generate_featured_image')
            ->label('تولید تصویر شاخص')
            ->icon(Heroicon::Photo)
            ->color('info')
            ->visible(fn (): bool => app(ContentSettings::class)->image_enabled)
            ->authorize(fn (Company $record): bool => auth()->user()?->can('Approve:Company') ?? false)
            ->requiresConfirmation()
            ->modalHeading('تولید تصویر شاخص')
            ->modalDescription('درخواست تولید تصویر شاخص با هوش مصنوعی برای این شرکت ارسال می‌شود.')
            ->action(function (Company $record) {
                GenerateFeaturedImage::dispatch($record->id)->onQueue('ai-content');

                Notification::make()
                    ->title('تولید تصویر شاخص آغاز شد')
                    ->success()
                    ->send();
            });
    }

    /**
     * Feature slugs are prefixed with their plan slug in the seeder (see
     * PlanSeeder::seedFeatures), so lookups must be too — same convention
     * as the dashboard's contentFeatureSlug().
     */
    private static function contentQuotaFeatureSlug(?Subscription $subscription): ?string
    {
        $plan = $subscription?->plan;

        return $plan !== null ? $plan->slug.'-ai-content-generations' : null;
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
