<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $realOrders = Order::where('type', '!=', 'proforma');

        $salesHalalas = (clone $realOrders)->sum('total');
        $salesSar = number_format($salesHalalas / 100, 2);
        $ordersCount = (clone $realOrders)->count();
        $customersCount = Customer::count();
        $productsCount = Product::count();
        $lowStockCount = Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('low_stock_threshold', '>', 0)
            ->count();

        $dailySales = [];
        $dailyOrders = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $q = Order::where('type', '!=', 'proforma')->whereDate('created_at', $day);
            $dailySales[] = (clone $q)->sum('total') / 100;
            $dailyOrders[] = (clone $q)->count();
        }

        return [
            Stat::make('إجمالي المبيعات', $salesSar . ' ر.س')
                ->description('قيمة الطلبات الفعلية')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($dailySales)
                ->color('success'),

            Stat::make('عدد الطلبات', $ordersCount)
                ->description('آخر 7 أيام')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->chart($dailyOrders)
                ->color('primary'),

            Stat::make('العملاء', $customersCount)
                ->description('إجمالي العملاء')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('المنتجات', $productsCount)
                ->description($lowStockCount > 0 ? "{$lowStockCount} منتج بمخزون منخفض" : 'المخزون جيد')
                ->descriptionIcon($lowStockCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($lowStockCount > 0 ? 'warning' : 'gray'),
        ];
    }
}