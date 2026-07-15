<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Enums\CompanyStatus;
use App\Filament\Schemas\Components\SeoMetaSection;
use App\Models\CompanyCategory;
use App\Models\Country;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        $locales = config('laravellocalization.supportedLocales');

        return $schema
            ->columns(1)
            ->components([
                Section::make('اطلاعات پایه')
                    ->schema([
                        Select::make('user_id')
                            ->label('کاربر')
                            ->relationship('user', 'id')
                            ->getOptionLabelFromRecordUsing(fn (User $record): string => trim($record->name.' '.$record->family).($record->phone ? " ({$record->phone})" : ''))
                            ->searchable(['name', 'family', 'phone'])
                            ->preload()
                            ->native(false)
                            ->required(),
                        TextInput::make('slug')
                            ->label('نامک (Slug)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('فقط انگلیسی — برای آدرس‌های سئوپسند'),
                        TextInput::make('legal_type')
                            ->label('نوع حقوقی')
                            ->maxLength(255),
                        TextInput::make('registration_number')
                            ->label('شماره ثبت')
                            ->maxLength(255),
                        TextInput::make('national_id')
                            ->label('شناسه ملی')
                            ->maxLength(255),
                        DatePicker::make('established_at')
                            ->label('تاریخ تأسیس')
                            ->jalali(),
                        TextInput::make('website')
                            ->label('وب‌سایت')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('ایمیل')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('employee_range')
                            ->label('بازه تعداد کارکنان')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('تماس و شبکه‌های اجتماعی')
                    ->schema([
                        TagsInput::make('phones')
                            ->label('شماره‌های تماس'),
                        KeyValue::make('social_links')
                            ->label('شبکه‌های اجتماعی')
                            ->keyLabel('پلتفرم')
                            ->valueLabel('آدرس'),
                    ])
                    ->columns(2),

                Section::make('دسته‌بندی‌ها و صادرات')
                    ->schema([
                        Select::make('categories')
                            ->label('دسته‌بندی‌ها')
                            ->multiple()
                            ->options(fn () => CompanyCategory::query()->get()->pluck('title', 'id'))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->dehydrated(false)
                            ->loadStateFromRelationshipsUsing(function (Select $component) {
                                $component->state(
                                    $component->getRecord()->categories()->pluck('company_categories.id')->all()
                                );
                            })
                            ->saveRelationshipsUsing(function (Select $component) {
                                $component->getRecord()->categories()->sync($component->getState() ?? []);
                            }),
                        Select::make('exportCountries')
                            ->label('کشورهای صادراتی')
                            ->multiple()
                            ->options(fn () => Country::query()->get()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->dehydrated(false)
                            ->loadStateFromRelationshipsUsing(function (Select $component) {
                                $component->state(
                                    $component->getRecord()->exportCountries()->pluck('countries.id')->all()
                                );
                            })
                            ->saveRelationshipsUsing(function (Select $component) {
                                $component->getRecord()->exportCountries()->sync($component->getState() ?? []);
                            }),
                    ])
                    ->columns(2),

                Section::make('وضعیت')
                    ->schema([
                        Select::make('status')
                            ->label('وضعیت')
                            ->options(CompanyStatus::class)
                            ->default(CompanyStatus::Draft)
                            ->live()
                            ->native(false)
                            ->required(),
                        DateTimePicker::make('published_at')
                            ->label('تاریخ انتشار')
                            ->jalali(),
                        Toggle::make('is_verified')
                            ->label('تأیید شده'),
                        Toggle::make('is_featured')
                            ->label('ویژه'),
                        Textarea::make('rejection_reason')
                            ->label('دلیل رد')
                            ->rows(3)
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => $get('status') === CompanyStatus::Rejected->value),
                    ])
                    ->columns(2),

                Section::make('تصاویر')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('logo')
                            ->label('لوگو')
                            ->collection('logo')
                            ->disk('s3')
                            ->visibility('public')
                            ->image()
                            ->imageEditor(),
                        SpatieMediaLibraryFileUpload::make('featured_image')
                            ->label('تصویر شاخص')
                            ->collection('featured_image')
                            ->disk('s3')
                            ->visibility('public')
                            ->image()
                            ->imageEditor(),
                        SpatieMediaLibraryFileUpload::make('gallery')
                            ->label('گالری تصاویر')
                            ->collection('gallery')
                            ->disk('s3')
                            ->visibility('public')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->columnSpanFull(),
                        SpatieMediaLibraryFileUpload::make('certificates')
                            ->label('گواهی‌نامه‌ها')
                            ->collection('certificates')
                            ->disk('s3')
                            ->visibility('public')
                            ->multiple()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

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
                                    TextInput::make("legal_name.{$code}")
                                        ->label('نام حقوقی')
                                        ->maxLength(255),
                                    Textarea::make("summary.{$code}")
                                        ->label('خلاصه')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                    RichEditor::make("description.{$code}")
                                        ->label('توضیحات')
                                        ->required($code === config('app.fallback_locale'))
                                        ->fileAttachmentsDisk('s3')
                                        ->fileAttachmentsDirectory("companies/{$code}")
                                        ->fileAttachmentsVisibility('public')
                                        ->columnSpanFull(),
                                    Textarea::make("main_products.{$code}")
                                        ->label('محصولات اصلی')
                                        ->rows(3)
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
