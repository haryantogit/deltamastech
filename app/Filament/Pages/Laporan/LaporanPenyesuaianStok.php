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

class LaporanPenyesuaianStok extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected string $view = 'filament.pages.laporan.laporan-penyesuaian-stok';

    protected static ?string $title = 'Laporan Penyesuaian Stok';

    protected static ?string $slug = 'laporan-penyesuaian-stok';

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
            'Laporan Penyesuaian Stok',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $startDate = $this->startDate ?? now()->startOfYear()->toDateString();
        $endDate = $this->endDate ?? now()->toDateString();
        $startFmt = \Carbon\Carbon::parse($startDate)->format('d/m/Y');
        $endFmt = \Carbon\Carbon::parse($endDate)->format('d/m/Y');

        $dateDisplay = $startFmt === $endFmt
            ? $startFmt
            : $startFmt . ' &mdash; ' . $endFmt;

        return new \Illuminate\Support\HtmlString('
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background-color: #f8fafc; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; font-size: 0.875rem; font-weight: 600; color: #475569;" class="dark:bg-white/5 dark:border-white/10 dark:text-gray-300">
                <svg style="width: 1.25rem; height: 1.25rem; opacity: 0.7;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
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
                'details' => [],
                'warehouses' => Warehouse::orderBy('name')->get(),
                'totalInitialQty' => 0,
                'totalMovementQty' => 0,
                'totalFinalQty' => 0,
                'totalInitialValue' => 0,
                'totalMovementValue' => 0,
                'totalFinalValue' => 0,
                'paginator' => null,
            ];
        }

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

        $products = $productsQuery->get();

        $allSummary = $products->map(function ($product) {
            // Initial Qty (before startDate) in specific warehouse
            $initialQty = (float) StockMovement::where('product_id', $product->id)
                ->where('warehouse_id', $this->warehouseId)
                ->where('created_at', '<', $this->startDate . ' 00:00:00')
                ->sum('quantity');

            // Movement Qty (within range) in specific warehouse - specifically for StockAdjustment movements
            $movementQty = (float) StockMovement::where('product_id', $product->id)
                ->where('warehouse_id', $this->warehouseId)
                ->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59'])
                ->where('reference_type', \App\Models\StockAdjustmentItem::class)
                ->sum('quantity');

            // Final Qty (Balance at end of period)
            $finalQty = $initialQty + $movementQty;

            // Value calculations (fallback to current COG)
            $price = (float) ($product->cost_of_goods ?? 0);
            $initialValue = $initialQty * $price;
            $movementValue = $movementQty * $price;
            $finalValue = $finalQty * $price;

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
                'avg_price' => $price,
            ];
        })->filter(function ($row) {
            // Only show products that have adjustments in this period
            return $row->movement_qty != 0;
        })->sortBy('name');

        // Pagination for summary
        $perPage = $this->perPage === 'all' ? max(1, $allSummary->count()) : (int) $this->perPage;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $paginatedSummary = new \Illuminate\Pagination\LengthAwarePaginator(
            $allSummary->forPage($currentPage, $perPage),
            $allSummary->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        $details = [];
        foreach ($this->expandedRows as $productId) {
            $productSummary = $allSummary->firstWhere('id', $productId);
            if (!$productSummary)
                continue;

            $movements = StockMovement::with(['reference.adjustment'])
                ->where('product_id', $productId)
                ->where('warehouse_id', $this->warehouseId)
                ->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59'])
                ->where('reference_type', \App\Models\StockAdjustmentItem::class)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($m) {
                    $adjItem = $m->reference;
                    $adj = $adjItem?->adjustment;
                    $price = (float) ($m->product->cost_of_goods ?? 0);

                    return (object) [
                        'date' => $m->created_at,
                        'number' => $adj->number ?? '-',
                        'reason' => $adj->reason ?? '-',
                        'qty' => $m->quantity,
                        'price' => $price,
                        'total' => $m->quantity * $price,
                        'url' => $adj ? \App\Filament\Resources\StockAdjustments\StockAdjustmentResource::getUrl('view', ['record' => $adj->id]) : '#',
                    ];
                });

            $details[$productId] = $movements;
        }

        return [
            'summary' => $paginatedSummary,
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


