<?php

namespace App\Filament\Pages\Laporan;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Contact;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class PembelianProdukPerVendor extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected static string $paginationView = 'filament-actions::link-pagination';

    protected string $view = 'filament.pages.laporan.pembelian-produk-per-vendor';

    protected static ?string $title = 'Pembelian Produk per Vendor';

    protected static ?string $slug = 'pembelian-produk-per-vendor';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?string $search = null;
    public $perPage = 10;
    public array $expandedVendors = [];

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'perPage' => ['except' => 10],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
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

    public function toggleVendor($id): void
    {
        if (in_array($id, $this->expandedVendors)) {
            $this->expandedVendors = array_diff($this->expandedVendors, [$id]);
        } else {
            $this->expandedVendors[] = $id;
        }
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }



    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Pembelian Produk per Vendor',
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
        // 1. Build Base Query for filtering
        $baseQuery = Contact::query()
            ->join('purchase_invoices', 'contacts.id', '=', 'purchase_invoices.supplier_id')
            ->join('purchase_invoice_items', 'purchase_invoices.id', '=', 'purchase_invoice_items.purchase_invoice_id')
            ->whereBetween('purchase_invoices.date', [$this->startDate, $this->endDate])
            ->where('purchase_invoices.status', '!=', 'cancelled');

        if ($this->search) {
            $baseQuery->where('contacts.name', 'like', "%{$this->search}%");
        }

        // 2. Chart Data: Pembelian Produk per Vendor (Quantity)
        $chartData = (clone $baseQuery)
            ->select('contacts.name', DB::raw('SUM(purchase_invoice_items.quantity) as total_qty'))
            ->groupBy('contacts.id', 'contacts.name')
            ->orderBy('total_qty', 'desc')
            ->limit(10)
            ->get();

        $perPage = $this->perPage === 'all' ? max(1, (clone $baseQuery)->count()) : $this->perPage;
        $paginator = (clone $baseQuery)
            ->select(
                'contacts.id as vendor_id',
                'contacts.name as vendor_name',
                DB::raw('SUM(purchase_invoice_items.quantity) as total_qty')
            )
            ->groupBy('contacts.id', 'contacts.name')
            ->orderBy('total_qty', 'desc')
            ->paginate($perPage);

        // 4. Nested Data for expanded vendors
        $nestedData = [];
        if (!empty($this->expandedVendors)) {
            $results = PurchaseInvoiceItem::query()
                ->join('purchase_invoices', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
                ->join('products', 'purchase_invoice_items.product_id', '=', 'products.id')
                ->whereIn('purchase_invoices.supplier_id', $this->expandedVendors)
                ->whereBetween('purchase_invoices.date', [$this->startDate, $this->endDate])
                ->where('purchase_invoices.status', '!=', 'cancelled')
                ->select(
                    'purchase_invoices.supplier_id',
                    'products.name as product_name',
                    'products.sku as product_sku',
                    'purchase_invoice_items.quantity',
                    'purchase_invoice_items.total_price'
                )
                ->get();

            foreach ($results as $res) {
                $nestedData[$res->supplier_id][] = $res;
            }
        }

        return [
            'chartData' => $chartData,
            'vendors' => $paginator->items(),
            'paginator' => $paginator,
            'nestedData' => $nestedData,
        ];
    }
}

