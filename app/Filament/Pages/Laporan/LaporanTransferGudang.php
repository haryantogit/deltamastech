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

class LaporanTransferGudang extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected string $view = 'filament.pages.laporan.laporan-transfer-gudang';

    protected static ?string $title = 'Laporan Transfer Gudang';

    protected static ?string $slug = 'laporan-transfer-gudang';

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
            'Laporan Transfer Gudang',
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

        // We use StockMovement to capture both IN and OUT transfers for the selected warehouse
        $movementsQuery = StockMovement::query()
            ->with(['product'])
            ->where('reference_type', \App\Models\WarehouseTransferItem::class)
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
            // Abs value might be preferred for volume, but net movement is better for audit.
            // Based on screenshot, "Pergerakan Kuantitas" is usually volume or delta.
            // We'll show the absolute sum of movement quantities for volume, 
            // but we can distinguish IN/OUT in details.
            $totalVolume = $productMovements->sum(fn($m) => abs($m->quantity));

            return (object) [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'movement_qty' => $totalVolume,
            ];
        });

        $details = [];
        foreach ($this->expandedRows as $productId) {
            $itemMovements = $allMovements->where('product_id', $productId);

            $details[$productId] = $itemMovements->map(function ($m) {
                $transferItem = $m->reference;
                $transfer = $transferItem?->transfer;

                return (object) [
                    'date' => $m->created_at,
                    'number' => $transfer->number ?? '-',
                    'from' => $transfer->fromWarehouse->name ?? '-',
                    'to' => $transfer->toWarehouse->name ?? '-',
                    'qty' => abs($m->quantity),
                    'type' => $m->quantity > 0 ? 'Masuk' : 'Keluar',
                    'url' => $transfer ? \App\Filament\Resources\WarehouseTransfers\WarehouseTransferResource::getUrl('view', ['record' => $transfer->id]) : '#',
                ];
            });
        }

        return [
            'summary' => $summary,
            'details' => $details,
            'warehouses' => Warehouse::orderBy('name')->get(),
            'totalVolume' => $summary->sum('movement_qty'),
        ];
    }
}
