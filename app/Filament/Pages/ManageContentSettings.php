<?php

namespace App\Filament\Pages;

use App\Settings\ContentSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageContentSettings extends SettingsPage
{
    protected static string $settings = ContentSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'تنظیمات تولید محتوا';

    protected static ?string $title = 'تنظیمات تولید محتوا';

    protected static string|UnitEnum|null $navigationGroup = 'هوش مصنوعی';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('کلید خاموش/روشن تولید محتوا')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('فعال‌سازی تولید محتوا')
                            ->helperText('در صورت خاموش بودن، درخواست تولید محتوا هیچ کاری انجام نمی‌دهد و ردیف شرکت دست‌نخورده می‌ماند.')
                            ->required(),
                    ]),

                Section::make('سرویس‌دهنده و مدل')
                    ->schema([
                        TextInput::make('provider')
                            ->label('ارائه‌دهنده')
                            ->required(),
                        TextInput::make('base_url')
                            ->label('آدرس پایه')
                            ->url()
                            ->required()
                            ->helperText('مثال: https://ai.example.com/v1 — مسیر chat/completions به صورت خودکار اضافه می‌شود.'),
                        TextInput::make('api_key')
                            ->label('کلید API')
                            ->password()
                            ->revealable()
                            ->required(),
                        TextInput::make('model')
                            ->label('مدل تولید محتوا')
                            ->required()
                            ->helperText('شناسه مدلی که متن اصلی (انگلیسی) شرکت‌ها با آن تولید می‌شود.'),
                        TextInput::make('translation_model')
                            ->label('مدل بومی‌سازی')
                            ->required()
                            ->helperText('شناسه مدلی که نسخه‌های زبان‌های دیگر (فارسی، عربی و…) از روی متن انگلیسی بومی‌سازی می‌شوند.'),
                    ])
                    ->columns(2),

                Section::make('پارامترهای عددی')
                    ->schema([
                        TextInput::make('temperature')
                            ->label('دما')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(2)
                            ->step(0.1)
                            ->required()
                            ->helperText('میزان خلاقیت مدل (۰ تا ۲). مقدار پایین (مثل ۰٫۵) برای متن واقعی و غیرداستانی مناسب است تا مدل چیزی از خودش نسازد.'),
                        TextInput::make('timeout')
                            ->label('مهلت (ثانیه)')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        TextInput::make('max_retries')
                            ->label('تعداد تلاش مجدد')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->helperText('در صورت خطای شبکه یا شلوغی سرور، چند بار دیگر تلاش شود (فقط برای خطاهای قابل تلاش).'),
                    ])
                    ->columns(3),

                Section::make('تولید تصویر شاخص')
                    ->schema([
                        Toggle::make('image_enabled')
                            ->label('فعال‌سازی تولید تصویر شاخص')
                            ->helperText('در صورت خاموش بودن، تصویر شاخص تولید نمی‌شود؛ حتی اگر تولید متن فعال باشد.')
                            ->required(),
                        TextInput::make('image_model')
                            ->label('مدل تولید تصویر')
                            ->helperText('شناسه مدلی که تصویر شاخص با آن تولید می‌شود.'),
                        TextInput::make('image_size')
                            ->label('اندازه تصویر')
                            ->helperText('مثال: 1024x1024'),
                    ])
                    ->columns(3),
            ]);
    }
}
