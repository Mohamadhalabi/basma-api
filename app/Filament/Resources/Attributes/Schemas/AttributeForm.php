<?php

namespace App\Filament\Resources\Attributes\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AttributeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم الخاصية')
                    ->placeholder('مثال: التردد، عدد الأزرار، نوع الشريحة')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->label('الرابط (Slug)')
                    ->required(),
                Toggle::make('is_filterable')
                    ->label('قابل للتصفية في المتجر')
                    ->default(true),
                Repeater::make('values')
                    ->label('القيم')
                    ->relationship('values')
                    ->schema([
                        TextInput::make('value')
                            ->label('القيمة')
                            ->placeholder('مثال: 433MHz')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                        TextInput::make('slug')
                            ->label('الرابط (Slug)')
                            ->required(),
                    ])
                    ->columns(2)
                    ->orderColumn('sort_order')
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['value'] ?? 'قيمة جديدة')
                    ->addActionLabel('إضافة قيمة')
                    ->columnSpanFull(),
            ]);
    }
}