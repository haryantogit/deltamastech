<?php

namespace App\Filament\Resources\ProductResource\Widgets;

use App\Models\Product;
use App\Models\Stock;
use App\Models\SalesInvoiceItem;
use App\Models\PurchaseInvoiceItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductListStatsOverview extends BaseWidget
{
    protected int|array|null $columns = 4;

    protected function getStats(): array
    {
        // Stock availability counts
        // Registered Fixed Assets are included in the product list now, so they count towards stats
        $query = Product::where(
            fn($q) =>
            $q->where('is_fixed_asset', false)
                ->orWhere(fn($sq) => $sq->where('is_fixed_asset', true)->where('status', 'registered'))
        );

        $stockAvailable = (clone $query)->where('stock', '>', 10)->count();
        $stockLow = (clone $query)->whereBetween('stock', [1, 10])->count();
        $stockOut = (clone $query)->where('stock', '<=', 0)->count();

        // Total stock across all warehouses (for visible products)
        $visibleProductIds = (clone $query)->pluck('id');
        $totalStock = Stock::whereIn('product_id', $visibleProductIds)->sum('quantity');

        // Total Nilai Produk (Potential Sales Value: stock * sell_price)
        $totalNilaiProduk = \DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->whereIn('products.id', $visibleProductIds)
            ->selectRaw('SUM(stocks.quantity * products.sell_price) as total')
            ->value('total') ?? 0;

        // Total HPP (Cost Value: stock * buy_price)
        $totalHPP = \DB::table('stocks')
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->whereIn('products.id', $visibleProductIds)
            ->selectRaw('SUM(stocks.quantity * products.buy_price) as total')
            ->value('total') ?? 0;

        // Total Sales Amount and Qty
        $salesData = SalesInvoiceItem::selectRaw('SUM(qty * price) as total_amount, SUM(qty) as total_qty')
            ->first();
        $totalSalesAmount = $salesData->total_amount ?? 0;
        $totalSalesQty = $salesData->total_qty ?? 0;

        // Total Purchase Amount and Qty
        $purchaseData = PurchaseInvoiceItem::selectRaw('SUM(quantity * unit_price) as total_amount, SUM(quantity) as total_qty')
            ->first();
        $totalPurchaseAmount = $purchaseData->total_amount ?? 0;
        $totalPurchaseQty = $purchaseData->total_qty ?? 0;

        // Total unique products
        $totalProducts = $query->count();

        return [
            Stat::make('Produk Stok Tersedia', $stockAvailable)
                ->description('Stok > 10')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->extraAttributes(['class' => 'stat-success stat-card-hover']),

            Stat::make('Produk Stok Hampir Habis', $stockLow)
                ->description('Stok 1-10')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->extraAttributes(['class' => 'stat-warning stat-card-hover']),

            Stat::make('Produk Stok Habis', $stockOut)
                ->description('Stok ≤ 0')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger')
                ->extraAttributes(['class' => 'stat-danger stat-card-hover']),

            Stat::make('Total Stok', number_format($totalStock, 0, ',', '.'))
                ->description('Semua Gudang')
                ->descriptionIcon('heroicon-o-cube')
                ->color('info')
                ->extraAttributes(['class' => 'stat-info stat-card-hover']),

            Stat::make('Total Nilai Produk', 'Rp ' . number_format($totalNilaiProduk, 0, ',', '.'))
                ->description('Total Nilai Jual')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('primary')
                ->extraAttributes(['class' => 'stat-primary stat-card-hover']),

            Stat::make('Total HPP', 'Rp ' . number_format($totalHPP, 0, ',', '.'))
                ->description('Total Nilai Beli')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success')
                ->extraAttributes(['class' => 'stat-success stat-card-hover']),

            Stat::make('Total Penjualan', 'Rp ' . number_format($totalSalesAmount, 0, ',', '.'))
                ->description(number_format($totalSalesQty, 0, ',', '.') . ' Terjual')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('secondary')
                ->extraAttributes(['class' => 'stat-secondary stat-card-hover']),

            Stat::make('Total Pembelian', 'Rp ' . number_format($totalPurchaseAmount, 0, ',', '.'))
                ->description(number_format($totalPurchaseQty, 0, ',', '.') . ' Dibeli')
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('warning')
                ->extraAttributes(['class' => 'stat-warning stat-card-hover']),

            Stat::make('Total Jenis Produk', $totalProducts)
                ->description('Jenis Produk')
                ->descriptionIcon('heroicon-o-rectangle-stack')
                ->color('gray')
                ->extraAttributes(['class' => 'stat-gray stat-card-hover']),
        ];
    }
}
