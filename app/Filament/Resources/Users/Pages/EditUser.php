<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

/**
 * No DeleteAction in the header actions, unlike EditCompany: deleting a user
 * cascades through companies, profile and invoices at the database level.
 * See the note at the foot of UsersTable for the full chain.
 */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
}
