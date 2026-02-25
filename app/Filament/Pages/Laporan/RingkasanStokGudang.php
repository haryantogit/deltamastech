<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Stock;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class RingkasanStokGudang extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected string $view = 'filament.pages.laporan.ringkasan-stok-gudang';

    protected static ?string $title = 'Ringkasan Stok Gudang';

    protected static ?string $slug = 'ringkasan-stok-gudang';

    protected static bool $shouldRegisterNavigation = false;

    public $search = '';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Ringkasan Stok Gudang',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ekspor')
                ->label('Ekspor')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray'),
            Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn() => $this->js('window.print()')),
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(\App\Filament\Pages\ReportPage::getUrl()),
        ];
    }

    public function getViewData(): array
    {
        $warehouses = Warehouse::orderBy('name')->get();

        $productsQuery = Product::query()
            ->with(['unit'])
            ->where('track_inventory', true)
            ->where('is_fixed_asset', false)
            ->where('is_active', true);

        if ($this->search) {
            $productsQuery->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        $products = $productsQuery->orderBy('name')->get();

        // Fetch all stocks for these products and warehouses
        $stocks = Stock::whereIn('product_id', $products->pluck('id'))
            ->get()
            ->groupBy('product_id');

        $rows = $products->map(function ($product) use ($warehouses, $stocks) {
            $productStocks = $stocks->get($product->id) ?? collect();

            $warehouseQuantities = [];
            $rowTotal = 0;

            foreach ($warehouses as $warehouse) {
                $qty = (float) ($productStocks->firstWhere('warehouse_id', $warehouse->id)->quantity ?? 0);
                $warehouseQuantities[$warehouse->id] = $qty;
                $rowTotal += $qty;
            }

            return (object) [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'quantities' => $warehouseQuantities,
                'total' => $rowTotal,
            ];
        });

        // Calculate totals per warehouse
        $warehouseTotals = [];
        $grandTotal = 0;
        foreach ($warehouses as $warehouse) {
            $total = $rows->sum(fn($r) => $r->quantities[$warehouse->id]);
            $warehouseTotals[$warehouse->id] = $total;
            $grandTotal += $total;
        }

        return [
            'warehouses' => $warehouses,
            'rows' => $rows,
            'warehouseTotals' => $warehouseTotals,
            'grandTotal' => $grandTotal,
        ];
    }
}
