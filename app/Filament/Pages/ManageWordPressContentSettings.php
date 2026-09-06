<?php

namespace App\Filament\Pages;

use App\Settings\WordPressContentSettings;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageWordPressContentSettings extends SettingsPage
{
    protected static string $settings = WordPressContentSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $navigationLabel = 'تنظیمات محتوای خودکار وردپرس';

    protected static ?string $title = 'تنظیمات محتوای خودکار وردپرس';

    protected static string|UnitEnum|null $navigationGroup = 'هوش مصنوعی';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        $locales = config('laravellocalization.supportedLocales');

        return $schema
            ->columns(1)
            ->components([
                Section::make('کلید خاموش/روشن تولید خودکار مقاله وردپرس')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('فعال‌سازی تولید خودکار مقاله وردپرس')
                            ->helperText('این کلید مستقل از کلید «تنظیمات تولید محتوا» است. در صورت خاموش بودن، زمان‌بند شبانه هیچ مقاله‌ای برای هیچ شرکتی تولید نمی‌کند.')
                            ->required(),
                    ]),

                Tabs::make('prompt_templates')->tabs(
                    collect($locales)->map(
                        fn ($data, string $code) => Tab::make("prompts_{$code}")
                            ->label($data['native'])
                            ->schema([
                                Textarea::make("article_system_prompt.{$code}")
                                    ->label('راهنمای نگارش مقاله (بدنه پرامپت سیستم)')
                                    ->rows(10)
                                    ->helperText('فقط متن راهنما. قرارداد خروجی JSON، نام زبان و قوانین ثابت به‌صورت خودکار به این متن اضافه می‌شوند؛ اگر خالی بماند، متن پیش‌فرض استفاده می‌شود.')
                                    ->columnSpanFull(),
                                Textarea::make("mode_brief_industry.{$code}")
                                    ->label('قالب معرفی موضوع تخصصی')
                                    ->rows(5)
                                    ->helperText('متن معرفی برای حالت «تخصصی در حوزه فعالیت شرکت». جای‌نگهدار {topic} با موضوع جایگزین می‌شود.')
                                    ->columnSpanFull(),
                                Textarea::make("mode_brief_trending.{$code}")
                                    ->label('قالب معرفی موضوع پرطرفدار')
                                    ->rows(6)
                                    ->helperText('برای حالت «موضوعات پرجستجوی گوگل». مقاله باید مستقل و درباره خودِ موضوع باشد، بدون پیوند به حوزه شرکت. جای‌نگهدارها: {topic} و {candidates} (فهرست موضوعات رائج که کد آن را جایگزین می‌کند).')
                                    ->columnSpanFull(),
                            ])
                    )->values()->all()
                ),
            ]);
    }
}
