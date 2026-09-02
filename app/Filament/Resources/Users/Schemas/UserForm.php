<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Gender;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $locales = config('laravellocalization.supportedLocales');

        return $schema
            ->columns(1)
            ->components([
                Section::make('حساب کاربری')
                    ->schema([
                        TextInput::make('email')
                            ->label('ایمیل')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('تلفن همراه')
                            ->tel()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        DateTimePicker::make('email_verified_at')
                            ->label('زمان تأیید ایمیل')
                            ->jalali()
                            ->helperText('خالی بودن یعنی ایمیل هنوز تأیید نشده است.'),
                        DateTimePicker::make('phone_verified_at')
                            ->label('زمان تأیید تلفن')
                            ->jalali()
                            ->helperText('خالی بودن یعنی تلفن هنوز تأیید نشده است.'),
                        DateTimePicker::make('deactivated_at')
                            ->label('پنهان‌سازی از سایت عمومی')
                            ->jalali()
                            // The model's own comment is explicit: deactivation
                            // only removes the user from public output and does
                            // NOT restrict dashboard access. The label and this
                            // helper text must not imply a suspension.
                            ->helperText('پر بودن این فیلد فقط کاربر را از خروجی سایت عمومی حذف می‌کند (scopePubliclyVisible). دسترسی او به داشبورد کاربری، ورود به حساب و ارسال پیام دست‌نخورده باقی می‌ماند — این یک تعلیق حساب نیست.'),
                        DateTimePicker::make('trial_used_at')
                            ->label('زمان استفاده از دوره آزمایشی')
                            ->jalali()
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('فقط برای مشاهده. توسط CompanySubscriptionService::startProPlusTrial() تنظیم می‌شود.'),
                    ])
                    ->columns(2),

                Section::make('رمز عبور')
                    ->description('برای تغییر رمز عبور مقدار جدیدی وارد کنید. خالی گذاشتن یعنی رمز فعلی بدون تغییر می‌ماند.')
                    ->schema([
                        TextInput::make('password')
                            ->label('رمز عبور جدید')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            // Never pre-filled: the stored value is a hash and
                            // must never round-trip through the form.
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            // Hashing is the model's 'hashed' cast — assigning a
                            // plain string here is hashed on save, so there is
                            // no Hash::make() call and no plaintext stored.
                            ->helperText('هش‌کردن توسط کست hashed روی مدل User انجام می‌شود.')
                            ->columnSpanFull(),
                    ]),

                Section::make('نقش‌ها')
                    ->schema([
                        Select::make('roles')
                            ->label('نقش‌ها')
                            ->relationship('roles', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Role $record): string => __("roles.{$record->name}"))
                            ->multiple()
                            ->preload()
                            ->rules([
                                /*
                                 * Self-lockout guard. An administrator editing
                                 * their OWN record cannot drop super_admin:
                                 * doing so would revoke canAccessPanel() mid-
                                 * session and leave nobody able to restore it
                                 * from the panel. Other users' roles are
                                 * unaffected, and a second super_admin can still
                                 * demote this one.
                                 */
                                fn (?User $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                    if ($record === null || $record->getKey() !== auth()->id()) {
                                        return;
                                    }

                                    $superAdminId = Role::query()->where('name', 'super_admin')->value('id');

                                    if ($superAdminId === null) {
                                        return;
                                    }

                                    $selected = array_map('strval', (array) $value);

                                    if (! in_array((string) $superAdminId, $selected, true)) {
                                        $fail('نمی‌توانید نقش super_admin را از حساب خودتان بردارید؛ در این صورت دسترسی شما به پنل قطع می‌شود و امکان بازگرداندن آن از داخل پنل وجود نخواهد داشت.');
                                    }
                                },
                            ])
                            ->columnSpanFull(),
                    ]),

                Tabs::make('translations')
                    ->tabs(
                        collect($locales)->map(
                            fn ($data, string $code) => Tab::make($code)
                                ->label($data['native'])
                                ->schema([
                                    TextInput::make("name.{$code}")
                                        ->label('نام')
                                        ->required($code === config('app.fallback_locale'))
                                        ->maxLength(255),
                                    TextInput::make("family.{$code}")
                                        ->label('نام خانوادگی')
                                        ->required($code === config('app.fallback_locale'))
                                        ->maxLength(255),
                                ])
                                ->columns(2)
                        )->values()->all()
                    )
                    ->columnSpanFull(),

                Section::make('آواتار')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('avatar')
                            ->label('تصویر پروفایل')
                            ->collection('avatar')
                            ->disk('s3')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->columnSpanFull(),
                    ]),

                // Profile is a HasOne, so it is edited inline through the
                // relationship rather than as a relation manager (a relation
                // manager for a single row would be needless indirection).
                Section::make('پروفایل')
                    ->relationship('profile')
                    ->schema([
                        TextInput::make('username')
                            ->label('نام کاربری')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('national_code')
                            ->label('کد ملی')
                            ->unique(ignoreRecord: true)
                            ->length(10)
                            // Deliberately not searchable in the table: it is
                            // national-ID PII, shown here because admins need
                            // it, but not turned into a lookup key.
                            ->helperText('اطلاعات هویتی حساس — در جدول کاربران قابل جست‌وجو نیست.'),
                        Select::make('gender')
                            ->label('جنسیت')
                            ->options([
                                Gender::Male->value => 'مرد',
                                Gender::Female->value => 'زن',
                                Gender::Other->value => 'سایر',
                            ])
                            ->native(false),
                        DatePicker::make('birth_date')
                            ->label('تاریخ تولد')
                            ->jalali(),
                        Select::make('country_id')
                            ->label('کشور')
                            ->options(fn () => Country::query()->get()->pluck('name', 'id'))
                            ->searchable()
                            ->native(false),
                        Select::make('state_id')
                            ->label('استان')
                            ->options(fn () => State::query()->get()->pluck('name', 'id'))
                            ->searchable()
                            ->native(false),
                        TextInput::make('city')
                            ->label('شهر')
                            ->maxLength(255),
                        TextInput::make('postal_code')
                            ->label('کد پستی')
                            ->maxLength(10),
                        TagsInput::make('skills')
                            ->label('مهارت‌ها')
                            ->helperText('فهرست ساده — چندزبانه نیست.')
                            ->columnSpanFull(),
                        KeyValue::make('social_links')
                            ->label('شبکه‌های اجتماعی')
                            ->keyLabel('شبکه')
                            ->valueLabel('آدرس')
                            ->columnSpanFull(),

                        Tabs::make('profile_translations')
                            ->tabs(
                                collect($locales)->map(
                                    fn ($data, string $code) => Tab::make("profile_{$code}")
                                        ->label($data['native'])
                                        ->schema([
                                            TextInput::make("job_title.{$code}")
                                                ->label('عنوان شغلی')
                                                ->maxLength(255),
                                            Textarea::make("address.{$code}")
                                                ->label('آدرس')
                                                ->rows(2)
                                                ->columnSpanFull(),
                                            Textarea::make("biography.{$code}")
                                                ->label('بیوگرافی')
                                                ->rows(3)
                                                ->columnSpanFull(),
                                        ])
                                )->values()->all()
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
