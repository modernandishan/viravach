<?php

namespace App\Filament\Resources\CompanyCategories\Pages;

use App\Filament\Resources\CompanyCategories\CompanyCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompanyCategories extends ListRecords
{
    protected static string $resource = CompanyCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
