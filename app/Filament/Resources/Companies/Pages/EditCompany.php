<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Companies\Tables\CompaniesTable;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CompaniesTable::approveAction(),
            CompaniesTable::rejectAction(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
