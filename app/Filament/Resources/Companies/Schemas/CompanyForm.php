<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Enums\CompanyReviewStatus;
use App\Enums\CompanyType;
use App\Filament\Schemas\Components\CompanyContentSection;
use App\Filament\Schemas\Components\SeoMetaSection;
use App\Models\Company;
use App\Models\CompanyCategory;
use App\Models\Country;
use App\Models\Plan;
use App\Models\User;
use App\Services\CompanySubscriptionService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
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
                        Select::make('legal_type')
                            ->label('نوع حقوقی')
                            ->options(CompanyType::class)
                            ->native(false),
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
                        Textarea::make('brief')
                            ->label('شرح کوتاه کسب‌وکار')
                            ->rows(8)
                            ->helperText('ورودی خام کاربر برای تولید محتوا. در صفحه‌ی عمومی نمایش داده نمی‌شود.')
                            ->columnSpanFull(),
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

                Section::make('اشتراک و پلن')
                    ->schema([
                        Select::make('plan_id')
                            ->label('پلن فعال')
                            ->options(fn () => Plan::query()
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->pluck('name', 'id'))
                            ->default(fn () => Plan::where('slug', 'free')->value('id'))
                            ->required()
                            ->searchable()
                            ->native(false)
                            // Not a real column — Company has no plan_id;
                            // subscriptions live in the package's
                            // `plan_subscriptions` table. Loaded/saved via
                            // the same hooks Filament uses for actual
                            // relationships (see the categories field
                            // above), routed through
                            // CompanySubscriptionService::switchToPlan() so
                            // both admin and user-facing subscription
                            // changes go through one code path.
                            ->dehydrated(false)
                            ->loadStateFromRelationshipsUsing(function (Select $component) {
                                $component->state($component->getRecord()->activeSubscription()?->plan_id);
                            })
                            ->saveRelationshipsUsing(function (Select $component) {
                                $planId = $component->getState();

                                if (! $planId) {
                                    return;
                                }

                                /** @var Company $record */
                                $record = $component->getRecord();
                                $plan = Plan::findOrFail($planId);

                                if ($record->activeSubscription()?->plan_id === $plan->id) {
                                    return;
                                }

                                app(CompanySubscriptionService::class)->switchToPlan($record, $plan);
                            }),
                    ])
                    ->columns(1),

                Section::make('وضعیت بررسی')
                    ->schema([
                        // "Approved" is not selectable here. It is reachable
                        // only through CompaniesTable::approveAction(), which
                        // publishes the snapshot in the same step; setting it
                        // straight from this form marks a company live with no
                        // CompanyPublication behind it, and that state is
                        // invisible — both the approve and republish actions
                        // hide themselves for such a record, so nothing in the
                        // panel can move it afterwards.
                        //
                        // The option stays listed rather than being removed, so
                        // an already-approved company still displays its real
                        // status; it is only disabled, and only while the record
                        // is not already approved — otherwise editing an
                        // approved company's name would fail validation on its
                        // own unchanged status. ->in() is what makes this a
                        // real gate: unlike Radio and ToggleButtons, Filament's
                        // Select does not derive its validation from the
                        // enabled options, so disableOptionWhen() alone would
                        // only hide the choice in the browser.
                        Select::make('review_status')
                            ->label('وضعیت بررسی')
                            ->options(CompanyReviewStatus::class)
                            ->default(CompanyReviewStatus::PendingReview)
                            ->disableOptionWhen(fn (string $value, ?Company $record): bool => $value === CompanyReviewStatus::Approved->value
                                && $record?->review_status !== CompanyReviewStatus::Approved)
                            ->in(fn (Select $component, ?Company $record): array => array_values(array_unique(array_merge(
                                array_keys($component->getEnabledOptions()),
                                $record?->review_status !== null ? [$record->review_status->value] : [],
                            ))))
                            ->helperText('تأیید فقط از طریق دکمه «تأیید» در فهرست شرکت‌ها انجام می‌شود تا نسخه عمومی هم منتشر شود.')
                            ->native(false)
                            ->required(),
                        DateTimePicker::make('reviewed_at')
                            ->label('تاریخ بررسی')
                            ->jalali()
                            ->disabled()
                            ->dehydrated(false),
                        Toggle::make('is_verified')
                            ->label('تأیید شده'),
                        Toggle::make('is_featured')
                            ->label('ویژه'),
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
                                ])
                                ->columns(2)
                        )->values()->all()
                    )
                    ->columnSpanFull(),

                SeoMetaSection::make(),
                CompanyContentSection::make(),
            ]);
    }
}
