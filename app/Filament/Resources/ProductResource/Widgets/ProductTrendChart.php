<?php

namespace App\Filament\Resources\ProductResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use Carbon\CarbonPeriod;

class ProductTrendChart extends ChartWidget
{
    protected ?string $heading = 'Trend Penjualan';

    protected int|string|array $columnSpan = 2;

    public ?Model $record = null;

    protected function getData(): array
    {
        if (!$this->record instanceof Product) {
            return ['datasets' => [], 'labels' => []];
        }

        $now = now();
        $start = $now->copy()->subMonths(5)->startOfMonth();
        $end = $now->copy()->endOfMonth();

        $labels = [];
        $salesData = [];
        $purchaseData = [];

        $period = CarbonPeriod::create($start, '1 month', $end);

        foreach ($period as $date) {
            $key = $date->format('Y-m');
            $labels[] = $date->format('M Y');
            $salesData[$key] = 0;
            $purchaseData[$key] = 0;
        }

        // Query Sales
        $sales = $this->record->salesInvoiceItems()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(qty) as total")
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Query Purchases
        $purchases = $this->record->purchaseInvoiceItems()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(quantity) as total")
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Fill data
        $finalSales = [];
        $finalPurchases = [];

        foreach ($period as $date) {
            $key = $date->format('Y-m');
            $finalSales[] = $sales[$key] ?? 0;
            $finalPurchases[] = $purchases[$key] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Terjual (Sales)',
                    'data' => $finalSales,
                    'backgroundColor' => '#2dce89', // Green
                    'barThickness' => 20,
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Dibeli (Purchases)',
                    'data' => $finalPurchases,
                    'backgroundColor' => '#5e72e4', // Blue
                    'barThickness' => 20,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
