<?php

namespace App\Filament\Pages\Laporan;

use App\Models\PurchaseInvoiceItem;
use App\Models\Product;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class PembelianPerProduk extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected static string $paginationView = 'filament-actions::link-pagination';

    protected string $view = 'filament.pages.laporan.pembelian-per-produk';

    protected static ?string $title = 'Pembelian per Produk';

    protected static ?string $slug = 'pembelian-per-produk';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $search = '';
    public $perPage = 10;
    public array $expandedProducts = [];

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'perPage' => ['except' => 10],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        if (!$this->startDate) {
            $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        }
        if (!$this->endDate) {
            $this->endDate = Carbon::now()->format('Y-m-d');
        }
    }

    public function toggleProduct($id): void
    {
        if (in_array($id, $this->expandedProducts)) {
            $this->expandedProducts = array_values(array_diff($this->expandedProducts, [$id]));
        } else {
            $this->expandedProducts[] = $id;
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
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

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Pembelian per Produk',
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
        $query = PurchaseInvoiceItem::query()
            ->join('purchase_invoices', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
            ->join('products', 'purchase_invoice_items.product_id', '=', 'products.id')
            ->whereBetween('purchase_invoices.date', [$this->startDate, $this->endDate])
            ->where('purchase_invoices.status', '!=', 'cancelled')
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'products.sku as product_sku',
                'products.buy_price as current_price',
                DB::raw('SUM(purchase_invoice_items.quantity) as total_qty'),
                DB::raw('SUM(purchase_invoice_items.total_price) as total_amount')
            );

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('products.name', 'like', "%{$this->search}%")
                    ->orWhere('products.sku', 'like', "%{$this->search}%");
            });
        }

        $query->groupBy('products.id', 'products.name', 'products.sku', 'products.buy_price')
            ->orderBy('total_amount', 'desc');

        $perPage = $this->perPage === 'all' ? max(1, $query->count()) : $this->perPage;
        $paginator = $query->paginate($perPage);

        // Nested data for expanded products
        $nestedData = [];
        if (!empty($this->expandedProducts)) {
            $results = PurchaseInvoiceItem::query()
                ->join('purchase_invoices', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
                ->join('contacts', 'purchase_invoices.supplier_id', '=', 'contacts.id')
                ->whereIn('purchase_invoice_items.product_id', $this->expandedProducts)
                ->whereBetween('purchase_invoices.date', [$this->startDate, $this->endDate])
                ->select(
                    'purchase_invoice_items.product_id',
                    'purchase_invoices.number as invoice_number',
                    'contacts.name as supplier_name',
                    'purchase_invoices.date as invoice_date',
                    'purchase_invoice_items.quantity',
                    'purchase_invoice_items.total_price'
                )
                ->orderBy('purchase_invoices.date', 'desc')
                ->get();

            foreach ($results as $item) {
                $nestedData[$item->product_id][] = [
                    'invoice_number' => $item->invoice_number,
                    'supplier_name' => $item->supplier_name,
                    'invoice_date' => Carbon::parse($item->invoice_date)->format('d/m/Y'),
                    'quantity' => (float) $item->quantity,
                    'total_price' => (float) $item->total_price,
                ];
            }
        }

        // Global total
        $globalTotals = DB::table('purchase_invoice_items')
            ->join('purchase_invoices', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
            ->whereBetween('purchase_invoices.date', [$this->startDate, $this->endDate])
            ->where('purchase_invoices.status', '!=', 'cancelled')
            ->select(
                DB::raw('SUM(purchase_invoice_items.quantity) as grand_total_qty'),
                DB::raw('SUM(purchase_invoice_items.total_price) as grand_total_amount')
            )
            ->first();

        return [
            'products' => collect($paginator->items())->map(fn($p) => [
                'product_id' => $p->product_id,
                'product_name' => $p->product_name,
                'product_sku' => $p->product_sku,
                'current_price' => (float) $p->current_price,
                'total_qty' => (float) $p->total_qty,
                'total_amount' => (float) $p->total_amount,
                'average_price' => $p->total_qty > 0 ? (float) ($p->total_amount / $p->total_qty) : 0,
            ]),
            'paginator' => $paginator,
            'nestedData' => $nestedData,
            'grandTotalQty' => (float) ($globalTotals->grand_total_qty ?? 0),
            'grandTotalAmount' => (float) ($globalTotals->grand_total_amount ?? 0),
        ];
    }
}

