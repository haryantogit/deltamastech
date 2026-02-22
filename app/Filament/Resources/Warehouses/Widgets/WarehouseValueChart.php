<?php

namespace App\Filament\Resources\Warehouses\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class WarehouseValueChart extends ChartWidget
{
    protected ?string $heading = 'NILAI PRODUK PER GUDANG';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $data = DB::table('stocks')
            ->join('warehouses', 'stocks.warehouse_id', '=', 'warehouses.id')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->selectRaw('warehouses.name, SUM(stocks.quantity * products.buy_price) as total_value')
            ->groupBy('warehouses.name')
            ->orderByDesc('total_value')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Nilai Produk (HPP)',
                    'data' => $data->pluck('total_value'),
                    'backgroundColor' => [
                        '#F87171', // Red
                        '#FBBF24', // Yellow
                        '#34D399', // Green
                        '#60A5FA', // Blue
                        '#A78BFA', // Purple
                    ],
                ],
            ],
            'labels' => $data->pluck('name'),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
