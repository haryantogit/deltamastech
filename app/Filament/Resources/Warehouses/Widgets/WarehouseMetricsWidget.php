<?php

namespace App\Filament\Resources\Warehouses\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WarehouseMetricsWidget extends Widget
{
    protected string $view = 'filament.resources.warehouses.widgets.warehouse-metrics-widget';

    public ?Model $record = null;

    protected function getViewData(): array
    {
        if (!$this->record) {
            return [
                'totalStock' => 0,
                'totalValue' => 0,
                'averageHpp' => 0,
            ];
        }

        $totalStock = DB::table('stocks')
            ->where('warehouse_id', $this->record->id)
            ->sum('quantity');

        $totalValue = DB::table('stocks')
            ->where('warehouse_id', $this->record->id)
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->selectRaw('SUM(stocks.quantity * products.buy_price) as total')
            ->value('total') ?? 0;

        $averageHpp = $totalStock > 0 ? $totalValue / $totalStock : 0;

        return [
            'totalStock' => $totalStock,
            'totalValue' => $totalValue,
            'averageHpp' => $averageHpp,
        ];
    }
}
