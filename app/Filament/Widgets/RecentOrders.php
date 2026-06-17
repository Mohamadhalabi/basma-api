<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentOrders extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'أحدث الطلبات';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()->latest()->limit(10)
            )
            ->columns([
                TextColumn::make('number')->label('رقم الطلب'),
                TextColumn::make('customer.name')->label('العميل'),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2) . ' ر.س'),
                TextColumn::make('created_at')->label('التاريخ')->dateTime('Y-m-d H:i'),
            ])
            ->paginated(false)
            ->recordUrl(fn ($record) => '/admin/create-order-builder?order=' . $record->id);
    }
}