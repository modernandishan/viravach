<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Models\CompanyBrand;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BrandsRelationManager extends RelationManager
{
    protected static string $relationship = 'brands';

    protected static ?string $title = 'برندها';

    public function form(Schema $schema): Schema
    {
        $locales = config('laravellocalization.supportedLocales');

        return $schema
            ->columns(2)
            ->components([
                TextInput::make('slug')
                    ->label('نامک (Slug)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->helperText('فقط انگلیسی — برای آدرس‌های سئوپسند'),
                TextInput::make('sort_order')
                    ->label('ترتیب نمایش')
                    ->numeric()
                    ->default(0)
                    ->required(),
                SpatieMediaLibraryFileUpload::make('logo')
                    ->label('لوگو')
                    ->collection('logo')
                    ->disk('s3')
                    ->visibility('public')
                    ->image()
                    ->imageEditor()
                    ->columnSpanFull(),

                Tabs::make('translations')
                    ->tabs(
                        collect($locales)->map(
                            fn ($data, string $code) => Tab::make($code)
                                ->label($data['native'])
                                ->schema([
                                    TextInput::make("name.{$code}")
                                        ->label('نام')
                                        ->required($code === config('app.fallback_locale'))
                                        ->maxLength(255),
                                ])
                        )->values()->all()
                    )
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                SpatieMediaLibraryImageColumn::make('logo')
                    ->collection('logo')
                    ->conversion('webp')
                    ->visibility('public')
                    ->label('لوگو')
                    ->circular(),
                TextColumn::make('name')
                    ->label('نام')
                    ->state(fn (CompanyBrand $record) => $record->name)
                    ->searchable(false)
                    ->sortable(false),
                TextColumn::make('slug')
                    ->label('نامک (Slug)')
                    ->searchable()
                    ->sortable(),
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
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]));
    }
}
