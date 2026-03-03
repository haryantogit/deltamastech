<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Product;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Illuminate\Support\Facades\DB;
use App\Filament\Pages\ReportPage;
use Livewire\WithPagination;

class ProfitabilitasProduk extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected string $view = 'filament.pages.laporan.profitabilitas-produk';
    protected static ?string $title = 'Profitabilitas Produk';
    protected static ?string $slug = 'profitabilitas-produk';
    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $activeTab = 'lacak_stok';
    public $search = '';
    public $perPage = 15;

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
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

    public function updatedPerPage()
    {
        $this->resetPage();
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

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $startFmt = Carbon::parse($this->startDate)->format('d/m/Y');
        $endFmt = Carbon::parse($this->endDate)->format('d/m/Y');

        return new \Illuminate\Support\HtmlString('
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background-color: #f8fafc; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; font-size: 0.875rem; font-weight: 600; color: #475569;" class="dark:bg-white/5 dark:border-white/10 dark:text-gray-300">
                <svg style="width: 1.25rem; height: 1.25rem; opacity: 0.7;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>' . $startFmt . ' &mdash; ' . $endFmt . '</span>
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
        $startDate = $this->startDate;
        $endDate = $this->endDate;

        $query = Product::query()
            ->select('products.*')
            ->join(
                DB::raw("(SELECT product_id, SUM(qty) as total_qty, SUM(subtotal) as total_sales
                    FROM sales_invoice_items
                    WHERE EXISTS (
                        SELECT 1 FROM sales_invoices
                        WHERE sales_invoices.id = sales_invoice_items.sales_invoice_id
                        AND sales_invoices.transaction_date BETWEEN '{$startDate}' AND '{$endDate}'
                    )
                    GROUP BY product_id) as sales_agg"),
                'products.id',
                '=',
                'sales_agg.product_id'
            )
            ->addSelect(
                'sales_agg.total_qty',
                'sales_agg.total_sales',
                DB::raw('(sales_agg.total_qty * COALESCE(products.cost_of_goods, 0)) as total_hpp'),
                DB::raw('(sales_agg.total_sales - (sales_agg.total_qty * COALESCE(products.cost_of_goods, 0))) as total_profit'),
                DB::raw('CASE WHEN sales_agg.total_sales > 0 THEN ((sales_agg.total_sales - (sales_agg.total_qty * COALESCE(products.cost_of_goods, 0))) / sales_agg.total_sales * 100) ELSE 0 END as profit_margin'),
                DB::raw('CASE WHEN sales_agg.total_sales > 0 THEN ((sales_agg.total_qty * COALESCE(products.cost_of_goods, 0)) / sales_agg.total_sales * 100) ELSE 0 END as biaya_percent'),
                DB::raw('CASE WHEN sales_agg.total_qty > 0 THEN (sales_agg.total_sales / sales_agg.total_qty) ELSE 0 END as avg_sell_price'),
                DB::raw('COALESCE(products.cost_of_goods, 0) as avg_hpp')
            )
            ->when($this->activeTab === 'lacak_stok', fn($q) => $q->where('products.track_inventory', true))
            ->when($this->activeTab === 'tanpa_lacak_stok', fn($q) => $q->where('products.track_inventory', false))
            ->when($this->activeTab === 'paket', fn($q) => $q->where('products.type', 'bundle'));

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('products.name', 'like', '%' . $this->search . '%')
                    ->orWhere('products.sku', 'like', '%' . $this->search . '%');
            });
        }

        $allResults = $query->orderBy('total_qty', 'desc')->get();

        // Totals for summary
        $grandTotalQty = $allResults->sum('total_qty');
        $grandTotalSales = $allResults->sum('total_sales');
        $grandTotalHpp = $allResults->sum('total_hpp');
        $grandTotalProfit = $allResults->sum('total_profit');

        // Pagination
        $perPage = $this->perPage === 'all' ? max(1, $allResults->count()) : $this->perPage;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $allResults->forPage($currentPage, $perPage),
            $allResults->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return [
            'paginator' => $paginator,
            'summary' => $paginator->items(),
            'grandTotals' => [
                'qty' => $grandTotalQty,
                'sales' => $grandTotalSales,
                'hpp' => $grandTotalHpp,
                'profit' => $grandTotalProfit,
            ],
        ];
    }
}
