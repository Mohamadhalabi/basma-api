<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label('رمز المنتج')
                    ->searchable(),
                TextColumn::make('title')
                    ->label('اسم المنتج')
                    ->searchable(),
                TextColumn::make('manufacturer.name')
                    ->label('الشركة المصنعة')
                    ->searchable(),
                // Stored as halalas; divide by 100 to show SAR
                TextColumn::make('default_price')
                    ->label('السعر')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2) . ' ر.س')
                    ->sortable(),
                TextColumn::make('stock_quantity')
                    ->label('المخزون')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('مفعّل')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}