<?php

namespace App\Filament\Resources\CompanyPublications;

use App\Filament\Resources\CompanyPublications\Pages\ListCompanyPublications;
use App\Filament\Resources\CompanyPublications\Pages\ViewCompanyPublication;
use App\Filament\Resources\CompanyPublications\Schemas\CompanyPublicationInfolist;
use App\Filament\Resources\CompanyPublications\Tables\CompanyPublicationsTable;
use App\Models\CompanyPublication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Read-only window on the public snapshots: what visitors actually see.
 * Snapshots are only ever written by CompanyPublicationService, so the
 * resource intentionally has no create/edit/delete pages or actions.
 */
class CompanyPublicationResource extends Resource
{
    protected static ?string $model = CompanyPublication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $recordTitleAttribute = 'slug';

    protected static ?string $modelLabel = 'نسخه منتشرشده';

    protected static ?string $pluralModelLabel = 'نسخه‌های منتشرشده';

    protected static ?string $navigationLabel = 'نسخه‌های منتشرشده';

    public static function infolist(Schema $schema): Schema
    {
        return CompanyPublicationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompanyPublicationsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanyPublications::route('/'),
            'view' => ViewCompanyPublication::route('/{record}'),
        ];
    }
}
