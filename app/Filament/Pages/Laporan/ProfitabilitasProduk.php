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

class ProfitabilitasProduk extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected static string $paginationView = 'filament-actions::link-pagination';

    protected string $view = 'filament.pages.laporan.profitabilitas-produk';
    protected static ?string $title = 'Profitabilitas Produk';
    protected static ?string $slug = 'profitabilitas-produk';
    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $search = '';
    public $activeTab = 'lacak_stok';
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
            'Profitabilitas Produk',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStartDate()
    {
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->resetPage();
    }

    public function getViewData(): array
    {
        // Query best-selling products: only those with sales in the date range
        $query = Product::query()
            ->select('products.*')
            ->join(
                DB::raw('(SELECT product_id, SUM(qty) as total_qty, SUM(subtotal) as total_sales
                    FROM sales_invoice_items
                    WHERE EXISTS (
                        SELECT 1 FROM sales_invoices
                        WHERE sales_invoices.id = sales_invoice_items.sales_invoice_id
                        AND sales_invoices.transaction_date BETWEEN ? AND ?
                    )
                    GROUP BY product_id) as sales_agg'),
                'products.id', '=', 'sales_agg.product_id'
            )
            ->addSelect('sales_agg.total_qty', 'sales_agg.total_sales')
            ->setBindings([$this->startDate, $this->endDate], 'join')
            ->when($this->activeTab === 'lacak_stok', fn($q) => $q->where('products.track_inventory', true))
            ->when($this->activeTab === 'tanpa_lacak_stok', fn($q) => $q->where('products.track_inventory', false))
            ->when($this->activeTab === 'paket', fn($q) => $q->where('products.type', 'bundle'))
            ->when($this->search, fn($q) => $q->where(function ($sub) {
                $sub->where('products.name', 'like', "%{$this->search}%")
                    ->orWhere('products.sku', 'like', "%{$this->search}%");
            }))
            ->orderByDesc('sales_agg.total_qty');

        $products = $query->paginate($this->perPage);

        // Map data to results
        $results = $products->map(function ($product) {
            $qty = (float) ($product->total_qty ?? 0);
            $totalSales = (float) ($product->total_sales ?? 0);

            $totalHpp = $qty * (float) ($product->cost_of_goods ?? 0);
            $totalProfit = $totalSales - $totalHpp;

            $profitMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;
            $avgSellPrice = $qty > 0 ? ($totalSales / $qty) : 0;
            $avgHpp = (float) ($product->cost_of_goods ?? 0);
            $biayaPercent = $totalSales > 0 ? ($totalHpp / $totalSales) * 100 : 0;

            return [
                'name' => $product->name,
                'sku' => $product->sku,
                'qty' => $qty,
                'total_sales' => $totalSales,
                'total_hpp' => $totalHpp,
                'total_profit' => $totalProfit,
                'profit_margin' => $profitMargin,
                'biaya_percent' => $biayaPercent,
                'avg_sell_price' => $avgSellPrice,
                'avg_hpp' => $avgHpp,
            ];
        });

        $pageStats = [
            'qty' => $results->sum('qty'),
            'total_sales' => $results->sum('total_sales'),
            'total_hpp' => $results->sum('total_hpp'),
            'total_profit' => $results->sum('total_profit'),
        ];

        return [
            'results' => $results,
            'paginator' => $products,
            'totalCount' => $products->total(),
            'pageStats' => $pageStats,
        ];
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
}
