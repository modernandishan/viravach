<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Enums\WordPressPostStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * The company's automatically generated WordPress articles, strictly
 * read-only: no create, edit, delete or bulk actions anywhere. These rows
 * are written by the scheduled pipeline (AbstractWordPressPostJob chain);
 * hand-editing them would desync the panel from the owner's dashboard,
 * which is the single source of truth for publish status.
 *
 * Follows the InvoicesRelationManager read-only pattern: status badge,
 * jalaliDateTime dates, copyable reference columns hidden by default.
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
            ]);
    }
}
