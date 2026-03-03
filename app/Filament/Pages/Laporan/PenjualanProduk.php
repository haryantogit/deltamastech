<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Product;
use App\Models\SalesInvoiceItem;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use App\Filament\Pages\ReportPage;

class PenjualanProduk extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected string $view = 'filament.pages.laporan.penjualan-produk';
    protected static ?string $title = 'Penjualan per Produk';
    protected static ?string $slug = 'penjualan-produk';
    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $search = '';
    public $perPage = 10;

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            ReportPage::getUrl() => 'Laporan',
            'Penjualan per Produk',
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

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $startDate = $this->startDate ?? now()->startOfYear()->toDateString();
        $endDate = $this->endDate ?? now()->toDateString();
        $startFmt = Carbon::parse($startDate)->format('d/m/Y');
        $endFmt = Carbon::parse($endDate)->format('d/m/Y');

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
                        ->label('Tanggal Akhir')
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
                ->url(ReportPage::getUrl()),
        ];
    }

    public function getViewData(): array
    {
        // Query products that have sales in the period
        $query = Product::query()
            ->select('products.*')
            ->join(
                DB::raw('(SELECT product_id, 
                                 SUM(qty) as jumlah_terjual, 
                                 SUM(subtotal) as total_pendapatan
                          FROM sales_invoice_items sii
                          JOIN sales_invoices si ON sii.sales_invoice_id = si.id
                          WHERE si.transaction_date BETWEEN ? AND ?
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

        $perPage = $this->perPage === 'all' ? max(1, $query->count()) : $this->perPage;
        $paginator = $query->paginate($perPage);

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
        // We only want totals for the products that match the search term if there is one
        $globalQuery = DB::table('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'sii.sales_invoice_id', '=', 'si.id')
            ->join('products as p', 'sii.product_id', '=', 'p.id')
            ->whereBetween('si.transaction_date', [$this->startDate, $this->endDate]);

        if ($this->search) {
            $globalQuery->where(function ($sub) {
                $sub->where('p.name', 'like', "%{$this->search}%")
                    ->orWhere('p.sku', 'like', "%{$this->search}%");
            });
        }

        $globalStatsRaw = $globalQuery
            ->selectRaw('SUM(sii.qty) as global_qty, SUM(sii.subtotal) as global_pendapatan')
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


