<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\CompanyReviewStatus;
use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * The user's companies, read-only. Editing happens in CompanyResource — the
 * canonical surface with the full form, the review workflow and the content
 * pipeline actions — so this lists and links out instead of becoming a second
 * place a company can be edited.
 *
 * Company DOES use SoftDeletes, so trashed rows stay reachable here via the
 * TrashedFilter rather than silently vanishing from the owner's record.
 */
class CompaniesRelationManager extends RelationManager
{
    protected static string $relationship = 'companies';

    protected static ?string $title = 'شرکت‌ها';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('slug')
            ->columns([
                TextColumn::make('name')
                    ->label('نام')
                    ->state(fn (Company $record) => $record->name)
                    ->searchable(false)
                    ->sortable(false),
                TextColumn::make('slug')
                    ->label('نامک (Slug)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('review_status')
                    ->label('وضعیت بررسی')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->jalaliDateTime()
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->label('تاریخ حذف')
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
            // Read-only: no create, edit, delete or bulk actions. The single
            // action opens the company in CompanyResource.
            ->recordActions([
                Action::make('open')
                    ->label('باز کردن در بخش شرکت‌ها')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Company $record): string => CompanyResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]));
    }
}
