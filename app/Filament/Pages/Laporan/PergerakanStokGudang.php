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

class PergerakanStokGudang extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-up-down';

    protected string $view = 'filament.pages.laporan.pergerakan-stok-gudang';

    protected static ?string $title = 'Pergerakan Stok Gudang';

    protected static ?string $slug = 'pergerakan-stok-gudang';

    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $search = '';
    public $warehouseId;
    public $expandedRows = [];
    public $perPage = 10;

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');

        $gudangUtama = Warehouse::where('name', 'like', '%Gudang Utama%')->first();
        if ($gudangUtama) {
            $this->warehouseId = $gudangUtama->id;
        } else {
            $firstWarehouse = Warehouse::orderBy('name')->first();
            $this->warehouseId = $firstWarehouse?->id;
        }
    }

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $startFmt = Carbon::parse($this->startDate)->format('d/m/Y');
        $endFmt = Carbon::parse($this->endDate)->format('d/m/Y');

        return new \Illuminate\Support\HtmlString('
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background-color: #f8fafc; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; font-size: 0.875rem; font-weight: 600; color: #475569;" class="dark:bg-white/5 dark:border-white/10 dark:text-gray-300">
                <svg style="width: 1.25rem; height: 1.25rem; opacity: 0.7;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>' . $startFmt . ' — ' . $endFmt . '</span>
            </div>
        ');
    }

    public function setWarehouse($id)
    {
        $this->warehouseId = $id;
        $this->expandedRows = []; // Reset expanded rows when changing warehouse
        $this->resetPage();
    }

    public function toggleRow($id): void
    {
        if (in_array($id, $this->expandedRows)) {
            $this->expandedRows = [];
        } else {
            $this->expandedRows = [$id];
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Pergerakan Stok Gudang',
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
                    $this->resetPage();
                }),
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
        if (!$this->warehouseId) {
            return [
                'summary' => collect(),
                'details' => [],
                'warehouses' => Warehouse::orderBy('name')->get(),
            ];
        }

        $productsQuery = Product::query()
            ->with(['category', 'unit'])
            ->where('track_inventory', true)
            ->where('is_fixed_asset', false)
            ->where('is_active', true);

        if ($this->search) {
            $productsQuery->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        $products = $productsQuery->get();

        $allSummary = $products->map(function ($product) {
            // Initial Qty (before startDate) in specific warehouse
            $initialQty = (float) StockMovement::where('product_id', $product->id)
                ->where('warehouse_id', $this->warehouseId)
                ->where('created_at', '<', $this->startDate . ' 00:00:00')
                ->sum('quantity');

            // Movement Qty (within range) in specific warehouse
            $movementQty = (float) StockMovement::where('product_id', $product->id)
                ->where('warehouse_id', $this->warehouseId)
                ->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59'])
                ->sum('quantity');

            // Final Qty
            $finalQty = $initialQty + $movementQty;

            // Value calculations (fallback to current COG)
            $initialValue = $initialQty * (float) $product->cost_of_goods;
            $movementValue = $movementQty * (float) $product->cost_of_goods;
            $finalValue = $finalQty * (float) $product->cost_of_goods;

            return (object) [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category->name ?? '-',
                'sku' => $product->sku,
                'initial_qty' => $initialQty,
                'movement_qty' => $movementQty,
                'final_qty' => $finalQty,
                'initial_value' => $initialValue,
                'movement_value' => $movementValue,
                'final_value' => $finalValue,
            ];
        })->filter(function ($row) {
            // Only show products that have movements or stock in this period
            return $row->initial_qty != 0 || $row->movement_qty != 0;
        });

        // Pagination for summary
        $perPage = $this->perPage === 'all' ? max(1, $allSummary->count()) : $this->perPage;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $paginatedSummary = new \Illuminate\Pagination\LengthAwarePaginator(
            $allSummary->forPage($currentPage, $perPage),
            $allSummary->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        $details = [];
        foreach ($this->expandedRows as $productId) {
            $product = $products->find($productId);
            if (!$product)
                continue;

            // Get initial qty for running balance
            $runningQty = (float) StockMovement::where('product_id', $productId)
                ->where('warehouse_id', $this->warehouseId)
                ->where('created_at', '<', $this->startDate . ' 00:00:00')
                ->sum('quantity');

            $movements = StockMovement::where('product_id', $productId)
                ->where('warehouse_id', $this->warehouseId)
                ->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59'])
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($m) use (&$runningQty, $product) {
                    $runningQty += $m->quantity;

                    // Resolve reference document number and link
                    $docNumber = '-';
                    $docLink = '#';
                    $company = '-';
                    $price = $product->cost_of_goods; // Default
    
                    if ($m->reference) {
                        if ($m->reference instanceof \App\Models\PurchaseInvoiceItem) {
                            $invoice = $m->reference->invoice;
                            $docNumber = 'Pengiriman Pembelian ' . ($invoice->invoice_number ?? $invoice->number ?? '-');
                            $docLink = \App\Filament\Resources\PurchaseInvoiceResource::getUrl('view', ['record' => $invoice->id]);
                            $company = $invoice->contact->name ?? '-';
                            $price = (float) $m->reference->unit_price;
                        } elseif ($m->reference instanceof \App\Models\SalesInvoiceItem) {
                            $invoice = $m->reference->invoice;
                            $docNumber = 'Penjualan ' . ($invoice->invoice_number ?? $invoice->number ?? '-');
                            $docLink = \App\Filament\Resources\SalesInvoiceResource::getUrl('view', ['record' => $invoice->id]);
                            $company = $invoice->customer->name ?? $invoice->contact->name ?? '-';
                        } elseif ($m->reference instanceof \App\Models\StockAdjustmentItem) {
                            $adj = $m->reference->adjustment;
                            $docNumber = 'Stock Adjustment ' . ($adj->number ?? '-');
                            $docLink = \App\Filament\Resources\StockAdjustments\StockAdjustmentResource::getUrl('view', ['record' => $adj->id]);
                        }
                    }

                    return (object) [
                        'date' => $m->created_at,
                        'doc_number' => $docNumber,
                        'doc_link' => $docLink,
                        'company' => $company,
                        'qty_movement' => $m->quantity,
                        'running_qty' => $runningQty,
                        'price' => $price,
                        'total_value' => $m->quantity * $price,
                    ];
                });

            $details[$productId] = $movements;
        }

        return [
            'summary' => $paginatedSummary->items(),
            'paginator' => $paginatedSummary,
            'details' => $details,
            'warehouses' => Warehouse::orderBy('name')->get(),
            'totalInitialQty' => $allSummary->sum('initial_qty'),
            'totalMovementQty' => $allSummary->sum('movement_qty'),
            'totalFinalQty' => $allSummary->sum('final_qty'),
            'totalInitialValue' => $allSummary->sum('initial_value'),
            'totalMovementValue' => $allSummary->sum('movement_value'),
            'totalFinalValue' => $allSummary->sum('final_value'),
        ];
    }
}


