<?php

namespace App\Filament\Resources\CompanyCategories\Pages;

use App\Filament\Resources\CompanyCategories\CompanyCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCompanyCategory extends EditRecord
{
    protected static string $resource = CompanyCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
