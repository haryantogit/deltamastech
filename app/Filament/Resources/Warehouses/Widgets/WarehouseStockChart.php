<?php

namespace App\Filament\Resources\Warehouses\Widgets;

use App\Models\Stock;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class WarehouseStockChart extends ChartWidget
{
    protected ?string $heading = 'STOK PER GUDANG';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $data = DB::table('stocks')
            ->join('warehouses', 'stocks.warehouse_id', '=', 'warehouses.id')
            ->selectRaw('warehouses.name, SUM(stocks.quantity) as total')
            ->groupBy('warehouses.name')
            ->orderByDesc('total')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Stok',
                    'data' => $data->pluck('total'),
                    'backgroundColor' => [
                        '#F87171', // Red (Unassigned)
                        '#FBBF24', // Yellow (Gudang Retur)
                        '#34D399', // Green/Cyan (GUDANG JT) - Adjusted to be distinct
                        '#60A5FA', // Blue (Gudang Sample)
                        '#A78BFA', // Purple/Brown (Gudang Riject)
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
