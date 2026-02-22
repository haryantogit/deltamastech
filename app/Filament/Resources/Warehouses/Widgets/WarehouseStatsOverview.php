<?php

namespace App\Filament\Resources\Warehouses\Widgets;

use App\Models\Stock;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class WarehouseStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // 1. Total Stock
        $totalStock = Stock::sum('quantity');

        // 2. Total HPP (Asset Value: Qty * Buy Price)
        $totalHpp = DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->selectRaw('SUM(stocks.quantity * products.buy_price) as total')
            ->value('total') ?? 0;

        // 3. Total unique products in stock
        $totalSku = DB::table('stocks')->where('quantity', '>', 0)->distinct()->count('product_id');

        return [
            Stat::make('Total Stok', number_format($totalStock, 0, ',', '.'))
                ->description('Total Unit')
                ->descriptionIcon('heroicon-o-cube')
                ->color('success'), // Green

            Stat::make('Total Nilai Aset', $this->formatMoneyShort($totalHpp))
                ->description('Berdasarkan HPP')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('warning'), // Yellow/Orange

            Stat::make('Total SKU', $totalSku)
                ->description('Jenis Produk Ada')
                ->descriptionIcon('heroicon-o-tag')
                ->color('primary'), // Blue
        ];
    }

    protected function formatMoneyShort(float $amount): string
    {
        if ($amount >= 1000000000) {
            return 'Rp ' . number_format($amount / 1000000000, 2, ',', '.') . ' M';
        }
        if ($amount >= 1000000) {
            return 'Rp ' . number_format($amount / 1000000, 2, ',', '.') . ' Jt';
        }
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
