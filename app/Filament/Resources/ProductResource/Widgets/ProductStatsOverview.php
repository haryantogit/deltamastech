<?php

namespace App\Filament\Resources\ProductResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductStatsOverview extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public ?Model $record = null;

    protected function getStats(): array
    {
        if (!$this->record instanceof Product) {
            return [];
        }

        $product = $this->record;

        // 1. Stok di Tangan
        $stokDiTangan = (float) $product->stock;

        // 2. Total Penjualan
        $totalPenjualan = 0;
        if (method_exists($product, 'salesInvoiceItems')) {
            $totalPenjualan = $product->salesInvoiceItems()->sum(DB::raw('qty * price'));
        }

        // 3. Total Pembelian
        $totalPembelian = 0;
        if (method_exists($product, 'purchaseInvoiceItems')) {
            $totalPembelian = $product->purchaseInvoiceItems()->sum(DB::raw('quantity * unit_price'));
        }

        // 4. HPP (Current Product COGS)
        $hpp = (float) $product->cost_of_goods;

        return [
            Stat::make('Stok di tangan', number_format($stokDiTangan, 2, ',', '.'))
                ->description('Jumlah stok saat ini')
                ->color('danger'),
            Stat::make('Penjualan', 'Rp ' . number_format($totalPenjualan, 0, ',', '.'))
                ->description('Total nilai penjualan')
                ->color('warning'),
            Stat::make('Pembelian', 'Rp ' . number_format($totalPembelian, 0, ',', '.'))
                ->description('Total nilai pembelian')
                ->color('success'),
            Stat::make('HPP', 'Rp ' . number_format($hpp, 0, ',', '.'))
                ->description('Nilai HPP produk')
                ->color('primary'),
        ];
    }
}
