<?php

namespace App\Filament\Resources\Plans\Pages;

use App\Filament\Resources\Plans\PlanResource;
use App\Models\Plan;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    protected static array $translatableImageProperties = ['title', 'alt'];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editImageMetadata')
                ->label('ویرایش متادیتای تصویر')
                ->icon('heroicon-o-photo')
                ->modalHeading('متادیتای تصویر پلن')
                ->visible(fn (Plan $record) => $record->getFirstMedia('image') !== null)
                ->schema(function () {
                    $locales = config('laravellocalization.supportedLocales');

                    return [
                        Tabs::make('image_metadata_translations')
                            ->tabs(
                                collect($locales)->map(
                                    fn ($data, string $code) => Tab::make($code)
                                        ->label($data['native'])
                                        ->schema([
                                            TextInput::make("title.{$code}")
                                                ->label('عنوان تصویر')
                                                ->maxLength(255),
                                            TextInput::make("alt.{$code}")
                                                ->label('متن جایگزین (Alt)')
                                                ->maxLength(255),
                                        ])
                                        ->columns(2)
                                )->values()->all()
                            )
                            ->columnSpanFull(),
                    ];
                })
                ->fillForm(function (Plan $record) {
                    $media = $record->getFirstMedia('image');
                    $data = [];

                    foreach (static::$translatableImageProperties as $property) {
                        $data[$property] = $media?->getCustomProperty($property, []);
                    }

                    return $data;
                })
                ->action(function (array $data, Plan $record) {
                    $media = $record->getFirstMedia('image');

                    foreach (static::$translatableImageProperties as $property) {
                        $media->setCustomProperty($property, $data[$property] ?? []);
                    }

                    $media->save();

                    Notification::make()
                        ->title('متادیتای تصویر ذخیره شد')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
