<?php

namespace App\Filament\Resources\Plans\Schemas;

use App\Filament\Schemas\Components\SeoMetaSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Laravelcm\Subscriptions\Interval;

class PlanForm
{
    /**
     * @return array<string, string>
     */
    protected static function intervalOptions(): array
    {
        return [
            Interval::DAY->value => 'روز',
            Interval::MONTH->value => 'ماه',
            Interval::YEAR->value => 'سال',
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        $locales = config('laravellocalization.supportedLocales');

        return $schema
            ->columns(1)
            ->components([
                Section::make('اطلاعات پایه')
                    ->schema([
                        TextInput::make('slug')
                            ->label('نامک (Slug)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('فقط انگلیسی — برای شناسایی یکتای پلن؛ در صورت خالی بودن، از نام پلن ساخته می‌شود.'),
                        TextInput::make('sort_order')
                            ->label('ترتیب نمایش')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('پلن‌ها بر اساس این عدد به‌صورت صعودی مرتب می‌شوند.'),
                        Toggle::make('is_active')
                            ->label('فعال')
                            ->default(true)
                            ->helperText('پلن‌های غیرفعال برای مشترکین جدید قابل انتخاب نیستند.'),
                        TextInput::make('active_subscribers_limit')
                            ->label('حداکثر تعداد مشترکین فعال')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('در صورت خالی گذاشتن، محدودیتی اعمال نمی‌شود.'),
                    ])
                    ->columns(2),

                Section::make('قیمت‌گذاری')
                    ->schema([
                        TextInput::make('price')
                            ->label('قیمت')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->helperText('برای پلن رایگان، مقدار صفر وارد شود.')
                            ->suffix(fn (Get $get) => $get('currency')),
                        TextInput::make('signup_fee')
                            ->label('هزینه ثبت‌نام (یک‌بار)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->helperText('هزینه‌ای که فقط یک‌بار و در زمان ثبت‌نام اولیه دریافت می‌شود.'),
                        TextInput::make('currency')
                            ->label('واحد پول')
                            ->required()
                            ->length(3)
                            ->default('IRT')
                            ->helperText('واحد پول سایت همیشه تومان است؛ کد سه‌حرفی IRT را تغییر ندهید مگر برای مقاصد آرشیوی.'),
                    ])
                    ->columns(3),

                Section::make('دوره صورتحساب (Invoice)')
                    ->schema([
                        TextInput::make('invoice_period')
                            ->label('طول دوره صورتحساب')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->helperText('عدد صفر یعنی این پلن به‌صورت دوره‌ای صورتحساب نمی‌شود (مثلا پلن رایگان).'),
                        Select::make('invoice_interval')
                            ->label('واحد دوره صورتحساب')
                            ->options(self::intervalOptions())
                            ->default(Interval::MONTH->value)
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),

                Section::make('دوره آزمایشی (Trial)')
                    ->schema([
                        TextInput::make('trial_period')
                            ->label('طول دوره آزمایشی')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->helperText('عدد صفر یعنی این پلن دوره آزمایشی ندارد.'),
                        Select::make('trial_interval')
                            ->label('واحد دوره آزمایشی')
                            ->options(self::intervalOptions())
                            ->default(Interval::DAY->value)
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2)
                    ->footer('توجه: محدودیت «هر کاربر فقط یک بار می‌تواند از دوره آزمایشی استفاده کند» در این مرحله در سطح پلن پیاده‌سازی نشده و باید در منطق اشتراک‌نویسی شرکت‌ها اعمال شود.'),

                Section::make('دوره مهلت (Grace Period)')
                    ->schema([
                        TextInput::make('grace_period')
                            ->label('طول دوره مهلت')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->helperText('مهلتی که پس از پایان اشتراک، پیش از قطع دسترسی به مشترک داده می‌شود.'),
                        Select::make('grace_interval')
                            ->label('واحد دوره مهلت')
                            ->options(self::intervalOptions())
                            ->default(Interval::DAY->value)
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),

                Section::make('تنظیمات تناسبی (Prorate)')
                    ->schema([
                        TextInput::make('prorate_day')
                            ->label('روز مبنای تناسبی')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(31),
                        TextInput::make('prorate_period')
                            ->label('دوره تناسبی')
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('prorate_extend_due')
                            ->label('تمدید سررسید تناسبی')
                            ->numeric()
                            ->minValue(1),
                    ])
                    ->description('این فیلدها برای محاسبه‌ی صورتحساب تناسبی (Proration) هنگام ارتقا یا تنزل پلن استفاده می‌شوند و در صورت نیاز خالی بمانند.')
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),

                Section::make('تصویر پلن')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('image')
                            ->label('تصویر شاخص')
                            ->collection('image')
                            ->disk('s3')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->helperText('متن جایگزین (Alt) و عنوان تصویر را می‌توانید پس از ذخیره، از طریق دکمه «ویرایش متادیتای تصویر» در بالای صفحه ویرایش پلن، یا از بخش «رسانه‌ها» تنظیم کنید.'),
                    ])
                    ->columns(1),

                Tabs::make('translations')
                    ->tabs(
                        collect($locales)->map(
                            fn ($data, string $code) => Tab::make($code)
                                ->label($data['native'])
                                ->schema([
                                    TextInput::make("name.{$code}")
                                        ->label('نام پلن')
                                        ->required($code === config('app.fallback_locale'))
                                        ->maxLength(255),
                                    Textarea::make("description.{$code}")
                                        ->label('توضیحات')
                                        ->rows(4)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                        )->values()->all()
                    )
                    ->columnSpanFull(),

                SeoMetaSection::make(),
            ]);
    }
}
