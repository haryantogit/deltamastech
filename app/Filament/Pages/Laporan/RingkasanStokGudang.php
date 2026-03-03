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
use Livewire\WithPagination;
use Carbon\Carbon;

class RingkasanStokGudang extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected string $view = 'filament.pages.laporan.ringkasan-stok-gudang';

    protected static ?string $title = 'Ringkasan Stok Gudang';

    protected static ?string $slug = 'ringkasan-stok-gudang';

    protected static bool $shouldRegisterNavigation = false;

    public $date;
    public $search = '';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function mount(): void
    {
        $this->date = Carbon::now()->format('Y-m-d');
    }

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $dateFmt = Carbon::parse($this->date)->format('d/m/Y');

        return new \Illuminate\Support\HtmlString('
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background-color: #f8fafc; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; font-size: 0.875rem; font-weight: 600; color: #475569;" class="dark:bg-white/5 dark:border-white/10 dark:text-gray-300">
                <svg style="width: 1.25rem; height: 1.25rem; opacity: 0.7;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>' . $dateFmt . '</span>
            </div>
        ');
    }

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
            Action::make('filter')
                ->label('Filter')
                ->icon('heroicon-m-funnel')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('date')
                        ->label('Per Tanggal')
                        ->default($this->date)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->date = $data['date'];
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

        $perPageCount = $this->perPage === 'all' ? max(1, (clone $productsQuery)->count()) : $this->perPage;
        $paginator = $productsQuery->orderBy('name')->paginate($perPageCount);
        $products = collect($paginator->items());

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
            'paginator' => $paginator,
        ];
    }
}
