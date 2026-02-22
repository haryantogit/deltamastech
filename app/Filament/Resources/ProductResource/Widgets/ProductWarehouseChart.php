<?php

namespace App\Filament\Resources\ProductResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class ProductWarehouseChart extends ChartWidget
{
    protected ?string $heading = 'Stok di Gudang';

    protected int|string|array $columnSpan = 1;

    public ?Model $record = null;

    protected function getData(): array
    {
        if (!$this->record instanceof Product) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $stocks = $this->record->stocks()->with('warehouse')->get();

        $labels = [];
        $data = [];
        $backgroundColors = [];

        $bgPalette = [
            '#6366f1', // Indigo 500
            '#10b981', // Emerald 500
            '#f59e0b', // Amber 500
            '#ef4444', // Red 500
            '#8b5cf6', // Violet 500
            '#ec4899', // Pink 500
            '#3b82f6', // Blue 500
            '#14b8a6', // Teal 500
        ];

        $totalAssigned = 0;

        foreach ($stocks as $index => $stock) {
            $name = $stock->warehouse->name;
            if ($name === 'Unassigned' || $stock->warehouse_id === null) {
                $name = 'Tanpa Gudang';
            }

            if ($stock->quantity == 0)
                continue;

            $totalAssigned += $stock->quantity;
            $labels[] = $name;
            $data[] = $stock->quantity;
            $backgroundColors[] = $bgPalette[$index % count($bgPalette)];
        }

        // Calculate unassigned (residual) stock
        $totalStock = $this->record->stock;
        $unassignedStock = $totalStock - $totalAssigned;

        if ($unassignedStock > 0) {
            $labels[] = 'Tanpa Gudang';
            $data[] = $unassignedStock;
            $backgroundColors[] = '#94a3b8'; // Slate 400 for unassigned
        } else if ($unassignedStock < 0) {
            // Should not happen theoretically if data is consistent, but just in case
            // checking for negative unassigned means we have more in warehouses than in total ??
        }

        return [
            'datasets' => [
                [
                    'label' => 'Quantity',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'layout' => [
                'padding' => 20,
            ],
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'cutout' => '60%',
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
