<?php

namespace App\Filament\Resources\CompanyPublications\Schemas;

use App\Models\CompanyPublication;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyPublicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('اطلاعات پایه')
                    ->schema([
                        TextEntry::make('name')
                            ->label('نام')
                            ->state(fn (CompanyPublication $record) => $record->name),
                        TextEntry::make('slug')
                            ->label('نامک (Slug)'),
                        TextEntry::make('legal_name')
                            ->label('نام حقوقی')
                            ->state(fn (CompanyPublication $record) => $record->legal_name)
                            ->placeholder('—'),
                        TextEntry::make('legal_type')
                            ->label('نوع حقوقی')
                            ->placeholder('—'),
                        TextEntry::make('registration_number')
                            ->label('شماره ثبت')
                            ->placeholder('—'),
                        TextEntry::make('national_id')
                            ->label('شناسه ملی')
                            ->placeholder('—'),
                        TextEntry::make('employee_range')
                            ->label('بازه تعداد کارکنان')
                            ->placeholder('—'),
                        TextEntry::make('established_at')
                            ->label('تاریخ تأسیس')
                            ->jalaliDate()
                            ->placeholder('—'),
                    ])
                    ->columns(2),

                Section::make('انتشار')
                    ->schema([
                        TextEntry::make('company.slug')
                            ->label('شرکت مبدأ')
                            ->placeholder('حذف‌شده'),
                        TextEntry::make('published_at')
                            ->label('تاریخ انتشار')
                            ->jalaliDateTime(),
                        IconEntry::make('is_verified')
                            ->label('تأیید شده')
                            ->boolean(),
                        IconEntry::make('is_featured')
                            ->label('ویژه')
                            ->boolean(),
                    ])
                    ->columns(2),

                Section::make('تماس و دسته‌بندی')
                    ->schema([
                        TextEntry::make('website')
                            ->label('وب‌سایت')
                            ->placeholder('—'),
                        TextEntry::make('email')
                            ->label('ایمیل')
                            ->placeholder('—'),
                        TextEntry::make('phones')
                            ->label('شماره‌های تماس')
                            ->placeholder('—'),
                        TextEntry::make('state.name')
                            ->label('استان')
                            ->placeholder('—'),
                        TextEntry::make('categories')
                            ->label('دسته‌بندی‌ها')
                            ->state(fn (CompanyPublication $record) => $record->categories->map(fn ($category) => $category->title)->join('، '))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('محتوا')
                    ->schema([
                        TextEntry::make('summary')
                            ->label('خلاصه')
                            ->state(fn (CompanyPublication $record) => $record->summary)
                            ->placeholder('—'),
                        TextEntry::make('description')
                            ->label('توضیحات')
                            ->state(fn (CompanyPublication $record) => $record->description)
                            ->html(),
                    ]),

                Section::make('تصاویر')
                    ->schema([
                        SpatieMediaLibraryImageEntry::make('logo')
                            ->label('لوگو')
                            ->collection('logo')
                            ->conversion('webp')
                            ->visibility('public')
                            ->placeholder('—'),
                        SpatieMediaLibraryImageEntry::make('gallery')
                            ->label('گالری تصاویر')
                            ->collection('gallery')
                            ->conversion('webp')
                            ->visibility('public')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
