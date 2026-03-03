<?php

namespace App\Filament\Pages\Laporan;

use App\Models\PurchaseDelivery;
use App\Models\PurchaseDeliveryItem;
use App\Models\Contact;
use App\Models\Product;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class PengirimanPembelian extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected static string $paginationView = 'filament-actions::link-pagination';

    protected string $view = 'filament.pages.laporan.pengiriman-pembelian';

    protected static ?string $title = 'Pengiriman Pembelian';

    protected static ?string $slug = 'pengiriman-pembelian';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $search = '';
    public string $viewType = 'pengiriman'; // pengiriman, vendor, produk
    public $perPage = 10;

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'viewType' => ['except' => 'pengiriman'],
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedViewType(): void
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
            'Pengiriman Pembelian',
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
                    \Filament\Forms\Components\Select::make('viewType')
                        ->label('Tipe Tampilan')
                        ->options([
                            'pengiriman' => 'Pengiriman',
                            'vendor' => 'Vendor',
                            'produk' => 'Produk',
                        ])
                        ->default($this->viewType)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->startDate = $data['startDate'];
                    $this->endDate = $data['endDate'];
                    $this->viewType = $data['viewType'];
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
        if ($this->viewType === 'pengiriman') {
            return $this->getPengirimanData();
        } elseif ($this->viewType === 'vendor') {
            return $this->getVendorData();
        } else {
            return $this->getProdukData();
        }
    }

    protected function getPengirimanData(): array
    {
        $query = PurchaseDelivery::query()
            ->join('contacts', 'purchase_deliveries.supplier_id', '=', 'contacts.id')
            ->join('purchase_delivery_items', 'purchase_deliveries.id', '=', 'purchase_delivery_items.purchase_delivery_id')
            ->leftJoin('purchase_order_items', function ($join) {
                $join->on('purchase_deliveries.purchase_order_id', '=', 'purchase_order_items.purchase_order_id')
                    ->on('purchase_delivery_items.product_id', '=', 'purchase_order_items.product_id');
            })
            ->whereBetween('purchase_deliveries.date', [$this->startDate, $this->endDate])
            ->where('purchase_deliveries.status', '!=', 'cancelled')
            ->select(
                'purchase_deliveries.id',
                'purchase_deliveries.date',
                'purchase_deliveries.number',
                'contacts.name as vendor_name',
                DB::raw('SUM(purchase_delivery_items.quantity * IFNULL(purchase_order_items.unit_price, 0)) as total_value')
            )
            ->groupBy('purchase_deliveries.id', 'purchase_deliveries.date', 'purchase_deliveries.number', 'contacts.name');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('purchase_deliveries.number', 'like', "%{$this->search}%")
                    ->orWhere('contacts.name', 'like', "%{$this->search}%");
            });
        }

        $perPage = $this->perPage === 'all' ? max(1, $query->count()) : $this->perPage;
        $paginator = $query->orderBy('date', 'desc')->paginate($perPage);

        return [
            'items' => collect($paginator->items())->map(fn($item) => [
                'id' => $item->id,
                'date' => Carbon::parse($item->date)->format('d/m/Y'),
                'number' => $item->number,
                'vendor_name' => $item->vendor_name,
                'total_value' => (float) $item->total_value,
            ]),
            'paginator' => $paginator,
            'grandTotal' => (float) $this->getGrandTotalValue(),
        ];
    }

    protected function getVendorData(): array
    {
        $query = Contact::query()
            ->join('purchase_deliveries', 'contacts.id', '=', 'purchase_deliveries.supplier_id')
            ->join('purchase_delivery_items', 'purchase_deliveries.id', '=', 'purchase_delivery_items.purchase_delivery_id')
            ->leftJoin('purchase_order_items', function ($join) {
                $join->on('purchase_deliveries.purchase_order_id', '=', 'purchase_order_items.purchase_order_id')
                    ->on('purchase_delivery_items.product_id', '=', 'purchase_order_items.product_id');
            })
            ->whereBetween('purchase_deliveries.date', [$this->startDate, $this->endDate])
            ->where('purchase_deliveries.status', '!=', 'cancelled')
            ->select(
                'contacts.id as group_id',
                'contacts.name as group_name',
                'contacts.company as company_name',
                DB::raw('COUNT(DISTINCT purchase_deliveries.id) as transaction_count'),
                DB::raw('SUM(purchase_delivery_items.quantity * IFNULL(purchase_order_items.unit_price, 0)) as total_value')
            )
            ->groupBy('contacts.id', 'contacts.name', 'contacts.company');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('contacts.name', 'like', "%{$this->search}%")
                    ->orWhere('contacts.company', 'like', "%{$this->search}%");
            });
        }

        $perPage = $this->perPage === 'all' ? max(1, $query->count()) : $this->perPage;
        $paginator = $query->orderBy('total_value', 'desc')->paginate($perPage);

        $vendorIds = collect($paginator->items())->pluck('group_id')->toArray();
        $nestedData = [];
        if (!empty($vendorIds)) {
            $results = PurchaseDeliveryItem::query()
                ->join('purchase_deliveries', 'purchase_delivery_items.purchase_delivery_id', '=', 'purchase_deliveries.id')
                ->join('products', 'purchase_delivery_items.product_id', '=', 'products.id')
                ->leftJoin('units', 'purchase_delivery_items.unit_id', '=', 'units.id')
                ->leftJoin('purchase_order_items', function ($join) {
                    $join->on('purchase_deliveries.purchase_order_id', '=', 'purchase_order_items.purchase_order_id')
                        ->on('purchase_delivery_items.product_id', '=', 'purchase_order_items.product_id');
                })
                ->whereIn('purchase_deliveries.supplier_id', $vendorIds)
                ->whereBetween('purchase_deliveries.date', [$this->startDate, $this->endDate])
                ->select(
                    'purchase_deliveries.supplier_id as group_id',
                    'purchase_deliveries.number as doc_number',
                    'purchase_deliveries.date as doc_date',
                    'products.name as product_name',
                    'products.sku as product_sku',
                    'units.name as unit_name',
                    'purchase_delivery_items.quantity',
                    DB::raw('IFNULL(purchase_order_items.unit_price, 0) as price'),
                    DB::raw('(purchase_delivery_items.quantity * IFNULL(purchase_order_items.unit_price, 0)) as row_total')
                )
                ->get();

            foreach ($results as $res) {
                $nestedData[$res->group_id][] = [
                    'doc_number' => $res->doc_number,
                    'doc_date' => Carbon::parse($res->doc_date)->format('d/m/Y'),
                    'product_name' => $res->product_name,
                    'product_sku' => $res->product_sku,
                    'unit_name' => $res->unit_name,
                    'quantity' => (float) $res->quantity,
                    'price' => (float) $res->price,
                    'row_total' => (float) $res->row_total,
                ];
            }
        }

        return [
            'items' => collect($paginator->items())->map(fn($item) => [
                'group_id' => $item->group_id,
                'group_name' => $item->group_name,
                'company_name' => $item->company_name,
                'transaction_count' => (int) $item->transaction_count,
                'total_value' => (float) $item->total_value,
            ]),
            'paginator' => $paginator,
            'nestedData' => $nestedData,
            'grandTotal' => (float) $this->getGrandTotalValue(),
        ];
    }

    protected function getProdukData(): array
    {
        $query = Product::query()
            ->join('purchase_delivery_items', 'products.id', '=', 'purchase_delivery_items.product_id')
            ->join('purchase_deliveries', 'purchase_delivery_items.purchase_delivery_id', '=', 'purchase_deliveries.id')
            ->leftJoin('purchase_order_items', function ($join) {
                $join->on('purchase_deliveries.purchase_order_id', '=', 'purchase_order_items.purchase_order_id')
                    ->on('purchase_delivery_items.product_id', '=', 'purchase_order_items.product_id');
            })
            ->whereBetween('purchase_deliveries.date', [$this->startDate, $this->endDate])
            ->where('purchase_deliveries.status', '!=', 'cancelled')
            ->select(
                'products.id as group_id',
                'products.name as group_name',
                'products.sku as product_sku',
                DB::raw('SUM(purchase_delivery_items.quantity) as total_qty'),
                DB::raw('SUM(purchase_delivery_items.quantity * IFNULL(purchase_order_items.unit_price, 0)) as total_value')
            )
            ->groupBy('products.id', 'products.name', 'products.sku');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('products.name', 'like', "%{$this->search}%")
                    ->orWhere('products.sku', 'like', "%{$this->search}%");
            });
        }

        $perPage = $this->perPage === 'all' ? max(1, $query->count()) : $this->perPage;
        $paginator = $query->orderBy('total_value', 'desc')->paginate($perPage);

        $productIds = collect($paginator->items())->pluck('group_id')->toArray();
        $nestedData = [];
        if (!empty($productIds)) {
            $results = PurchaseDeliveryItem::query()
                ->join('purchase_deliveries', 'purchase_delivery_items.purchase_delivery_id', '=', 'purchase_deliveries.id')
                ->join('contacts', 'purchase_deliveries.supplier_id', '=', 'contacts.id')
                ->leftJoin('units', 'purchase_delivery_items.unit_id', '=', 'units.id')
                ->leftJoin('purchase_order_items', function ($join) {
                    $join->on('purchase_deliveries.purchase_order_id', '=', 'purchase_order_items.purchase_order_id')
                        ->on('purchase_delivery_items.product_id', '=', 'purchase_order_items.product_id');
                })
                ->whereIn('purchase_delivery_items.product_id', $productIds)
                ->whereBetween('purchase_deliveries.date', [$this->startDate, $this->endDate])
                ->select(
                    'purchase_delivery_items.product_id as group_id',
                    'purchase_deliveries.number as doc_number',
                    'purchase_deliveries.date as doc_date',
                    'contacts.name as vendor_name',
                    'units.name as unit_name',
                    'purchase_delivery_items.quantity',
                    DB::raw('IFNULL(purchase_order_items.unit_price, 0) as price'),
                    DB::raw('(purchase_delivery_items.quantity * IFNULL(purchase_order_items.unit_price, 0)) as row_total')
                )
                ->get();

            foreach ($results as $res) {
                $nestedData[$res->group_id][] = [
                    'doc_number' => $res->doc_number,
                    'doc_date' => Carbon::parse($res->doc_date)->format('d/m/Y'),
                    'vendor_name' => $res->vendor_name,
                    'unit_name' => $res->unit_name,
                    'quantity' => (float) $res->quantity,
                    'price' => (float) $res->price,
                    'row_total' => (float) $res->row_total,
                ];
            }
        }

        return [
            'items' => collect($paginator->items())->map(fn($item) => [
                'group_id' => $item->group_id,
                'group_name' => $item->group_name,
                'product_sku' => $item->product_sku,
                'total_qty' => (float) $item->total_qty,
                'total_value' => (float) $item->total_value,
            ]),
            'paginator' => $paginator,
            'nestedData' => $nestedData,
            'grandTotal' => (float) $this->getGrandTotalValue(),
        ];
    }

    protected function getGrandTotalValue(): float
    {
        return DB::table('purchase_delivery_items')
            ->join('purchase_deliveries', 'purchase_delivery_items.purchase_delivery_id', '=', 'purchase_deliveries.id')
            ->leftJoin('purchase_order_items', function ($join) {
                $join->on('purchase_deliveries.purchase_order_id', '=', 'purchase_order_items.purchase_order_id')
                    ->on('purchase_delivery_items.product_id', '=', 'purchase_order_items.product_id');
            })
            ->whereBetween('purchase_deliveries.date', [$this->startDate, $this->endDate])
            ->where('purchase_deliveries.status', '!=', 'cancelled')
            ->sum(DB::raw('purchase_delivery_items.quantity * IFNULL(purchase_order_items.unit_price, 0)'));
    }
}

