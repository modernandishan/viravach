<?php

namespace App\Filament\Resources\CompanyCategories;

use App\Filament\Resources\CompanyCategories\Pages\CreateCompanyCategory;
use App\Filament\Resources\CompanyCategories\Pages\EditCompanyCategory;
use App\Filament\Resources\CompanyCategories\Pages\ListCompanyCategories;
use App\Filament\Resources\CompanyCategories\Schemas\CompanyCategoryForm;
use App\Filament\Resources\CompanyCategories\Tables\CompanyCategoriesTable;
use App\Models\CompanyCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyCategoryResource extends Resource
{
    protected static ?string $model = CompanyCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'slug';

    protected static ?string $modelLabel = 'دسته‌بندی شرکت';

    protected static ?string $pluralModelLabel = 'دسته‌بندی‌های شرکت';

    protected static ?string $navigationLabel = 'دسته‌بندی‌های شرکت';

    public static function form(Schema $schema): Schema
    {
        return CompanyCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompanyCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanyCategories::route('/'),
            'create' => CreateCompanyCategory::route('/create'),
            'edit' => EditCompanyCategory::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
