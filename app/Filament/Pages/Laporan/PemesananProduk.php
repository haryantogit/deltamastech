<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Product;
use App\Models\SalesOrderItem;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use App\Filament\Pages\ReportPage;

class PemesananProduk extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected string $view = 'filament.pages.laporan.pemesanan-produk';
    protected static ?string $title = 'Pemesanan per Produk';
    protected static ?string $slug = 'pemesanan-produk';
    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $search = '';
    public $perPage = 15;

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            ReportPage::getUrl() => 'Laporan',
            'Pemesanan per Produk',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function updatedSearch()
    {
        $this->resetPage();
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
                        ->label('Tanggal Akhir')
                        ->default($this->endDate)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->startDate = $data['startDate'];
                    $this->endDate = $data['endDate'];
                    $this->resetPage();
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
                ->url(ReportPage::getUrl()),
        ];
    }

    public function getViewData(): array
    {
        // Query products that have sales orders in the period
        $query = Product::query()
            ->select('products.*')
            ->join(
                DB::raw('(SELECT product_id, 
                                 SUM(quantity) as jumlah_terjual, 
                                 SUM(total_price) as total_pendapatan
                          FROM sales_order_items soi
                          JOIN sales_orders so ON soi.sales_order_id = so.id
                          WHERE so.date BETWEEN ? AND ?
                          GROUP BY product_id) as sales_agg'),
                'products.id',
                '=',
                'sales_agg.product_id'
            )
            ->addSelect('sales_agg.jumlah_terjual', 'sales_agg.total_pendapatan')
            ->setBindings([$this->startDate, $this->endDate], 'join')
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('products.name', 'like', "%{$this->search}%")
                        ->orWhere('products.sku', 'like', "%{$this->search}%");
                });
            })
            ->orderByDesc('sales_agg.total_pendapatan')
            ->orderByDesc('sales_agg.jumlah_terjual');

        $paginator = $query->paginate($this->perPage);

        // Map data to results
        $results = collect($paginator->items())->map(function ($product) {
            $jumlahTerjual = (float) ($product->jumlah_terjual ?? 0);
            $totalPendapatan = (float) ($product->total_pendapatan ?? 0);
            $rataRata = $jumlahTerjual > 0 ? $totalPendapatan / $jumlahTerjual : 0;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku ?? '-',
                'harga_saat_ini' => (float) ($product->sell_price ?? 0),
                'jumlah_terjual' => $jumlahTerjual,
                'total' => $totalPendapatan,
                'rata_rata' => $rataRata,
            ];
        });

        // Global Totals via separate query for accuracy
        $globalQuery = DB::table('sales_order_items as soi')
            ->join('sales_orders as so', 'soi.sales_order_id', '=', 'so.id')
            ->join('products as p', 'soi.product_id', '=', 'p.id')
            ->whereBetween('so.date', [$this->startDate, $this->endDate]);

        if ($this->search) {
            $globalQuery->where(function ($sub) {
                $sub->where('p.name', 'like', "%{$this->search}%")
                    ->orWhere('p.sku', 'like', "%{$this->search}%");
            });
        }

        $globalStatsRaw = $globalQuery
            ->selectRaw('SUM(soi.quantity) as global_qty, SUM(soi.total_price) as global_pendapatan')
            ->first();

        $globalQty = (float) ($globalStatsRaw->global_qty ?? 0);
        $globalPendapatan = (float) ($globalStatsRaw->global_pendapatan ?? 0);
        $globalRataRata = $globalQty > 0 ? $globalPendapatan / $globalQty : 0;

        return [
            'results' => $results,
            'paginator' => $paginator,
            'totalCount' => $paginator->total(),
            'globalStats' => [
                'qty' => $globalQty,
                'total' => $globalPendapatan,
                'rata_rata' => $globalRataRata,
            ]
        ];
    }
}
