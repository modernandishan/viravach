<?php

namespace App\Filament\Pages;

use App\Settings\ChatSettings;
use BackedEnum;
use Closure;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageChatSettings extends SettingsPage
{
    protected static string $settings = ChatSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'تنظیمات چت';

    protected static ?string $title = 'تنظیمات چت';

    protected static string|UnitEnum|null $navigationGroup = 'چت آنلاین';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    /**
     * Blank locale prompts are filled in from the shipped config array so the
     * form starts pre-populated instead of empty — this keeps the "required
     * when ai_enabled" validation below from blocking a fresh install's first
     * save, while still letting an admin overwrite any locale explicitly.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var array<string, string> $configPrompts */
        $configPrompts = config('viravach_chat.system_prompts');

        foreach ($configPrompts as $locale => $prompt) {
            if (blank($data['system_prompts'][$locale] ?? null)) {
                $data['system_prompts'][$locale] = $prompt;
            }
        }

        return $data;
    }

    public function form(Schema $schema): Schema
    {
        $locales = config('laravellocalization.supportedLocales');

        return $schema
            ->columns(1)
            ->components([
                Section::make('کلید خاموش/روشن هوش مصنوعی')
                    ->schema([
                        Toggle::make('ai_enabled')
                            ->label('پاسخ‌گویی هوش مصنوعی فعال باشد')
                            ->helperText('در صورت خاموش بودن، پیام کاربران ذخیره می‌شود اما به‌جای ارسال به هوش مصنوعی، بلافاصله همان پیام خطای پیش‌فرض ("'.__('chat.ai_error').'") برای او ارسال می‌شود.')
                            ->live()
                            ->required(),
                    ]),

                Section::make('سرویس‌دهنده و مدل هوش مصنوعی')
                    ->schema([
                        TextInput::make('ai_provider')
                            ->label('سرویس‌دهنده هوش مصنوعی')
                            ->required()
                            ->helperText('باید دقیقاً برابر یکی از کلیدهای آرایه providers در config/ai.php باشد.'),
                        TextInput::make('ai_model')
                            ->label('مدل هوش مصنوعی')
                            ->required()
                            ->helperText('باید دقیقاً برابر شناسه مدل تعریف‌شده برای همان سرویس‌دهنده باشد.'),
                    ])
                    ->columns(2),

                Section::make('محدودیت‌های عددی')
                    ->description('هر فیلد را خالی بگذارید تا مقدار پیش‌فرض اعمال شود.')
                    ->schema([
                        TextInput::make('ai_history_limit')
                            ->label('تعداد پیام‌های تاریخچه ارسالی به هوش مصنوعی')
                            ->numeric()
                            ->minValue(1)
                            ->nullable()
                            ->helperText('پیش‌فرض فعلی: '.(int) config('viravach_chat.ai_history_limit').' پیام'),
                        TextInput::make('rate_limit_messages_per_minute')
                            ->label('حداکثر تعداد پیام ارسالی در دقیقه')
                            ->numeric()
                            ->minValue(1)
                            ->nullable()
                            ->helperText('پیش‌فرض فعلی: ۱۰ پیام در دقیقه به ازای هر کاربر'),
                        TextInput::make('rate_limit_translations_per_minute')
                            ->label('حداکثر تعداد ترجمه در دقیقه')
                            ->numeric()
                            ->minValue(1)
                            ->nullable()
                            ->helperText('پیش‌فرض فعلی: ۲۰ ترجمه در دقیقه به ازای هر کاربر'),
                    ])
                    ->columns(3),

                Section::make('پرامپت سیستمی ویرابات')
                    ->description('این متن‌ها جایگزین آرایه system_prompts در config/viravach_chat.php می‌شوند. عبارت {context} در متن هر زبان نگه داشته شود؛ در زمان اجرا با اطلاعات صفحه یا شرکت مرتبط جایگزین می‌شود.')
                    ->schema([
                        Tabs::make('system_prompts')
                            ->tabs(
                                collect($locales)->map(
                                    fn ($data, string $code) => Tab::make($code)
                                        ->label($data['native'])
                                        ->schema([
                                            Textarea::make("system_prompts.{$code}")
                                                ->label('پرامپت سیستمی')
                                                ->required(fn (Get $get): bool => (bool) $get('ai_enabled'))
                                                ->rows(14)
                                                ->helperText('عبارت {context} را در متن نگه دارید — در زمان اجرا با اطلاعات صفحه/شرکت مربوطه جایگزین می‌شود.')
                                                /*
                                                 * Enforced, not merely advised. ViraBotAgent::instructions()
                                                 * substitutes {context} with str_replace(); a prompt saved
                                                 * without the placeholder silently drops the entire company
                                                 * facts block, and the bot answers from nothing with no error
                                                 * anywhere. Only validated when a prompt is actually present,
                                                 * so clearing a locale to fall back to config still works.
                                                 */
                                                ->rules([
                                                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                                        if (blank($value)) {
                                                            return;
                                                        }

                                                        if (! str_contains((string) $value, '{context}')) {
                                                            $fail('پرامپت باید شامل عبارت {context} باشد. بدون آن، اطلاعات شرکت و صفحه به هوش مصنوعی ارسال نمی‌شود و ویرابات بدون هیچ خطایی «بی‌اطلاع» پاسخ می‌دهد.');
                                                        }
                                                    },
                                                ])
                                                ->columnSpanFull(),
                                        ])
                                )->values()->all()
                            ),
                    ]),
            ]);
    }
}
