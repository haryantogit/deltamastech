<?php

namespace App\Filament\Resources\ProductResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class ProductPerformanceChart extends ChartWidget
{
    protected ?string $heading = 'Performa Produk (12 Bulan Terakhir)';

    protected int|string|array $columnSpan = 2;

    public ?Model $record = null;

    protected function getData(): array
    {
        if (!$this->record instanceof Product) {
            return ['datasets' => [], 'labels' => []];
        }

        $now = now();
        $start = $now->copy()->subMonths(11)->startOfMonth();
        $end = $now->copy()->endOfMonth();

        $months = [];
        $soldData = [];
        $purchasedData = [];

        $period = \Carbon\CarbonPeriod::create($start, '1 month', $end);

        foreach ($period as $date) {
            $months[] = $date->format('M Y');
            $soldData[$date->format('Y-m')] = 0;
            $purchasedData[$date->format('Y-m')] = 0;
        }

        // Query Sold (Out/Sale)
        $sold = $this->record->stockMovements()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(quantity) as total")
            ->whereIn('type', ['out', 'sale', 'adjustment_minus'])
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('month')
            ->get()
            ->pluck('total', 'month');

        // Query Purchased (In/Purchase)
        $purchased = $this->record->stockMovements()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(quantity) as total")
            ->whereIn('type', ['in', 'purchase', 'adjustment_plus'])
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('month')
            ->get()
            ->pluck('total', 'month');

        // Merge Data
        foreach ($sold as $month => $total) {
            if (isset($soldData[$month]))
                $soldData[$month] = $total;
        }
        foreach ($purchased as $month => $total) {
            if (isset($purchasedData[$month]))
                $purchasedData[$month] = $total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Sold Qty',
                    'data' => array_values($soldData),
                    'borderColor' => '#f5365c',
                    'backgroundColor' => 'rgba(245, 54, 92, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Purchased Qty',
                    'data' => array_values($purchasedData),
                    'borderColor' => '#2dce89',
                    'backgroundColor' => 'rgba(45, 206, 137, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
