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

class LaporanPenyesuaianStok extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected string $view = 'filament.pages.laporan.laporan-penyesuaian-stok';

    protected static ?string $title = 'Laporan Penyesuaian Stok';

    protected static ?string $slug = 'laporan-penyesuaian-stok';

    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $search = '';
    public $warehouseId;
    public $expandedRows = [];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');

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
        if (!$this->warehouseId) {
            return [
                'summary' => collect(),
                'details' => [],
                'warehouses' => Warehouse::orderBy('name')->get(),
            ];
        }

        $movementsQuery = StockMovement::query()
            ->with(['product'])
            ->where('reference_type', \App\Models\StockAdjustmentItem::class)
            ->where('warehouse_id', $this->warehouseId)
            ->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);

        if ($this->search) {
            $movementsQuery->whereHas('product', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        $allMovements = $movementsQuery->get();

        $summary = $allMovements->groupBy('product_id')->map(function ($productMovements) {
            $product = $productMovements->first()->product;
            $totalQty = $productMovements->sum('quantity');
            $price = (float) ($product->cost_of_goods ?? 0);

            return (object) [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'movement_qty' => $totalQty,
                'avg_price' => $price,
                'total_value' => $totalQty * $price,
            ];
        });

        $details = [];
        foreach ($this->expandedRows as $productId) {
            $itemMovements = $allMovements->where('product_id', $productId);

            $details[$productId] = $itemMovements->map(function ($m) {
                $adjItem = $m->reference;
                $adj = $adjItem?->adjustment;

                return (object) [
                    'date' => $m->created_at,
                    'number' => $adj->number ?? '-',
                    'reason' => $adj->reason ?? '-',
                    'qty' => $m->quantity,
                    'price' => (float) ($m->product->cost_of_goods ?? 0),
                    'total' => $m->quantity * (float) ($m->product->cost_of_goods ?? 0),
                    'url' => $adj ? \App\Filament\Resources\StockAdjustments\StockAdjustmentResource::getUrl('view', ['record' => $adj->id]) : '#',
                ];
            });
        }

        return [
            'summary' => $summary,
            'details' => $details,
            'warehouses' => Warehouse::orderBy('name')->get(),
            'totalValue' => $summary->sum('total_value'),
        ];
    }
}
