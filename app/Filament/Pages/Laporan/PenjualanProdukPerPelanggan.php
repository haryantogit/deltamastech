<?php

namespace App\Filament\Pages\Laporan;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Livewire\WithPagination;

class PenjualanProdukPerPelanggan extends Page implements \Filament\Actions\Contracts\HasActions
{
    use \Filament\Actions\Concerns\InteractsWithActions;
    use WithPagination;

    protected static ?string $navigationLabel = 'Penjualan Produk per Pelanggan';
    protected static ?string $title = 'Penjualan Produk per Pelanggan';
    protected static ?string $slug = 'penjualan-produk-per-pelanggan';
    protected string $view = 'filament.pages.laporan.penjualan-produk-per-pelanggan';
    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $perPage = 15;
    public $expandedContacts = [];

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

    public function toggleContact($id): void
    {
        if (in_array($id, $this->expandedContacts)) {
            $this->expandedContacts = array_diff($this->expandedContacts, [$id]);
        } else {
            $this->expandedContacts[] = $id;
        }
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Penjualan Produk per Pelanggan',
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
        // Fetch contacts with aggregated quantity
        $query = DB::table('sales_invoices as si')
            ->join('contacts as c', 'si.contact_id', '=', 'c.id')
            ->join('sales_invoice_items as sii', 'si.id', '=', 'sii.sales_invoice_id')
            ->whereBetween('si.transaction_date', [$this->startDate, $this->endDate])
            ->where('si.status', '!=', 'cancelled')
            ->select(
                'c.id',
                'c.name as contact_name',
                'c.company',
                DB::raw('SUM(sii.qty) as total_qty')
            )
            ->groupBy('c.id', 'contact_name', 'c.company')
            ->orderBy('total_qty', 'desc');

        $paginator = $query->paginate($this->perPage);

        $results = $paginator->items();

        // If contact is expanded, fetch products for that contact
        foreach ($results as $contact) {
            if (in_array($contact->id, $this->expandedContacts)) {
                $contact->products = DB::table('sales_invoice_items as sii')
                    ->join('sales_invoices as si', 'sii.sales_invoice_id', '=', 'si.id')
                    ->join('products as p', 'sii.product_id', '=', 'p.id')
                    ->where('si.contact_id', $contact->id)
                    ->whereBetween('si.transaction_date', [$this->startDate, $this->endDate])
                    ->where('si.status', '!=', 'cancelled')
                    ->select(
                        'p.name as product_name',
                        'p.sku',
                        DB::raw('SUM(sii.qty) as qty'),
                        DB::raw('AVG(sii.price) as avg_price'),
                        DB::raw('SUM(sii.subtotal) as total_price')
                    )
                    ->groupBy('product_name', 'p.sku')
                    ->get();
            } else {
                $contact->products = [];
            }
        }

        return [
            'results' => $results,
            'paginator' => $paginator,
        ];
    }
}
