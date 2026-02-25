<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class PergerakanStokInventori extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected string $view = 'filament.pages.laporan.pergerakan-stok-inventori';

    protected static ?string $title = 'Pergerakan Stok Inventori';

    protected static ?string $slug = 'pergerakan-stok-inventori';

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
            $this->expandedRows = [];
        } else {
            $this->expandedRows = [$id];
        }
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Pergerakan Stok Inventori',
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

        $summary = $products->map(function ($product) {
            // Initial Qty (before startDate)
            $initialQty = (float) StockMovement::where('product_id', $product->id)
                ->where('created_at', '<', $this->startDate . ' 00:00:00')
                ->sum('quantity');

            // Movement Qty (within range)
            $movementQty = (float) StockMovement::where('product_id', $product->id)
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
        });

        $details = [];
        foreach ($this->expandedRows as $productId) {
            $product = $products->find($productId);
            if (!$product)
                continue;

            // Get initial qty for running balance
            $runningQty = (float) StockMovement::where('product_id', $productId)
                ->where('created_at', '<', $this->startDate . ' 00:00:00')
                ->sum('quantity');

            $movements = StockMovement::where('product_id', $productId)
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
            'summary' => $summary,
            'details' => $details,
            'totalInitialQty' => $summary->sum('initial_qty'),
            'totalMovementQty' => $summary->sum('movement_qty'),
            'totalFinalQty' => $summary->sum('final_qty'),
            'totalInitialValue' => $summary->sum('initial_value'),
            'totalMovementValue' => $summary->sum('movement_value'),
            'totalFinalValue' => $summary->sum('final_value'),
        ];
    }
}
