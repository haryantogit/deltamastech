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
    public ?string $search = null;
    public string $viewType = 'pengiriman'; // pengiriman, vendor, produk
    public int $perPage = 15;

    public function mount(): void
    {
        $this->startDate = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = \Carbon\Carbon::now()->format('Y-m-d');
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

    public function updatedViewType(): void
    {
        $this->resetPage();
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Pengiriman Pembelian',
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
            Action::make('bagikan')
                ->label('Bagikan')
                ->icon('heroicon-o-share')
                ->color('gray'),
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

        $paginator = $query->orderBy('date', 'desc')->paginate($this->perPage);

        return [
            'items' => $paginator->items(),
            'paginator' => $paginator,
            'grandTotal' => $this->getGrandTotalValue(),
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

        $paginator = $query->orderBy('total_value', 'desc')->paginate($this->perPage);

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
                $nestedData[$res->group_id][] = $res;
            }
        }

        return [
            'items' => $paginator->items(),
            'paginator' => $paginator,
            'nestedData' => $nestedData,
            'grandTotal' => $this->getGrandTotalValue(),
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

        $paginator = $query->orderBy('total_value', 'desc')->paginate($this->perPage);

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
                $nestedData[$res->group_id][] = $res;
            }
        }

        return [
            'items' => $paginator->items(),
            'paginator' => $paginator,
            'nestedData' => $nestedData,
            'grandTotal' => $this->getGrandTotalValue(),
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
