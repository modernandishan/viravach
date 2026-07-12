<?php

namespace App\Filament\Pages;

use App\Models\GeneralSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
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
