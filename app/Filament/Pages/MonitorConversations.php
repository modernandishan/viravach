<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ChatStatsOverview;
use App\Models\AiAssistant;
use App\Models\Company;
use App\Models\User;
use App\Services\Chat\ChatConversationDirectory;
use App\Support\LocalizedDate;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Musonza\Chat\Models\Conversation;
use Musonza\Chat\Models\Message;
use UnitEnum;

/**
 * Read-only oversight page: AI conversations (data.type=ai) and human
 * conversations (data.type=support|company) are deliberately kept in two
 * separate tables (switched via $activeTab), never mixed in one grid, per
 * the phase 8 spec. No editing/deleting here — musonza's tables aren't
 * meant to be CRUD-edited directly (see the AiChatService/
 * SupportTransferService/CompanyChatService doc comments).
 */
class MonitorConversations extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'نظارت بر گفتگوها';

    protected static ?string $title = 'نظارت بر گفتگوها';

    protected static string|UnitEnum|null $navigationGroup = 'چت آنلاین';

    protected string $view = 'filament.pages.monitor-conversations';

    public string $activeTab = 'ai';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [ChatStatsOverview::class];
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $this->activeTab === 'human' ? $this->humanTable($table) : $this->aiTable($table);
    }

    protected function aiTable(Table $table): Table
    {
        $ids = app(ChatConversationDirectory::class)->aiConversations()->pluck('id');

        return $table
            ->query(
                Conversation::query()
                    ->whereIn('id', $ids)
                    ->withCount('messages')
                    ->with('participants.messageable')
            )
            ->heading('گفتگوهای هوش مصنوعی')
            ->columns([
                TextColumn::make('participants_label')
                    ->label('کاربر')
                    ->state(fn (Conversation $record) => $this->participantsLabel($record, excludeAssistant: true)),
                TextColumn::make('scope')
                    ->label('محدوده')
                    ->state(fn (Conversation $record) => $this->scopeLabel($record)),
                TextColumn::make('messages_count')
                    ->label('تعداد پیام'),
                TextColumn::make('updated_at')
                    ->label('آخرین پیام')
                    ->jalaliDateTime(),
            ])
            ->filters([
                SelectFilter::make('scope')
                    ->label('محدوده')
                    ->options($this->companyFilterOptions())
                    ->query(fn (Builder $query, array $data) => $this->applyCompanyScopeFilter($query, $data['value'] ?? null)),
            ])
            ->recordActions([$this->transcriptAction()])
            ->paginated([10, 25, 50]);
    }

    protected function humanTable(Table $table): Table
    {
        $ids = app(ChatConversationDirectory::class)->humanConversations()->pluck('id');

        return $table
            ->query(
                Conversation::query()
                    ->whereIn('id', $ids)
                    ->withCount('messages')
                    ->with('participants.messageable')
            )
            ->heading('گفتگوهای انسانی')
            ->columns([
                TextColumn::make('participants_label')
                    ->label('طرفین گفتگو')
                    ->state(fn (Conversation $record) => $this->participantsLabel($record, excludeAssistant: false)),
                TextColumn::make('type_tag')
                    ->label('نوع')
                    ->badge()
                    ->color(fn (Conversation $record) => ($record->data['type'] ?? null) === 'support' ? 'warning' : 'info')
                    ->state(fn (Conversation $record) => ($record->data['type'] ?? null) === 'support' ? 'پشتیبانی' : 'شرکت'),
                TextColumn::make('messages_count')
                    ->label('تعداد پیام'),
                TextColumn::make('updated_at')
                    ->label('آخرین پیام')
                    ->jalaliDateTime(),
            ])
            ->recordActions([$this->transcriptAction()])
            ->paginated([10, 25, 50]);
    }

    protected function transcriptAction(): Action
    {
        return Action::make('viewTranscript')
            ->label('نمایش گفتگو')
            ->icon(Heroicon::OutlinedEye)
            ->modalHeading('گفتگو')
            ->modalContent(fn (Conversation $record): View => view('filament.pages.chat-transcript', [
                'messages' => $this->transcriptMessages($record),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('بستن')
            ->slideOver();
    }

    /**
     * @return Collection<int, array{sender: string, body: string, time: ?string, isSystem: bool, isStaffSide: bool}>
     */
    protected function transcriptMessages(Conversation $record): Collection
    {
        return $record->messages()
            ->with('participation.messageable')
            ->orderBy('id')
            ->get()
            ->map(function (Message $message) {
                $messageable = $message->participation?->messageable;

                return [
                    'sender' => $this->transcriptSenderLabel($messageable),
                    'body' => (string) $message->body,
                    'time' => LocalizedDate::format($message->created_at, LocalizedDate::FORMAT_DATETIME),
                    'isSystem' => $message->type === 'system',
                    'isStaffSide' => $this->isStaffSide($messageable),
                ];
            });
    }

    /**
     * "ویرابات" for the AI, the company's display name, "پشتیبان: {name}" for
     * a support agent (a company/support conversation's other User
     * participant is the customer, not staff — see isStaffSide()), or the
     * plain participant name/label otherwise (already correct for regular
     * users and "کاربر مهمان" for guests via each model's own
     * getParticipantDetailsAttribute()).
     */
    protected function transcriptSenderLabel(?Model $messageable): string
    {
        if ($messageable === null) {
            return '—';
        }

        $name = (string) ($messageable->participant_details['name'] ?? '—');

        if ($messageable instanceof User && $messageable->hasRole('support')) {
            return "پشتیبان: {$name}";
        }

        return $name;
    }

    /**
     * The "staff" side (AI assistant, company, or support agent) is rendered
     * on the opposite side from the end user (customer/guest), reusing the
     * same colored-bubble-vs-gray-bubble, opposite-sides convention as the
     * customer-facing chat's message-item partial.
     */
    protected function isStaffSide(?Model $messageable): bool
    {
        if ($messageable instanceof AiAssistant || $messageable instanceof Company) {
            return true;
        }

        return $messageable instanceof User && $messageable->hasRole('support');
    }

    protected function participantsLabel(Conversation $record, bool $excludeAssistant): string
    {
        $names = $record->participants
            ->map(fn ($participation) => $participation->messageable?->participant_details['name'] ?? null)
            ->filter();

        if ($excludeAssistant) {
            $names = $names->reject(fn (string $name) => $name === __('chat.virabot_name'));
        }

        return $names->implode('، ') ?: '—';
    }

    protected function scopeLabel(Conversation $record): string
    {
        $companyId = $record->data['company_id'] ?? null;

        if (blank($companyId)) {
            return 'عمومی';
        }

        $company = Company::find($companyId);

        return (string) ($company?->publication?->name ?? $company?->name ?? "شرکت #{$companyId}");
    }

    /**
     * @return array<string, string>
     */
    protected function companyFilterOptions(): array
    {
        $companyIds = app(ChatConversationDirectory::class)->aiConversations()
            ->map(fn (Conversation $conversation) => $conversation->data['company_id'] ?? null)
            ->filter()
            ->unique()
            ->values();

        $options = ['general' => 'عمومی'];

        foreach (Company::query()->whereIn('id', $companyIds)->get() as $company) {
            $options[(string) $company->id] = (string) ($company->publication?->name ?? $company->name);
        }

        return $options;
    }

    protected function applyCompanyScopeFilter(Builder $query, ?string $value): Builder
    {
        if (blank($value)) {
            return $query;
        }

        $ids = app(ChatConversationDirectory::class)->aiConversations()
            ->filter(function (Conversation $conversation) use ($value) {
                $companyId = $conversation->data['company_id'] ?? null;

                return $value === 'general'
                    ? blank($companyId)
                    : (string) $companyId === $value;
            })
            ->pluck('id');

        return $query->whereIn('id', $ids);
    }
}
