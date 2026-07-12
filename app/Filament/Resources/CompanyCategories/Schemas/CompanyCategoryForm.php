<?php

namespace App\Filament\Resources\CompanyCategories\Schemas;

use App\Models\CompanyCategory;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class CompanyCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        $locales = config('laravellocalization.supportedLocales');

        return $schema
            ->columns(1)
            ->components([
                Section::make('اطلاعات دسته‌بندی')
                    ->schema([
                        Select::make('parent_id')
                            ->label('دسته‌بندی والد')
                            ->options(
                                fn (?CompanyCategory $record) => CompanyCategory::query()
                                    ->when(
                                        $record,
                                        fn ($query) => $query->whereKeyNot(
                                            $record->descendantsAndSelf()->pluck('id')
                                        ),
                                    )
                                    ->get()
                                    ->pluck('title', 'id'),
                            )
                            ->searchable()
                            ->native(false),
                        TextInput::make('slug')
                            ->label('نامک (Slug)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('فقط انگلیسی — برای آدرس‌های سئوپسند'),
                        TextInput::make('sort_order')
                            ->label('ترتیب نمایش')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Toggle::make('is_active')
                            ->label('فعال')
                            ->default(true),
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
                    ])
                    ->columns(2),

                Tabs::make('translations')
                    ->tabs(
                        collect($locales)->map(
                            fn ($data, string $code) => Tab::make($code)
                                ->label($data['native'])
                                ->schema([
                                    TextInput::make("title.{$code}")
                                        ->label('عنوان')
                                        ->required($code === config('app.fallback_locale'))
                                        ->maxLength(255),
                                    RichEditor::make("description.{$code}")
                                        ->label('توضیحات')
                                        ->fileAttachmentsDisk('s3')
                                        ->fileAttachmentsDirectory("companies/{$code}")
                                        ->fileAttachmentsVisibility('public')
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                        )->values()->all()
                    )
                    ->columnSpanFull(),
            ]);
    }
}
