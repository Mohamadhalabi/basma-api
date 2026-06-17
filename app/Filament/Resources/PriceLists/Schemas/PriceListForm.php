<?php

namespace App\Filament\Resources\PriceLists\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PriceListForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم القائمة')
                    ->placeholder('مثال: أسعار الجملة، عملاء مميزون')
                    ->required(),
                Toggle::make('is_active')
                    ->label('مفعّلة')
                    ->default(true),
            ]);
    }
}