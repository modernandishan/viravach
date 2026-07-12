<?php

namespace App\Filament\Resources\Media\Tables;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Image;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaTable
{
    protected static array $translatableProperties = ['title', 'alt', 'caption', 'description'];

    public static function configure(Table $table): Table
    {
        $locales = config('laravellocalization.supportedLocales');

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
                Action::make('edit')
                    ->label('ویرایش')
                    ->modalHeading(fn (Media $record) => $record->name)
                    ->modalWidth('2xl')
                    ->schema(fn () => [
                        Image::make(
                            fn (Media $record) => $record->getUrl(),
                            fn (Media $record) => $record->name,
                        )->imageHeight(200),
                        Tabs::make('translations')
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
                            ->columnSpanFull(),
                    ])
                    ->fillForm(function (Media $record) {
                        $data = [];

                        foreach (static::$translatableProperties as $property) {
                            $data[$property] = $record->getCustomProperty($property, []);
                        }

                        return $data;
                    })
                    ->action(function (array $data, Media $record) {
                        foreach (static::$translatableProperties as $property) {
                            $record->setCustomProperty($property, $data[$property] ?? []);
                        }

                        $record->save();

                        Notification::make()
                            ->title('اطلاعات رسانه ذخیره شد')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordAction('edit');
    }
}
