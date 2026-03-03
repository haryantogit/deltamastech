<?php

namespace App\Filament\Pages\Laporan;

use App\Models\ProductionOrder;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Livewire\WithPagination;

class LaporanProduksi extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected string $view = 'filament.pages.laporan.laporan-produksi';

    protected static ?string $title = 'Laporan Produksi';

    protected static ?string $slug = 'laporan-produksi';

    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $search = '';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Laporan Produksi',
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
        $ordersQuery = ProductionOrder::query()
            ->with(['product', 'warehouse', 'items', 'costs'])
            ->whereBetween('transaction_date', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);

        if ($this->search) {
            $ordersQuery->whereHas('product', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        $ordersQuery->orderBy('transaction_date', 'desc');

        $perPageCount = $this->perPage === 'all' ? max(1, (clone $ordersQuery)->count()) : $this->perPage;
        $paginatedOrders = $ordersQuery->paginate($perPageCount);

        $rows = $paginatedOrders->map(function ($order) {
            $materialCost = $order->items->sum('total_price');
            $otherCosts = $order->costs->sum('amount');
            $productionValue = $materialCost + $otherCosts;
            $quantity = (float) $order->quantity;
            $hpp = $quantity > 0 ? $productionValue / $quantity : 0;

            return (object) [
                'id' => $order->id,
                'finished_product' => $order->product->name ?? '-',
                'quantity' => $quantity,
                'hpp' => $hpp,
                'production_value' => $productionValue,
                'other_costs' => $otherCosts,
                'date' => $order->transaction_date,
                'number' => $order->number,
                'warehouse' => $order->warehouse->name ?? 'Unassigned',
                'tag' => $order->tag ?? '-',
                'url' => \App\Filament\Resources\ProductionOrderResource::getUrl('view', ['record' => $order->id]),
            ];
        });

        // For totals, we need to sum over the WHOLE filtered set, not just the page
        $allFilteredQuery = clone $ordersQuery;
        // However, the sum logic is a bit complex due to the mapping.
        // For production value and other costs, we might need a more optimized way if the dataset is huge,
        // but for now we follow the existing logic on the filtered collection.
        $allOrders = $allFilteredQuery->get();
        $totalQty = 0;
        $totalProductionValue = 0;
        $totalOtherCosts = 0;

        foreach ($allOrders as $order) {
            $mc = $order->items->sum('total_price');
            $oc = $order->costs->sum('amount');
            $totalQty += (float) $order->quantity;
            $totalProductionValue += ($mc + $oc);
            $totalOtherCosts += $oc;
        }

        return [
            'rows' => $rows,
            'paginator' => $paginatedOrders,
            'totalQty' => $totalQty,
            'totalProductionValue' => $totalProductionValue,
            'totalOtherCosts' => $totalOtherCosts,
        ];
    }
}


