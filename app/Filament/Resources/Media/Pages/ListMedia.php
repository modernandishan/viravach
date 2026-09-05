<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use App\Filament\Resources\Media\Tables\MediaTable;
use App\Models\MediaLibraryEntry;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Facades\Storage;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Manual library upload. Media rows need a polymorphic owner, so
            // uploads attach to the single shared MediaLibraryEntry row; the
            // per-locale metadata block is the same schema MediaTable's edit
            // action uses and is stored as the new Media rows' custom
            // properties. A custom action instead of canCreate()/CreateAction:
            // the resource has no create route (Media rows come from their
            // owners), and the resource model itself is never instantiated.
            Action::make('upload')
                ->label('آپلود رسانه')
                ->modalHeading('آپلود رسانه')
                ->modalWidth('2xl')
                ->schema([
                    Tabs::make('uploads')
                        ->tabs([
                            Tab::make('images')
                                ->label('تصاویر')
                                ->schema([
                                    FileUpload::make('image_files')
                                        ->label('تصاویر')
                                        ->image()
                                        ->multiple()
                                        ->maxFiles(10)
                                        ->maxSize(4096)
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])
                                        ->disk('local')
                                        ->visibility('private')
                                        ->required(),
                                ]),
                            Tab::make('files')
                                ->label('فایل‌ها')
                                ->schema([
                                    FileUpload::make('other_files')
                                        ->label('فایل‌ها')
                                        ->multiple()
                                        ->maxFiles(10)
                                        ->maxSize(10240)
                                        ->disk('local')
                                        ->visibility('private'),
                                ]),
                        ])
                        ->columnSpanFull(),
                    MediaTable::translatableTabs(),
                ])
                ->action(function (array $data) {
                    $entry = MediaLibraryEntry::shared();
                    $properties = MediaTable::customPropertiesFromData($data);
                    $count = 0;

                    foreach (['library_images' => 'image_files', 'library_files' => 'other_files'] as $collection => $field) {
                        foreach ($data[$field] ?? [] as $path) {
                            $entry
                                ->addMedia(Storage::disk('local')->path($path))
                                ->withCustomProperties($properties)
                                ->toMediaCollection($collection);

                            $count++;
                        }
                    }

                    Notification::make()
                        ->title($count.' فایل آپلود شد')
                        ->success()
                        ->send();
                }),
        ];
    }
}
