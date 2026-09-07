<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * "همه" stays first so it remains the default tab. The second tab is the
     * republish queue: companies whose public snapshot is stale because the
     * owner (or an admin) edited the draft after it went live. Both the tab
     * and its badge run through Company::scopePendingRepublish() so the count
     * here and the sidebar badge can never disagree.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $pendingRepublishCount = Company::query()->pendingRepublish()->count();

        return [
            'all' => Tab::make('همه'),
            'pending_republish' => Tab::make('در انتظار انتشار مجدد')
                ->icon(Heroicon::ArrowPath)
                ->badge($pendingRepublishCount ?: null)
                ->badgeColor('warning')
                ->query(fn (Builder $query): Builder => $query->pendingRepublish()),
        ];
    }
}
