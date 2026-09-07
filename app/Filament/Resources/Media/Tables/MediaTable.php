<?php

namespace App\Filament\Resources\Media\Tables;

use App\Models\MediaLibraryEntry;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Image;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaTable
{
    protected static array $translatableProperties = ['title', 'alt', 'caption', 'description'];

    /**
     * The video rules the dashboard's own uploader already enforces
     * (⚡settings.blade.php::saveIntroVideo() validates
     * `mimes:mp4,webm,mov,avi,mkv` + `max:262144`, and the same five types
     * are the accept list on its file input). Mirrored here so this modal
     * cannot be used as a back door around them.
     */
    protected const VIDEO_MIME_TYPES = [
        'video/mp4',
        'video/webm',
        'video/quicktime',
        'video/x-msvideo',
        'video/x-matroska',
    ];

    /** Kilobytes — 256MB, matching `max:262144` and config/livewire.php. */
    protected const VIDEO_MAX_SIZE = 262144;

    /** Memoised so the SVG is only read from disk and encoded once per request. */
    protected static ?string $videoPlaceholderDataUri = null;

    /** Media whose bytes an <img> tag can never render (video files, ...). */
    protected static function isVideo(Media $record): bool
    {
        return str_starts_with((string) $record->mime_type, 'video/');
    }

    /**
     * A data URI holding the Heroicon video-camera glyph, used as the preview
     * for video rows: pointing an <img> at an mp4 only ever yields a broken
     * image. This is a static stand-in, not a frame extracted from the file.
     *
     * The icon comes from the same Heroicon set the rest of the panel uses
     * (blade-heroicons, which Filament's Heroicon enum also resolves to), but
     * `currentColor` has to be swapped for a literal colour: an SVG loaded
     * through <img> is an isolated document and cannot inherit the page's
     * text colour. Zinc-400 stays legible on both the light and dark panel.
     */
    protected static function videoPlaceholderDataUri(): string
    {
        return static::$videoPlaceholderDataUri ??= 'data:image/svg+xml;base64,'.base64_encode(
            str_replace(
                'currentColor',
                '#a1a1aa',
                svg('heroicon-o-'.Heroicon::VideoCamera->value)->contents(),
            )
        );
    }

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

    /**
     * The edit modal's file block. Images keep the display-only Image
     * component they have always had. Videos get a real <video> player plus
     * an upload field: an <img> can never render an mp4, and the modal
     * previously offered no way to swap the file out at all.
     *
     * @return array<Component>
     */
    protected static function fileComponents(Media $record): array
    {
        if (! static::isVideo($record)) {
            return [
                Image::make(
                    fn (Media $record) => $record->getUrl(),
                    fn (Media $record) => $record->name,
                )->imageHeight(200),
            ];
        }

        return [
            // Passed as an HtmlString on purpose: TextEntry (which Placeholder
            // extends) runs a plain HTML string through Str::sanitizeHtml(),
            // whose allowlist does not carry <video>, but hands an Htmlable
            // through untouched. Nothing else escapes the URL, so e() does.
            Placeholder::make('video_preview')
                ->label('ویدیوی فعلی')
                ->content(new HtmlString(
                    '<video controls preload="metadata" style="max-height: 16rem; width: 100%; border-radius: 0.5rem;" src="'
                        .e($record->getUrl()).'"></video>'
                ))
                ->columnSpanFull(),
            // Staged on the private local disk and handed to addMedia() as a
            // real path — the same upload pathway ListMedia's upload action
            // uses. Optional: the modal is still primarily a metadata editor.
            FileUpload::make('video_file')
                ->label('جایگزینی ویدیو')
                ->helperText('حداکثر ۲۵۶ مگابایت — mp4، webm، mov، avi، mkv')
                ->acceptedFileTypes(static::VIDEO_MIME_TYPES)
                ->maxSize(static::VIDEO_MAX_SIZE)
                ->storeFileNamesIn('video_file_name')
                ->disk('local')
                ->visibility('private')
                ->columnSpanFull(),
        ];
    }

    /**
     * Swaps the file behind an existing Media row for a freshly uploaded one.
     *
     * medialibrary has no "replace this row's file" call, so the supported
     * move is to add the new file to the same owner and collection and drop
     * the old row. Collection, disk and custom properties are read off the
     * record rather than re-derived, so a replacement stays exactly where the
     * original lived (s3, per MEDIA_DISK) and keeps its metadata.
     *
     * The old row is re-queried instead of deleted outright because a
     * singleFile() collection — Company::intro_video is one — clears itself
     * during toMediaCollection(), so it may already be gone by this point.
     */
    protected static function replaceFile(Media $record, HasMedia $owner, string $stagedPath, ?string $originalFileName): void
    {
        $oldMediaId = $record->getKey();

        $fileAdder = $owner
            ->addMedia(Storage::disk('local')->path($stagedPath))
            ->withCustomProperties($record->custom_properties);

        if (filled($originalFileName)) {
            $fileAdder->usingFileName($originalFileName);
        }

        $fileAdder->toMediaCollection($record->collection_name, $record->disk);

        Media::query()->whereKey($oldMediaId)->first()?->delete();
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Images keep loading their own file; videos resolve to a
                // blank state so ImageColumn falls back to the icon below
                // instead of trying to paint the mp4 into an <img>.
                ImageColumn::make('preview')
                    ->label('پیش‌نمایش')
                    ->state(fn (Media $record) => static::isVideo($record) ? null : $record->getUrl())
                    ->defaultImageUrl(fn (Media $record): ?string => static::isVideo($record)
                        ? static::videoPlaceholderDataUri()
                        : null)
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
                // ->copyable() is a TextColumn/entry modifier (Support's
                // CanBeCopied concern) — Filament\Actions\Action does not use
                // it, so a row-action button has to run the clipboard call
                // itself. This mirrors Filament's own CopyAction: the JS goes
                // through ->alpineClickHandler() (which also switches off the
                // pointless wire:click round trip), and every value it
                // interpolates goes through Js::from(). That last part is not
                // cosmetic — see the note on Js::from below. No confirmation:
                // the action is side-effect free.
                Action::make('copy_link')
                    ->label('کپی لینک')
                    ->icon(Heroicon::Link)
                    ->color('gray')
                    ->alpineClickHandler(function (Media $record): string {
                        // Js::from() encodes with JSON_HEX_QUOT/APOS/TAG/AMP and
                        // emits a single-quoted literal, so the result carries no
                        // characters that can terminate the surrounding HTML
                        // attribute. Plain json_encode() would emit real double
                        // quotes, which close the attribute early and dump the
                        // rest of the handler into the cell as visible text.
                        $url = Js::from($record->getUrl());
                        $message = Js::from('لینک کپی شد');

                        return <<<JS
                            window.navigator.clipboard.writeText({$url})
                            new FilamentNotification().title({$message}).success().send()
                            JS;
                    }),
                Action::make('edit')
                    ->label('ویرایش')
                    ->modalHeading(fn (Media $record) => $record->name)
                    ->modalWidth('2xl')
                    ->schema(fn (Media $record): array => [
                        ...static::fileComponents($record),
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
                        $upload = $data['video_file'] ?? null;
                        $owner = $record->model;

                        // Checked before anything is written: a replacement
                        // needs an owner to hang the new Media row off, and
                        // half-applying the edit would be worse than refusing.
                        if (filled($upload) && ! $owner instanceof HasMedia) {
                            Notification::make()
                                ->title('این رسانه مالک معتبری ندارد و فایل آن جایگزین نشد')
                                ->danger()
                                ->send();

                            throw new Halt;
                        }

                        foreach (static::customPropertiesFromData($data) as $property => $value) {
                            $record->setCustomProperty($property, $value);
                        }

                        $record->save();

                        if (filled($upload)) {
                            static::replaceFile($record, $owner, $upload, $data['video_file_name'] ?? null);
                        }

                        Notification::make()
                            ->title(filled($upload) ? 'فایل جایگزین و اطلاعات ذخیره شد' : 'اطلاعات رسانه ذخیره شد')
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
