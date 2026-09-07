<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Enums\WordPressPostStatus;
use App\Models\WordPressContentPost;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * The company's automatically generated WordPress articles. Read-only apart
 * from deleting FAILED rows: these are written by the scheduled pipeline
 * (AbstractWordPressPostJob chain), and hand-editing them would desync the
 * panel from the owner's dashboard, which is the single source of truth for
 * publish status.
 *
 * Deletion is deliberately narrow — see deleteFailedAction() for why every
 * other status is excluded. It removes only OUR row; an article already
 * live on the owner's WordPress site is never touched, because nothing in
 * app/Services/WordPress can delete a remote post.
 *
 * Otherwise follows the InvoicesRelationManager read-only pattern: status
 * badge, jalaliDateTime dates, copyable reference columns hidden by default.
 */
class WordPressContentRelationManager extends RelationManager
{
    protected static string $relationship = 'wordPressContentPosts';

    protected static ?string $title = 'مقالات وردپرس';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('topic')
            ->columns([
                TextColumn::make('topic')
                    ->label('موضوع')
                    ->description(fn ($record): ?string => $record->title)
                    ->limit(60)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mode')
                    ->label('حالت')
                    ->badge(),
                TextColumn::make('locale')
                    ->label('زبان')
                    ->formatStateUsing(fn (string $state): string => mb_strtoupper($state)),
                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge(),
                TextColumn::make('wp_post_url')
                    ->label('لینک مقاله')
                    // A queued/generating/failed row has no real URL yet, so
                    // the link (and its copy button) only exists once the
                    // article is actually published on the owner's site.
                    ->state(fn ($record): ?string => $record->status === WordPressPostStatus::Published
                        ? $record->wp_post_url
                        : null)
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('لینک مقاله کپی شد')
                    ->url(fn ($record): ?string => $record->status === WordPressPostStatus::Published
                        ? $record->wp_post_url
                        : null,
                        shouldOpenInNewTab: true)
                    ->toggleable(),
                TextColumn::make('failure_reason')
                    ->label('علت ناموفق بودن')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('تاریخ تولید')
                    ->jalaliDateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(WordPressPostStatus::class),
            ])
            ->recordActions([
                static::deleteFailedAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Secondary to the per-row action: one failed generation
                    // run can leave several rows behind, and clearing them one
                    // at a time is the common complaint. Same status rule,
                    // enforced per record rather than by hiding the button —
                    // authorizeIndividualRecords() makes Filament skip any
                    // non-failed row in the selection and report how many were
                    // left alone, instead of silently taking them with it.
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords(
                            fn (WordPressContentPost $record): bool => static::isDeletable($record),
                        ),
                ]),
            ]);
    }

    /**
     * Purging a single article that errored out — the case this exists for.
     *
     * Restricted to Failed on purpose, and the other three statuses are each
     * excluded for their own reason:
     *
     * - Queued/Generating are still in flight. The job chain reloads the row
     *   as it advances, so deleting one mid-run turns a background job into a
     *   ModelNotFoundException rather than a clean cancellation.
     * - Published rows are the local record of an article that is live on
     *   someone else's site, and deleting one here would NOT remove it there.
     *   Worse, `topic_normalized` doubles as the trend-repeat ledger (see
     *   WordPressContentPost's docblock): drop a published row and the picker
     *   forgets that topic was ever used, so the pipeline can regenerate it
     *   and post a duplicate article to the live site.
     *
     * requiresConfirmation() is not set here because DeleteAction already
     * applies it in setUp().
     */
    protected static function deleteFailedAction(): DeleteAction
    {
        return DeleteAction::make()
            ->label('حذف')
            ->visible(fn (WordPressContentPost $record): bool => static::isDeletable($record))
            ->modalHeading('حذف مقاله ناموفق')
            ->modalDescription('این رکورد و تصویر شاخص آن از پایگاه‌داده حذف می‌شود. این کار برگشت‌پذیر نیست.');
    }

    /** Only a failed generation is safe to purge. */
    protected static function isDeletable(WordPressContentPost $record): bool
    {
        return $record->status === WordPressPostStatus::Failed;
    }
}
