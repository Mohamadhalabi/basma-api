<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('رقم الطلب')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('النوع')
                    ->badge(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
                TextColumn::make('payment_method')
                    ->label('طريقة الدفع')
                    ->badge(),
                TextColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->badge(),
                TextColumn::make('subtotal')
                    ->label('المجموع الفرعي')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2) . ' ر.س')
                    ->sortable(),
                TextColumn::make('vat_amount')
                    ->label('قيمة الضريبة')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2) . ' ر.س')
                    ->sortable(),
                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2) . ' ر.س')
                    ->sortable(),
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
            ->recordUrl(
                fn ($record) => '/admin/create-order-builder?order=' . $record->id
            )
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\Action::make('editBuilder')
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => '/admin/create-order-builder?order=' . $record->id),
                \Filament\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn ($record) => '/admin/invoice/' . $record->id)
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}