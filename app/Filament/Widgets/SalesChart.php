<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SalesChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'المبيعات خلال آخر 30 يوماً';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $labels = [];
        $data = [];

        for ($i = 29; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $sum = Order::where('type', '!=', 'proforma')
                ->whereDate('created_at', $day)
                ->sum('total') / 100;
            $labels[] = $day->format('m/d');
            $data[] = $sum;
        }

        return [
            'datasets' => [
                [
                    'label' => 'المبيعات (ر.س)',
                    'data' => $data,
                    'borderColor' => '#0F7A3D',
                    'backgroundColor' => 'rgba(15,122,61,0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}