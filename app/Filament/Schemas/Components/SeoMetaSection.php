<?php

namespace App\Filament\Schemas\Components;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * سکشن سئوی قابل‌استفاده‌ی مجدد، برای اتصال به هر مدلی که از تریت
 * App\Models\Concerns\HasSeo (رابطه‌ی morphOne به SeoMeta) استفاده می‌کند.
 *
 * کافی است در فرم ریسورس مدنظر SeoMetaSection::make() اضافه شود.
 */
class SeoMetaSection extends Section
{
    public static function make(string|\Illuminate\Contracts\Support\Htmlable|\Closure|array|null $heading = 'سئو (SEO)'): static
    {
        $locales = config('laravellocalization.supportedLocales');

        $static = parent::make($heading);

        $static
            ->relationship('seo')
            ->collapsible()
            ->collapsed()
            ->columnSpanFull()
            ->schema([
                Tabs::make('seo_translations')
                    ->tabs(
                        collect($locales)->map(
                            fn ($data, string $code) => Tab::make($code)
                                ->label($data['native'])
                                ->schema([
                                    TextInput::make("meta_title.{$code}")
                                        ->label('عنوان متا (Meta Title)')
                                        ->maxLength(255),
                                    TextInput::make("focus_keyword.{$code}")
                                        ->label('کلیدواژه هدف'),
                                    Textarea::make("meta_description.{$code}")
                                        ->label('توضیحات متا (Meta Description)')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                    TextInput::make("meta_keywords.{$code}")
                                        ->label('کلیدواژه‌ها')
                                        ->helperText('با کاما از هم جدا شوند')
                                        ->columnSpanFull(),
                                    TextInput::make("og_title.{$code}")
                                        ->label('عنوان Open Graph')
                                        ->helperText('خالی بماند = استفاده از عنوان متا'),
                                    TextInput::make("twitter_title.{$code}")
                                        ->label('عنوان توییتر')
                                        ->helperText('خالی بماند = استفاده از عنوان متا'),
                                    Textarea::make("og_description.{$code}")
                                        ->label('توضیحات Open Graph')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                    Textarea::make("twitter_description.{$code}")
                                        ->label('توضیحات توییتر')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                        )->values()->all()
                    )
                    ->columnSpanFull(),

                Section::make('تنظیمات سراسری سئو')
                    ->schema([
                        Toggle::make('robots_index')
                            ->label('ایندکس شود (robots: index)')
                            ->default(true),
                        Toggle::make('robots_follow')
                            ->label('دنبال شود (robots: follow)')
                            ->default(true),
                        TextInput::make('canonical_url')
                            ->label('آدرس Canonical')
                            ->url()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('og_type')
                            ->label('نوع Open Graph')
                            ->options([
                                'website' => 'Website',
                                'article' => 'Article',
                                'product' => 'Product',
                                'profile' => 'Profile',
                            ])
                            ->default('website')
                            ->native(false),
                        SpatieMediaLibraryFileUpload::make('og_image')
                            ->label('تصویر Open Graph')
                            ->collection('og_image')
                            ->disk('s3')
                            ->visibility('public')
                            ->image()
                            ->imageEditor(),
                        Select::make('twitter_card_type')
                            ->label('نوع کارت توییتر')
                            ->options([
                                'summary' => 'Summary',
                                'summary_large_image' => 'Summary Large Image',
                            ])
                            ->default('summary_large_image')
                            ->native(false),
                        SpatieMediaLibraryFileUpload::make('twitter_image')
                            ->label('تصویر توییتر')
                            ->collection('twitter_image')
                            ->disk('s3')
                            ->visibility('public')
                            ->image()
                            ->imageEditor(),
                        TextInput::make('schema_type')
                            ->label('نوع Schema (JSON-LD)')
                            ->helperText('مثلا Organization، LocalBusiness، Product، Article')
                            ->maxLength(255),
                        Textarea::make('schema_extra')
                            ->label('فیلدهای اضافه Schema (JSON)')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('به‌صورت JSON وارد شود')
                            ->afterStateHydrated(fn ($component, $state) => $component->state(
                                is_array($state) ? json_encode($state) : $state
                            ))
                            ->dehydrateStateUsing(fn (?string $state) => $state ? json_decode($state, true) : null),
                        Toggle::make('sitemap_include')
                            ->label('در Sitemap لحاظ شود')
                            ->default(true),
                        TextInput::make('sitemap_priority')
                            ->label('اولویت Sitemap (0 تا 1)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.1)
                            ->default(0.5),
                        Select::make('sitemap_change_freq')
                            ->label('بازه تغییر Sitemap')
                            ->options([
                                'always' => 'Always',
                                'hourly' => 'Hourly',
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                                'yearly' => 'Yearly',
                                'never' => 'Never',
                            ])
                            ->default('weekly')
                            ->native(false),
                    ])
                    ->columns(2),
            ]);

        return $static;
    }
}
