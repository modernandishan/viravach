<?php

namespace App\Filament\Schemas\Components;

use App\Ai\Schemas\CompanyContentSchema;
use App\Support\LocalizedDate;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

/**
 * سکشن نمایش/ویرایش محتوای تولیدشده با هوش مصنوعی روی فرم شرکت.
 *
 * یک تب برای هر زبان پشتیبانی‌شده (از config/laravellocalization)، فیلدها
 * از CompanyContentSchema::definition() خوانده می‌شوند تا محدودیت طول‌ها
 * فقط یک‌جا تعریف شده باشند. اعتبارسنجی نهایی در صفحه‌ی EditCompany با
 * CompanyContentSchema::validate() انجام می‌شود.
 */
class CompanyContentSection extends Section
{
    public static function make(string|Htmlable|\Closure|array|null $heading = 'محتوای تولیدشده با هوش مصنوعی'): static
    {
        $static = parent::make($heading);

        $static
            ->columnSpanFull()
            ->hiddenOn('create')
            ->schema([
                self::statusGrid(),
                Tabs::make('content_tabs')
                    ->tabs(
                        collect(config('laravellocalization.supportedLocales'))
                            ->map(fn (array $data, string $code) => Tab::make($code)
                                ->label($data['native'])
                                ->schema(self::localeSchema($code)))
                            ->values()
                            ->all(),
                    ),
            ]);

        return $static;
    }

    private static function statusGrid(): Section
    {
        return Section::make('وضعیت پایپ‌لاین')
            ->columns(4)
            ->schema([
                Placeholder::make('content_status')
                    ->label('وضعیت')
                    ->content(fn ($record): string => $record?->contentRecord?->status?->getLabel() ?? 'بدون محتوا'),
                Placeholder::make('content_step')
                    ->label('گام فعلی')
                    ->content(fn ($record): string => (string) ($record?->contentRecord?->step ?? '—')),
                Placeholder::make('content_generations_count')
                    ->label('تعداد تولید')
                    ->content(fn ($record): string => (string) ($record?->contentRecord?->generations_count ?? '—')),
                Placeholder::make('content_locked_at')
                    ->label('زمان قفل')
                    ->content(fn ($record): string => LocalizedDate::format($record?->contentRecord?->locked_at, LocalizedDate::FORMAT_DATETIME) ?? '—'),
                Placeholder::make('content_failure_reason')
                    ->label('خطای آخرین تولید')
                    ->columnSpanFull()
                    ->content(fn ($record): Htmlable|string => filled($record?->contentRecord?->failure_reason)
                        ? new HtmlString('<span class="text-warning-700 fw-bold">'.e($record->contentRecord->failure_reason).'</span>')
                        : '—'),
            ]);
    }

    /**
     * @return list<Component>
     */
    private static function localeSchema(string $code): array
    {
        return [
            TextInput::make("content.{$code}.hero.headline")
                ->label('تیتر اصلی')
                ->maxLength(self::max('hero.fields.headline')),
            TextInput::make("content.{$code}.hero.subheadline")
                ->label('زیرتیتر')
                ->maxLength(self::max('hero.fields.subheadline')),
            TextInput::make("content.{$code}.hero.image_alt")
                ->label('متن جایگزین تصویر')
                ->maxLength(self::max('hero.fields.image_alt')),

            TextInput::make("content.{$code}.about.heading")
                ->label('تیتر درباره ما')
                ->maxLength(self::max('about.fields.heading')),
            Textarea::make("content.{$code}.about.body")
                ->label('متن درباره ما')
                ->rows(12)
                ->maxLength(self::max('about.fields.body'))
                ->columnSpanFull(),

            self::repeater($code, 'offerings', __('companies.content_offerings'), [
                TextInput::make('title')->label('عنوان')->maxLength(self::max('offerings.item.title')),
                Textarea::make('body')->label('توضیحات')->rows(4)->maxLength(self::max('offerings.item.body')),
            ]),
            self::repeater($code, 'strengths', __('companies.content_strengths'), [
                TextInput::make('title')->label('عنوان')->maxLength(self::max('strengths.item.title')),
                Textarea::make('body')->label('توضیحات')->rows(4)->maxLength(self::max('strengths.item.body')),
            ]),

            TextInput::make("content.{$code}.markets.heading")
                ->label('تیتر بازارهای صادراتی')
                ->maxLength(self::max('markets.fields.heading')),
            Textarea::make("content.{$code}.markets.body")
                ->label('متن بازارهای صادراتی')
                ->rows(6)
                ->maxLength(self::max('markets.fields.body'))
                ->columnSpanFull(),

            self::repeater($code, 'specs', __('companies.content_specs'), [
                TextInput::make('label')->label('برچسب')->maxLength(self::max('specs.item.label')),
                TextInput::make('value')->label('مقدار')->maxLength(self::max('specs.item.value')),
            ]),
            self::repeater($code, 'faq', __('companies.content_faq'), [
                TextInput::make('q')->label('سؤال')->maxLength(self::max('faq.item.q')),
                Textarea::make('a')->label('پاسخ')->rows(4)->maxLength(self::max('faq.item.a')),
            ]),

            TextInput::make("content.{$code}.cta.heading")
                ->label('تیتر فراخوان')
                ->maxLength(self::max('cta.fields.heading')),
            Textarea::make("content.{$code}.cta.body")
                ->label('متن فراخوان')
                ->rows(4)
                ->maxLength(self::max('cta.fields.body'))
                ->columnSpanFull(),
        ];
    }

    private static function repeater(string $code, string $path, string $label, array $schema): Repeater
    {
        return Repeater::make("content.{$code}.{$path}")
            ->label($label)
            ->schema($schema)
            ->defaultItems(0)
            ->reorderable(false)
            ->columns(1)
            ->columnSpanFull();
    }

    /**
     * حداکثر طول فیلد مستقیماً از تعریف اسکیما خوانده می‌شود تا محدودیت‌ها
     * تک‌منبعی بمانند.
     */
    private static function max(string $path): int
    {
        return (int) data_get(CompanyContentSchema::definition(), "{$path}.max");
    }
}
