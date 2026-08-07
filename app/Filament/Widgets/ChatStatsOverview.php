<?php

namespace App\Filament\Widgets;

use App\Services\Chat\ChatConversationDirectory;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Musonza\Chat\Models\Message;

/**
 * Only ever attached explicitly via MonitorConversations::getHeaderWidgets()
 * — $isDiscovered = false keeps the panel's auto-discovery (configured in
 * AdminPanelProvider) from also surfacing it on the main admin Dashboard,
 * which would mix unrelated chat stats into unrelated dashboard content.
 */
class ChatStatsOverview extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $directory = app(ChatConversationDirectory::class);

        $aiConversations = $directory->aiConversations();
        $humanConversations = $directory->humanConversations();

        $aiTotal = $aiConversations->count();
        $aiToday = $aiConversations->where('created_at', '>=', Carbon::today())->count();
        $aiThisWeek = $aiConversations->where('created_at', '>=', Carbon::now()->startOfWeek())->count();

        $aiConversationIds = $aiConversations->pluck('id');
        $aiMessageCount = $aiConversationIds->isEmpty()
            ? 0
            : Message::whereIn('conversation_id', $aiConversationIds)->count();
        $averageMessagesPerAiConversation = $aiTotal > 0 ? round($aiMessageCount / $aiTotal, 1) : 0;

        $fallbackErrorCount = Message::where('type', 'ai_error')->count();

        return [
            Stat::make('گفتگوهای هوش مصنوعی (امروز)', (string) $aiToday),
            Stat::make('گفتگوهای هوش مصنوعی (این هفته)', (string) $aiThisWeek),
            Stat::make('گفتگوهای هوش مصنوعی (کل)', (string) $aiTotal),
            Stat::make('گفتگوهای انسانی (کل)', (string) $humanConversations->count()),
            Stat::make('میانگین پیام هر گفتگوی هوش مصنوعی', (string) $averageMessagesPerAiConversation),
            Stat::make('تعداد پاسخ‌های پیش‌فرض خطای هوش مصنوعی', (string) $fallbackErrorCount),
        ];
    }
}
