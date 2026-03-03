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

use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Livewire\WithPagination;

class PerputaranPersediaan extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected string $view = 'filament.pages.laporan.perputaran-persediaan';

    protected static ?string $title = 'Perputaran Persediaan Barang';

    protected static ?string $slug = 'perputaran-persediaan';

    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $search = '';
    public $perPage = 10;
    public $warehouseId;
    public $expandedRows = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');

        $firstWarehouse = Warehouse::orderBy('name')->first();
        $this->warehouseId = $firstWarehouse?->id;
    }

    public function setWarehouse($id)
    {
        $this->warehouseId = $id;
        $this->expandedRows = [];
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

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $startDate = $this->startDate ?? now()->startOfyear()->toDateString();
        $endDate = $this->endDate ?? now()->toDateString();
        $startFmt = \Carbon\Carbon::parse($startDate)->format('d/m/Y');
        $endFmt = \Carbon\Carbon::parse($endDate)->format('d/m/Y');

        $dateDisplay = $startFmt === $endFmt
            ? $startFmt
            : $startFmt . ' &mdash; ' . $endFmt;

        return new \Illuminate\Support\HtmlString('
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background-color: #f8fafc; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; font-size: 0.875rem; font-weight: 600; color: #475569;" class="dark:bg-white/5 dark:border-white/10 dark:text-gray-300">
                <svg style="width: 1.25rem; height: 1.25rem; opacity: 0.7;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z" />
                </svg>
                <span>' . $dateDisplay . '</span>
            </div>
        ');
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
                'warehouses' => Warehouse::orderBy('name')->get(),
                'paginator' => null,
            ];
        }

        $daysInPeriod = Carbon::parse($this->startDate)->diffInDays(Carbon::parse($this->endDate)) ?: 1;

        $productsQuery = Product::query()
            ->with(['category'])
            ->where('track_inventory', true)
            ->where('is_fixed_asset', false)
            ->where('is_active', true);

        if ($this->search) {
            $productsQuery->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('sku', 'like', '%' . $this->search . '%')
                    ->orWhereHas('category', function ($cq) {
                        $cq->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        $allProducts = $productsQuery->get();

        $allSummary = $allProducts->map(function ($product) use ($daysInPeriod) {
            // Initial Stock in specific warehouse
            $initialQty = (float) StockMovement::where('product_id', $product->id)
                ->where('warehouse_id', $this->warehouseId)
                ->where('created_at', '<', $this->startDate . ' 00:00:00')
                ->sum('quantity');

            // Net Movement within period
            $netMovement = (float) StockMovement::where('product_id', $product->id)
                ->where('warehouse_id', $this->warehouseId)
                ->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59'])
                ->sum('quantity');

            $finalQty = $initialQty + $netMovement;
            $avgStock = ($initialQty + $finalQty) / 2;

            // Qty Terjual (Negative movements from Sales)
            $qtyTerjual = (float) StockMovement::where('product_id', $product->id)
                ->where('warehouse_id', $this->warehouseId)
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
            $storageDuration = $turnoverRatio > 0 ? $daysInPeriod / $turnoverRatio : 0;

            return (object) [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category->name ?? '-',
                'sku' => $product->sku,
                'initial_qty' => $initialQty,
                'final_qty' => $finalQty,
                'avg_qty' => $avgStock,
                'qty_sold' => $qtyTerjual,
                'ratio' => $turnoverRatio,
                'duration' => $storageDuration,
            ];
        })->filter(function ($row) {
            // Only show products that have movements or existing stock to avoid clutter
            return $row->initial_qty != 0 || $row->final_qty != 0 || $row->qty_sold != 0;
        })->sortBy('name');

        // Pagination
        $perPageCount = $this->perPage === 'all' ? max(1, $allSummary->count()) : (int) $this->perPage;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $paginatedSummary = new \Illuminate\Pagination\LengthAwarePaginator(
            $allSummary->forPage($currentPage, $perPageCount),
            $allSummary->count(),
            $perPageCount,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return [
            'summary' => $paginatedSummary,
            'paginator' => $paginatedSummary,
            'warehouses' => Warehouse::orderBy('name')->get(),
        ];
    }
}


