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
    public $search = '';
    public $perPage = 10;
    public $expandedContacts = [];

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'perPage' => ['except' => 10],
        'search' => ['except' => ''],
    ];

    public function mount()
    {
        if (!$this->startDate) {
            $this->startDate = Carbon::now()->startOfYear()->toDateString();
        }
        if (!$this->endDate) {
            $this->endDate = Carbon::now()->toDateString();
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
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
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('c.name', 'like', "%{$this->search}%")
                        ->orWhere('c.company', 'like', "%{$this->search}%");
                });
            })
            ->select(
                'c.id',
                'c.name as contact_name',
                'c.company',
                DB::raw('SUM(sii.qty) as total_qty')
            )
            ->groupBy('c.id', 'contact_name', 'c.company')
            ->orderBy('total_qty', 'desc');

        $perPage = $this->perPage === 'all' ? max(1, $query->count()) : $this->perPage;
        $paginator = $query->paginate($perPage);

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


