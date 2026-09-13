<?php

namespace App\Filament\Pages;

use App\Settings\ContentSettings;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
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

    /**
     * The stored api_key is never sent to the browser. Filament hydrates the
     * form state into the Livewire component's client-side snapshot whatever
     * the input type is, so ->password()->revealable() hides the key from
     * the screen but NOT from dev tools on an authenticated admin session.
     * Blanking it here means there is nothing to leak; a blank submission is
     * then treated as "keep the current key" by mutateFormDataBeforeSave().
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['api_key'] = '';

        return $data;
    }

    /**
     * Dropping the key from the payload entirely (rather than writing '')
     * leaves $settings->fill() with nothing to say about api_key, so the
     * previously stored value survives the save untouched.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (blank($data['api_key'] ?? null)) {
            unset($data['api_key']);
        }

        return $data;
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
                            /*
                             * Only required when nothing is stored yet: the
                             * field always renders blank (see
                             * mutateFormDataBeforeFill), so an unconditional
                             * ->required() would fail validation on every
                             * save that does not retype the key — before
                             * mutateFormDataBeforeSave ever runs.
                             */
                            ->required(fn (): bool => blank(app(ContentSettings::class)->api_key))
                            ->helperText('برای حفظ کلید فعلی، این فیلد را خالی بگذارید. کلید ذخیره‌شده هرگز در فرم نمایش داده نمی‌شود و فقط در صورت وارد کردن مقدار جدید جایگزین می‌شود.'),
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

                Section::make('راهنمای اضافهٔ بومی‌سازی')
                    ->schema([
                        Tabs::make('localization_prompt_tabs')->tabs(
                            collect(config('laravellocalization.supportedLocales'))->map(
                                fn ($data, string $code) => Tab::make("localization_prompt_{$code}")
                                    ->label($data['native'])
                                    ->schema([
                                        Textarea::make("localization_prompt.{$code}")
                                            ->label('راهنمای اضافهٔ بومی‌سازی')
                                            ->rows(10)
                                            ->helperText('اختیاری. در صورت وارد کردن متن، به انتهای پرامپت ثابت بومی‌سازی برای این زبان اضافه می‌شود (بعد از همه قوانین ثابت). اگر خالی بماند، هیچ متن اضافه‌ای پرامپت افزوده نمی‌شود.')
                                            ->columnSpanFull(),
                                    ])
                            )->values()->all()
                        ),
                    ]),

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
