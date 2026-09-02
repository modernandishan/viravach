<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * The user's invoices, strictly read-only: no create, edit, delete or bulk
 * actions anywhere. These are financial records written by the payment flow
 * (InvoicePaymentService and PaymentCallbackController); hand-editing them in
 * the panel would put the ledger and the gateway out of sync.
 *
 * transaction_id and gateway_ref are gateway *references*, not card data —
 * shetabit/payment never stores a PAN — but they are still shipped as
 * toggleable columns hidden by default, so they are available when
 * reconciling a payment and out of the way otherwise.
 */
class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'فاکتورها';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('مبلغ')
                    ->formatStateUsing(fn (int $state): string => number_format($state))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge(),
                TextColumn::make('company.name')
                    ->label('شرکت')
                    ->state(fn (Invoice $record) => $record->company?->name)
                    ->searchable(false)
                    ->sortable(false),
                TextColumn::make('gateway')
                    ->label('درگاه')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('تاریخ')
                    ->jalaliDateTime()
                    ->sortable(),
                TextColumn::make('transaction_id')
                    ->label('شناسه تراکنش')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('gateway_ref')
                    ->label('کد پیگیری درگاه')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('failure_reason')
                    ->label('علت ناموفق بودن')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(InvoiceStatus::class),
            ]);
    }
}
