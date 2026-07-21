<?php

namespace App\Filament\Resources\Plans\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Laravelcm\Subscriptions\Interval;

class FeaturesRelationManager extends RelationManager
{
    protected static string $relationship = 'features';

    protected static ?string $title = 'ویژگی‌های پلن';

    protected static ?string $modelLabel = 'ویژگی';

    protected static ?string $pluralModelLabel = 'ویژگی‌ها';

    /**
     * @return array<string, string>
     */
    protected static function intervalOptions(): array
    {
        return [
            Interval::DAY->value => 'روز',
            Interval::MONTH->value => 'ماه',
            Interval::YEAR->value => 'سال',
        ];
    }

    public function form(Schema $schema): Schema
    {
        $locales = config('laravellocalization.supportedLocales');

        return $schema
            ->columns(1)
            ->components([
                Section::make('اطلاعات پایه')
                    ->schema([
                        TextInput::make('slug')
                            ->label('نامک (Slug)')
                            ->required()
                            ->maxLength(255)
                            ->helperText('فقط انگلیسی — کلید یکتای ویژگی که در کد برای بررسی دسترسی استفاده می‌شود (مثال: products-limit).'),
                        TextInput::make('value')
                            ->label('مقدار')
                            ->required()
                            ->maxLength(255)
                            ->helperText('عدد برای محدودیت‌های شمارشی (مثلا 50)، true/false برای ویژگی‌های بولی.'),
                        TextInput::make('sort_order')
                            ->label('ترتیب نمایش')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('بازنشانی مصرف (Resettable)')
                    ->schema([
                        TextInput::make('resettable_period')
                            ->label('طول دوره بازنشانی')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->helperText('عدد صفر یعنی مصرف این ویژگی هرگز بازنشانی نمی‌شود.'),
                        Select::make('resettable_interval')
                            ->label('واحد دوره بازنشانی')
                            ->options(self::intervalOptions())
                            ->default(Interval::MONTH->value)
                            ->required()
                            ->native(false),
                    ])
                    ->description('برای ویژگی‌های مصرفی (مانند تعداد محصول قابل ثبت در ماه)، دوره‌ای که شمارنده‌ی مصرف صفر می‌شود.')
                    ->columns(2),

                Tabs::make('translations')
                    ->tabs(
                        collect($locales)->map(
                            fn ($data, string $code) => Tab::make($code)
                                ->label($data['native'])
                                ->schema([
                                    TextInput::make("name.{$code}")
                                        ->label('نام ویژگی')
                                        ->required($code === config('app.fallback_locale'))
                                        ->maxLength(255),
                                    Textarea::make("description.{$code}")
                                        ->label('توضیحات')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ])
                                ->columns(1)
                        )->values()->all()
                    )
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('نام')
                    ->state(fn ($record) => $record->name),
                TextColumn::make('slug')
                    ->label('نامک (Slug)')
                    ->searchable(),
                TextColumn::make('value')
                    ->label('مقدار'),
                TextColumn::make('resettable_period')
                    ->label('بازنشانی')
                    ->formatStateUsing(fn ($record) => $record->resettable_period > 0
                        ? $record->resettable_period.' '.self::intervalOptions()[$record->resettable_interval->value ?? $record->resettable_interval]
                        : 'بدون بازنشانی'),
                TextColumn::make('sort_order')
                    ->label('ترتیب')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
