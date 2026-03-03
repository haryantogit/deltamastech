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

class PembelianPerVendor extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected static string $paginationView = 'filament-actions::link-pagination';

    protected string $view = 'filament.pages.laporan.pembelian-per-vendor';

    protected static ?string $title = 'Pembelian per Vendor';

    protected static ?string $slug = 'pembelian-per-vendor';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $search = '';
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
        if (!$this->startDate) {
            $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        }
        if (!$this->endDate) {
            $this->endDate = Carbon::now()->format('Y-m-d');
        }
    }

    public function toggleVendor($id): void
    {
        if (in_array($id, $this->expandedVendors)) {
            $this->expandedVendors = array_values(array_diff($this->expandedVendors, [$id]));
        } else {
            $this->expandedVendors[] = $id;
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
            'Pembelian per Vendor',
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
        $query = Contact::query()
            ->join('purchase_invoices', 'contacts.id', '=', 'purchase_invoices.supplier_id')
            ->whereBetween('purchase_invoices.date', [$this->startDate, $this->endDate])
            ->where('purchase_invoices.status', '!=', 'cancelled')
            ->select(
                'contacts.id as vendor_id',
                'contacts.name as vendor_name',
                'contacts.company as company_name',
                DB::raw('COUNT(purchase_invoices.id) as transaction_count'),
                DB::raw('SUM(purchase_invoices.total_amount) as total_amount')
            );

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('contacts.name', 'like', "%{$this->search}%")
                    ->orWhere('contacts.company', 'like', "%{$this->search}%");
            });
        }

        $query->groupBy('contacts.id', 'contacts.name', 'contacts.company')
            ->orderBy('total_amount', 'desc');

        $perPage = $this->perPage === 'all' ? max(1, $candidateCount = $query->count()) : $this->perPage;
        $paginator = $query->paginate($perPage);

        // Nested data for expanded vendors
        $nestedData = [];
        if (!empty($this->expandedVendors)) {
            $results = PurchaseInvoiceItem::query()
                ->join('purchase_invoices', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
                ->join('products', 'purchase_invoice_items.product_id', '=', 'products.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->whereIn('purchase_invoices.supplier_id', $this->expandedVendors)
                ->whereBetween('purchase_invoices.date', [$this->startDate, $this->endDate])
                ->where('purchase_invoices.status', '!=', 'cancelled')
                ->select(
                    'purchase_invoices.supplier_id',
                    'purchase_invoices.number as invoice_number',
                    'purchase_invoices.date as invoice_date',
                    'products.name as product_name',
                    'products.sku as product_sku',
                    'categories.name as category_name',
                    'purchase_invoice_items.description',
                    'purchase_invoice_items.quantity',
                    'purchase_invoice_items.unit_price',
                    'purchase_invoice_items.total_price'
                )
                ->orderBy('purchase_invoices.date', 'desc')
                ->get();

            foreach ($results as $item) {
                $nestedData[$item->supplier_id][] = [
                    'invoice_number' => $item->invoice_number,
                    'invoice_date' => Carbon::parse($item->invoice_date)->format('d/m/Y'),
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'category_name' => $item->category_name,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                ];
            }
        }

        // Global total
        $globalTotals = DB::table('purchase_invoices')
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->where('status', '!=', 'cancelled')
            ->select(
                DB::raw('COUNT(id) as grand_total_count'),
                DB::raw('SUM(total_amount) as grand_total_amount')
            )
            ->first();

        return [
            'vendors' => collect($paginator->items())->map(fn($v) => [
                'vendor_id' => $v->vendor_id,
                'vendor_name' => $v->vendor_name,
                'company_name' => $v->company_name,
                'transaction_count' => (int) $v->transaction_count,
                'total_amount' => (float) $v->total_amount,
            ]),
            'paginator' => $paginator,
            'nestedData' => $nestedData,
            'grandTotalCount' => (int) ($globalTotals->grand_total_count ?? 0),
            'grandTotalAmount' => (float) ($globalTotals->grand_total_amount ?? 0),
        ];
    }
}

