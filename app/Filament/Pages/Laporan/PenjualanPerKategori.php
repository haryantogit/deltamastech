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
    public $perPage = 15;

    protected $queryString = [
        'startDate',
        'endDate',
        'perPage',
    ];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate = Carbon::now()->toDateString();
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

        $results = $query->get();

        // Calculate totals
        $grandTotalQty = $results->sum('total_qty');
        $grandTotalAmount = $results->sum('total_amount');
        $grandAverage = $grandTotalQty > 0 ? $grandTotalAmount / $grandTotalQty : 0;

        return [
            'results' => $results,
            'grandTotalQty' => $grandTotalQty,
            'grandTotalAmount' => $grandTotalAmount,
            'grandAverage' => $grandAverage,
        ];
    }
}
