<?php

namespace App\Filament\Pages\Laporan;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Livewire\WithPagination;

class PenjualanPerKategori extends Page implements \Filament\Actions\Contracts\HasActions
{
    use \Filament\Actions\Concerns\InteractsWithActions;
    use WithPagination;

    protected static ?string $navigationLabel = 'Penjualan per Kategori Produk';
    protected static ?string $title = 'Penjualan per Kategori Produk';
    protected static ?string $slug = 'penjualan-per-kategori-produk';
    protected string $view = 'filament.pages.laporan.penjualan-per-kategori';
    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $perPage = 10;

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfYear()->toDateString();
        $this->endDate = Carbon::now()->toDateString();
    }

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $startFmt = Carbon::parse($this->startDate)->format('d/m/Y');
        $endFmt = Carbon::parse($this->endDate)->format('d/m/Y');

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

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Penjualan per Kategori Produk',
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
                    DatePicker::make('startDate')
                        ->label('Tanggal Mulai')
                        ->default($this->startDate)
                        ->required(),
                    DatePicker::make('endDate')
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
                ->color('gray')
                ->icon('heroicon-o-printer')
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
        $query = DB::table('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'sii.sales_invoice_id', '=', 'si.id')
            ->join('products as p', 'sii.product_id', '=', 'p.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->whereBetween('si.transaction_date', [$this->startDate, $this->endDate])
            ->where('si.status', '!=', 'cancelled')
            ->select(
                DB::raw('COALESCE(c.name, "Tanpa Kategori") as category_name'),
                DB::raw('SUM(sii.qty) as total_qty'),
                DB::raw('SUM(sii.subtotal) as total_amount')
            )
            ->groupBy('category_name')
            ->orderBy('total_amount', 'desc');

        $perPage = $this->perPage === 'all' ? max(1, $query->count()) : $this->perPage;
        $paginator = $query->paginate($perPage);

        // Grand totals from separate query for accuracy across all pages
        $globalStats = DB::table('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'sii.sales_invoice_id', '=', 'si.id')
            ->whereBetween('si.transaction_date', [$this->startDate, $this->endDate])
            ->where('si.status', '!=', 'cancelled')
            ->selectRaw('SUM(sii.qty) as total_qty, SUM(sii.subtotal) as total_amount')
            ->first();

        $grandTotalQty = (float) ($globalStats->total_qty ?? 0);
        $grandTotalAmount = (float) ($globalStats->total_amount ?? 0);
        $grandAverage = $grandTotalQty > 0 ? $grandTotalAmount / $grandTotalQty : 0;

        return [
            'results' => $paginator->items(),
            'paginator' => $paginator,
            'grandTotalQty' => $grandTotalQty,
            'grandTotalAmount' => $grandTotalAmount,
            'grandAverage' => $grandAverage,
        ];
    }
}

