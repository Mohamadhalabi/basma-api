<?php

namespace App\Filament\Resources\Attributes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttributesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الخاصية')
                    ->searchable(),
                TextColumn::make('values_count')
                    ->label('عدد القيم')
                    ->counts('values'),
                IconColumn::make('is_filterable')
                    ->label('قابل للتصفية')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make()->label('تعديل'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}