<?php

namespace App\Filament\Pages;

use App\Models\GeneralSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Concerns\HasUnsavedDataChangesAlert;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Throwable;
use UnitEnum;

class ManageGeneralSettings extends Page
{
    use CanUseDatabaseTransactions;
    use HasUnsavedDataChangesAlert;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'تنظیمات عمومی';

    protected static ?string $title = 'تنظیمات عمومی';

    protected static string|UnitEnum|null $navigationGroup = 'تنظیمات';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * فیلدهای تصویری که به‌صورت مسیر ساده روی دیسک s3 ذخیره می‌شوند.
     *
     * @var array<int, string>
     */
    protected array $imageFields = [
        'favicon',
        'logo_square_light',
        'logo_square_dark',
        'logo_wide_light',
        'logo_wide_dark',
    ];

    public function mount(): void
    {
        $this->form->fill(GeneralSetting::current()->toArray());
    }

    public function form(Schema $schema): Schema
    {
        $locales = config('laravellocalization.supportedLocales');

        return $schema
            ->columns(1)
            ->statePath('data')
            ->components([
                Tabs::make('site_name')->tabs(
                    collect($locales)->map(
                        fn ($data, string $code) => Tab::make($code)
                            ->label($data['native'])
                            ->schema([
                                TextInput::make("site_name.{$code}")
                                    ->label('نام سایت')
                                    ->required($code === config('app.fallback_locale'))
                                    ->maxLength(255),
                                TextInput::make("site_tagline.{$code}")
                                    ->label('شعار سایت')
                                    ->maxLength(255),
                            ])
                    )->values()->all()
                ),

                Section::make('نمادها و لوگوها')
                    ->schema([
                        FileUpload::make('favicon')
                            ->label('Favicon')
                            ->disk('s3')
                            ->visibility('public')
                            ->directory('branding')
                            ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon']),
                        FileUpload::make('logo_square_light')
                            ->label('لوگوی مربعی (پس‌زمینه روشن)')
                            ->disk('s3')
                            ->visibility('public')
                            ->directory('branding')
                            ->image()
                            ->imageEditor(),
                        FileUpload::make('logo_square_dark')
                            ->label('لوگوی مربعی (پس‌زمینه تیره)')
                            ->disk('s3')
                            ->visibility('public')
                            ->directory('branding')
                            ->image()
                            ->imageEditor(),
                        FileUpload::make('logo_wide_light')
                            ->label('لوگوی عریض (پس‌زمینه روشن)')
                            ->disk('s3')
                            ->visibility('public')
                            ->directory('branding')
                            ->image()
                            ->imageEditor(),
                        FileUpload::make('logo_wide_dark')
                            ->label('لوگوی عریض (پس‌زمینه تیره)')
                            ->disk('s3')
                            ->visibility('public')
                            ->directory('branding')
                            ->image()
                            ->imageEditor(),
                    ])
                    ->columns(2),

                Tabs::make('footer_about')->tabs(
                    collect($locales)->map(
                        fn ($data, string $code) => Tab::make("footer_about_{$code}")
                            ->label($data['native'])
                            ->schema([
                                Textarea::make("footer_about.{$code}")
                                    ->label('متن معرفی فوتر')
                                    ->rows(3)
                                    ->maxLength(500),
                            ])
                    )->values()->all()
                ),

                Section::make('شبکه‌های اجتماعی')
                    ->schema([
                        TextInput::make('social_facebook')
                            ->label('فیس‌بوک')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_instagram')
                            ->label('اینستاگرام')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_twitter')
                            ->label('ایکس (توییتر)')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_linkedin')
                            ->label('لینکدین')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_telegram')
                            ->label('تلگرام')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_whatsapp')
                            ->label('واتساپ')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Section::make('اطلاعات تماس فوتر')
                    ->schema([
                        Textarea::make('contact_address')
                            ->label('آدرس')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                        TextInput::make('contact_phone')
                            ->label('تلفن')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('contact_email')
                            ->label('ایمیل')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('ویجت تراستپایلوت (Trustpilot)')
                    ->description('نظرات مشتریان درباره ویراواچ در تراستپایلوت. ویجت فقط زمانی در فوتر نمایش داده می‌شود که فعال باشد و هر سه مقدار زیر پر شده باشند.')
                    ->schema([
                        Toggle::make('trustpilot_enabled')
                            ->label('نمایش ویجت تراستپایلوت در فوتر')
                            ->live()
                            ->required(),
                        TextInput::make('trustpilot_business_unit_id')
                            ->label('شناسه بیزینس‌یونیت (Business Unit ID)')
                            ->visible(fn (Get $get): bool => (bool) $get('trustpilot_enabled'))
                            ->maxLength(64)
                            ->helperText('از پنل تراستپایلوت یا کد ویجت؛ مقدار data-businessunit-id.')
                            ->placeholder('4f8e5b8d00006400057c8d1c'),
                        TextInput::make('trustpilot_template_id')
                            ->label('شناسه قالب ویجت (Template ID)')
                            ->visible(fn (Get $get): bool => (bool) $get('trustpilot_enabled'))
                            ->maxLength(64)
                            ->helperText('مقدار data-template-id در کد ویجت تراستپایلوت.')
                            ->placeholder('5419b6ffb0d04a07eed4f9d2'),
                        TextInput::make('trustpilot_locale')
                            ->label('زبان ویجت (data-locale)')
                            ->visible(fn (Get $get): bool => (bool) $get('trustpilot_enabled'))
                            ->maxLength(16)
                            ->helperText('مثال: en-US یا fa-IR — بسته به زبان‌هایی که تراستپایلوت پشتیبانی می‌کند.')
                            ->placeholder('en-US'),
                    ])
                    ->columns(2),

                Section::make('نماد اعتماد الکترونیک (اینماد)')
                    ->description('کد HTML اینماد را اینجا جای‌گذاری کنید. هنگام نمایش، فقط تگ‌های <a> و <img> و ویژگی‌های href/src/alt/id/class/style/referrerpolicy از آن نگه‌داشته می‌شود و بقیه (از جمله اسکریپت) حذف می‌شود.')
                    ->schema([
                        Textarea::make('enamad_html')
                            ->label('کد اینماد')
                            ->rows(6)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([$this->getSaveFormAction()])
                        ->key('form-actions'),
                ]),
        ]);
    }

    public function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('ذخیره')
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();

            $setting = GeneralSetting::current();

            foreach ($this->imageFields as $field) {
                $oldPath = $setting->{$field};
                $newPath = $data[$field] ?? null;

                if (filled($oldPath) && $oldPath !== $newPath) {
                    Storage::disk('s3')->delete($oldPath);
                }
            }

            $setting->fill($data);
            $setting->save();
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->commitDatabaseTransaction();

        $this->rememberData();

        Notification::make()
            ->title('تنظیمات ذخیره شد')
            ->success()
            ->send();
    }
}
