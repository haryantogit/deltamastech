<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class PerputaranPersediaan extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected string $view = 'filament.pages.laporan.perputaran-persediaan';

    protected static ?string $title = 'Perputaran Persediaan Barang';

    protected static ?string $slug = 'perputaran-persediaan';

    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $search = '';
    public $expandedRows = [];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function toggleRow($id): void
    {
        if (in_array($id, $this->expandedRows)) {
            $this->expandedRows = array_diff($this->expandedRows, [$id]);
        } else {
            $this->expandedRows[] = $id;
        }
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Perputaran Persediaan Barang',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter')
                ->label('Filter')
                ->icon('heroicon-m-funnel')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('startDate')
                        ->label('Tanggal Mulai')
                        ->default($this->startDate)
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('endDate')
                        ->label('Tanggal Selesai')
                        ->default($this->endDate)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->startDate = $data['startDate'];
                    $this->endDate = $data['endDate'];
                }),
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
        $daysInPeriod = Carbon::parse($this->startDate)->diffInDays(Carbon::parse($this->endDate)) ?: 1;

        $summary = $warehouses->map(function ($warehouse) {
            // Initial Stock (sum across all products in this warehouse)
            $initialQty = (float) StockMovement::where('warehouse_id', $warehouse->id)
                ->where('created_at', '<', $this->startDate . ' 00:00:00')
                ->sum('quantity');

            // Net Movement within period
            $netMovement = (float) StockMovement::where('warehouse_id', $warehouse->id)
                ->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59'])
                ->sum('quantity');

            $finalQty = $initialQty + $netMovement;
            $avgStock = ($initialQty + $finalQty) / 2;

            // Qty Terjual (Negative movements from Sales)
            // We'll consider movements where quantity < 0 and reference type is sales-related or simple decrease
            // For general turnover, we usually look at COGS or Qty Sold. 
            // Here we'll sum ABS of negative movements linked to sales documents.
            $qtyTerjual = (float) StockMovement::where('warehouse_id', $warehouse->id)
                ->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59'])
                ->where('quantity', '<', 0)
                ->whereIn('reference_type', [
                    'App\Models\SalesInvoiceItem',
                    'App\Models\SalesDeliveryItem',
                    'App\Models\PosOrderItem'
                ])
                ->sum('quantity');

            $qtyTerjual = abs($qtyTerjual);

            $turnoverRatio = $avgStock > 0 ? $qtyTerjual / $avgStock : 0;
            $daysInPeriod = Carbon::parse($this->startDate)->diffInDays(Carbon::parse($this->endDate)) ?: 1;
            $storageDuration = $turnoverRatio > 0 ? $daysInPeriod / $turnoverRatio : 0;

            return (object) [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'initial_qty' => $initialQty,
                'final_qty' => $finalQty,
                'avg_qty' => $avgStock,
                'qty_sold' => $qtyTerjual,
                'ratio' => $turnoverRatio,
                'duration' => $storageDuration,
            ];
        });

        // Detailed product breakdown for expanded rows
        $details = [];
        foreach ($this->expandedRows as $warehouseId) {
            $topProducts = StockMovement::where('warehouse_id', $warehouseId)
                ->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59'])
                ->where('quantity', '<', 0)
                ->whereIn('reference_type', [
                    'App\Models\SalesInvoiceItem',
                    'App\Models\SalesDeliveryItem',
                    'App\Models\PosOrderItem'
                ])
                ->select('product_id', DB::raw('SUM(ABS(quantity)) as total_sold'))
                ->groupBy('product_id')
                ->orderByDesc('total_sold')
                ->limit(5)
                ->with('product')
                ->get();

            $details[$warehouseId] = $topProducts->map(function ($m) use ($warehouseId) {
                $pId = $m->product_id;

                $pInitial = (float) StockMovement::where('warehouse_id', $warehouseId)
                    ->where('product_id', $pId)
                    ->where('created_at', '<', $this->startDate . ' 00:00:00')
                    ->sum('quantity');

                $pNet = (float) StockMovement::where('warehouse_id', $warehouseId)
                    ->where('product_id', $pId)
                    ->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59'])
                    ->sum('quantity');

                $pFinal = $pInitial + $pNet;
                $pAvg = ($pInitial + $pFinal) / 2;
                $pRatio = $pAvg > 0 ? $m->total_sold / $pAvg : 0;

                return (object) [
                    'name' => $m->product->name ?? 'Unknown',
                    'sku' => $m->product->sku ?? '-',
                    'sold' => $m->total_sold,
                    'avg_stock' => $pAvg,
                    'ratio' => $pRatio,
                ];
            });
        }

        return [
            'summary' => $summary,
            'details' => $details,
        ];
    }
}
