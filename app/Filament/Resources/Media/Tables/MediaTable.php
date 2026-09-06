<?php

namespace App\Filament\Resources\Media\Tables;

use App\Models\MediaLibraryEntry;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Image;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaTable
{
    protected static array $translatableProperties = ['title', 'alt', 'caption', 'description'];

    /**
     * Per-locale metadata tabs (title/alt/caption/description as Media
     * custom properties) shared by the table's edit action and the Media
     * resource's upload action, so the field definitions exist in one place.
     */
    public static function translatableTabs(): Tabs
    {
        $locales = config('laravellocalization.supportedLocales');

        return Tabs::make('translations')
            ->tabs(
                collect($locales)->map(
                    fn ($data, string $code) => Tab::make($code)
                        ->label($data['native'])
                        ->schema([
                            TextInput::make("title.{$code}")
                                ->label('عنوان')
                                ->maxLength(255),
                            TextInput::make("alt.{$code}")
                                ->label('متن جایگزین (Alt)')
                                ->maxLength(255),
                            TextInput::make("caption.{$code}")
                                ->label('توضیحات کوتاه (Caption)')
                                ->columnSpanFull()
                                ->maxLength(255),
                            Textarea::make("description.{$code}")
                                ->label('توضیحات')
                                ->rows(5)
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                )->values()->all()
            )
            ->columnSpanFull();
    }

    /** The locale-keyed payload each translatable property expects. */
    public static function customPropertiesFromData(array $data): array
    {
        $properties = [];

        foreach (static::$translatableProperties as $property) {
            $properties[$property] = $data[$property] ?? [];
        }

        return $properties;
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('preview')
                    ->label('پیش‌نمایش')
                    ->state(fn (Media $record) => $record->getUrl())
                    ->square(),
                TextColumn::make('name')
                    ->label('نام')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('collection_name')
                    ->label('مجموعه')
                    ->badge(),
                TextColumn::make('model_type')
                    ->label('مدل مرتبط')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—'),
                TextColumn::make('size')
                    ->label('حجم')
                    ->formatStateUsing(fn (int $state) => number_format($state / 1024, 1).' KB')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->jalaliDateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('collection_name')
                    ->label('مجموعه')
                    ->options(fn () => Media::query()->distinct()->pluck('collection_name', 'collection_name')),
            ])
            ->recordActions([
                // Read-only helpers available on EVERY media row, regardless
                // of how the file arrived (library upload, company logo,
                // generated article image, ...). The URL resolution reuses
                // spatie's getUrl() — the same call the preview column
                // already makes — so it honours each row's own disk/driver.
                Action::make('view')
                    ->label('مشاهده')
                    ->icon(Heroicon::Eye)
                    ->color('gray')
                    ->url(fn (Media $record): ?string => Str::sanitizeUrl($record->getUrl()), shouldOpenInNewTab: true),
                // Filament has no first-class copy action for row actions
                // (only ->copyable() on columns/fields), so this is the
                // documented fallback: a plain anchor whose Alpine click
                // handler writes the clipboard and raises the framework's
                // own JS notification. No confirmation — it is side-effect
                // free.
                Action::make('copy_link')
                    ->label('کپی لینک')
                    ->icon(Heroicon::Link)
                    ->color('gray')
                    ->url('#')
                    ->extraAttributes(fn (Media $record): array => [
                        'x-on:click.prevent' => 'navigator.clipboard.writeText('
                            .json_encode($record->getUrl())
                            .').then(() => { new FilamentNotification().title('
                            .json_encode('لینک کپی شد').').success().send(); })',
                    ]),
                Action::make('edit')
                    ->label('ویرایش')
                    ->modalHeading(fn (Media $record) => $record->name)
                    ->modalWidth('2xl')
                    ->schema(fn () => [
                        Image::make(
                            fn (Media $record) => $record->getUrl(),
                            fn (Media $record) => $record->name,
                        )->imageHeight(200),
                        static::translatableTabs(),
                    ])
                    ->fillForm(function (Media $record) {
                        $data = [];

                        foreach (static::$translatableProperties as $property) {
                            $data[$property] = $record->getCustomProperty($property, []);
                        }

                        return $data;
                    })
                    ->action(function (array $data, Media $record) {
                        foreach (static::customPropertiesFromData($data) as $property => $value) {
                            $record->setCustomProperty($property, $value);
                        }

                        $record->save();

                        Notification::make()
                            ->title('اطلاعات رسانه ذخیره شد')
                            ->success()
                            ->send();
                    }),
                // Deleting a Media row also removes its file and conversions —
                // spatie's Media::delete() handles that; nothing custom here.
                // Gated to manually-uploaded library files only: media attached
                // to a real Company/Category/... owner must never be deletable
                // from this list. Closure authorize per the CompaniesTable
                // convention for non-Shield-policy gating. No bulk-delete
                // counterpart on purpose: safely scoping a bulk selection the
                // same way is not worth the risk of mass-deleting attached
                // media.
                DeleteAction::make()
                    ->label('حذف')
                    ->authorize(fn (Media $record): bool => MediaLibraryEntry::owns($record)),
            ])
            ->recordAction('edit');
    }
}
