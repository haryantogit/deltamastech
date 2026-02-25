<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Contact;
use App\Models\Product;
use App\Models\SalesDelivery;
use App\Models\SalesDeliveryItem;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use App\Filament\Pages\ReportPage;

class PengirimanPenjualan extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected string $view = 'filament.pages.laporan.pengiriman-penjualan';
    protected static ?string $title = 'Pengiriman Penjualan';
    protected static ?string $slug = 'pengiriman-penjualan';
    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $search = '';
    public $perPage = 15;
    public $groupBy = 'pelanggan'; // 'pelanggan', 'pengiriman', 'produk'

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            ReportPage::getUrl() => 'Laporan',
            'Pengiriman Penjualan',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedGroupBy()
    {
        $this->resetPage();
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

    /**
     * Base query for fetching delivery data alongside price data from SO or Products
     */
    protected function getBaseDeliveryQuery()
    {
        return DB::table('sales_delivery_items as sdi')
            ->join('sales_deliveries as sd', 'sdi.sales_delivery_id', '=', 'sd.id')
            ->join('products as p', 'sdi.product_id', '=', 'p.id')
            ->join('contacts as c', 'sd.customer_id', '=', 'c.id')
            ->leftJoin('sales_order_items as soi', function ($join) {
                $join->on('sd.sales_order_id', '=', 'soi.sales_order_id')
                    ->on('sdi.product_id', '=', 'soi.product_id');
            })
            ->whereBetween('sd.date', [$this->startDate, $this->endDate])
            ->where('sd.status', '!=', 'cancelled')
            ->select(
                'sd.id as delivery_id',
                'sd.number as delivery_number',
                'sd.date as delivery_date',
                'c.id as customer_id',
                'c.name as customer_name',
                'p.id as product_id',
                'p.name as product_name',
                'sdi.quantity as quantity',
                'p.unit_name as unit_name',
                DB::raw('COALESCE(soi.unit_price, p.sell_price) as actual_price'),
                DB::raw('sdi.quantity * COALESCE(soi.unit_price, p.sell_price) as total_price')
            );
    }

    public function getViewData(): array
    {
        $viewData = [
            'results' => collect(),
            'paginator' => null,
            'totalCount' => 0,
            'globalTotal' => 0,
            'groupBy' => $this->groupBy,
        ];

        if ($this->groupBy === 'pelanggan') {
            $viewData = $this->getPelangganData($viewData);
        } elseif ($this->groupBy === 'pengiriman') {
            $viewData = $this->getPengirimanData($viewData);
        } elseif ($this->groupBy === 'produk') {
            $viewData = $this->getProdukData($viewData);
        }

        return $viewData;
    }

    protected function getPelangganData(array $viewData): array
    {
        // Get parent customers (paginated)
        $customerQuery = DB::table('sales_deliveries as sd')
            ->join('contacts as c', 'sd.customer_id', '=', 'c.id')
            ->whereBetween('sd.date', [$this->startDate, $this->endDate])
            ->where('sd.status', '!=', 'cancelled')
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('c.name', 'like', "%{$this->search}%");
                });
            })
            ->select('c.id', 'c.name')
            ->distinct()
            ->orderBy('c.name');

        $paginator = $customerQuery->paginate($this->perPage);
        $customerIds = collect($paginator->items())->pluck('id')->toArray();

        // Get children details for these customers
        $details = collect();
        if (!empty($customerIds)) {
            $details = $this->getBaseDeliveryQuery()
                ->whereIn('sd.customer_id', $customerIds)
                ->orderBy('sd.date', 'desc')
                ->get()
                ->groupBy('customer_id');
        }

        $results = collect($paginator->items())->map(function ($customer) use ($details) {
            $customerItems = $details->get($customer->id, collect());
            $customerTotal = $customerItems->sum('total_price');

            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'total' => $customerTotal,
                'items' => $customerItems->map(fn($item) => (array) $item)->toArray(),
            ];
        });

        // Global total
        $globalTotal = $this->getBaseDeliveryQuery()
            ->when($this->search, function ($q) {
                $q->where('c.name', 'like', "%{$this->search}%");
            })
            ->sum(DB::raw('sdi.quantity * COALESCE(soi.unit_price, p.sell_price)'));

        $viewData['results'] = $results;
        $viewData['paginator'] = $paginator;
        $viewData['totalCount'] = $paginator->total();
        $viewData['globalTotal'] = (float) $globalTotal;

        return $viewData;
    }

    protected function getPengirimanData(array $viewData): array
    {
        // Get parent deliveries (paginated)
        $deliveryQuery = DB::table('sales_deliveries as sd')
            ->join('contacts as c', 'sd.customer_id', '=', 'c.id')
            ->whereBetween('sd.date', [$this->startDate, $this->endDate])
            ->where('sd.status', '!=', 'cancelled')
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('sd.number', 'like', "%{$this->search}%")
                        ->orWhere('c.name', 'like', "%{$this->search}%");
                });
            })
            ->select('sd.id', 'sd.number', 'sd.date', 'c.name as customer_name')
            ->orderBy('sd.date', 'desc');

        $paginator = $deliveryQuery->paginate($this->perPage);
        $deliveryIds = collect($paginator->items())->pluck('id')->toArray();

        // For "Pengiriman" flat view, we just need the aggregated totals per delivery
        $totals = collect();
        if (!empty($deliveryIds)) {
            $totals = $this->getBaseDeliveryQuery()
                ->whereIn('sd.id', $deliveryIds)
                ->groupBy('sd.id')
                ->select('sd.id', DB::raw('SUM(sdi.quantity * COALESCE(soi.unit_price, p.sell_price)) as delivery_total'))
                ->get()
                ->keyBy('id');
        }

        $results = collect($paginator->items())->map(function ($delivery) use ($totals) {
            $totalRaw = $totals->get($delivery->id);
            $totalAmount = $totalRaw ? $totalRaw->delivery_total : 0;

            return [
                'id' => $delivery->id,
                'date' => Carbon::parse($delivery->date)->format('d/m/Y'),
                'number' => $delivery->number,
                'customer_name' => $delivery->customer_name,
                'total' => (float) $totalAmount,
            ];
        });

        // Global total
        $globalTotal = $this->getBaseDeliveryQuery()
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('sd.number', 'like', "%{$this->search}%")
                        ->orWhere('c.name', 'like', "%{$this->search}%");
                });
            })
            ->sum(DB::raw('sdi.quantity * COALESCE(soi.unit_price, p.sell_price)'));

        $viewData['results'] = $results;
        $viewData['paginator'] = $paginator;
        $viewData['totalCount'] = $paginator->total();
        $viewData['globalTotal'] = (float) $globalTotal;

        return $viewData;
    }

    protected function getProdukData(array $viewData): array
    {
        // Get parent products (paginated)
        $productQuery = DB::table('sales_delivery_items as sdi')
            ->join('sales_deliveries as sd', 'sdi.sales_delivery_id', '=', 'sd.id')
            ->join('products as p', 'sdi.product_id', '=', 'p.id')
            ->whereBetween('sd.date', [$this->startDate, $this->endDate])
            ->where('sd.status', '!=', 'cancelled')
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('p.name', 'like', "%{$this->search}%");
                });
            })
            ->select('p.id', 'p.name', 'p.sku')
            ->distinct()
            ->orderBy('p.name');

        $paginator = $productQuery->paginate($this->perPage);
        $productIds = collect($paginator->items())->pluck('id')->toArray();

        // Get children details for these products
        $details = collect();
        if (!empty($productIds)) {
            $details = $this->getBaseDeliveryQuery()
                ->whereIn('p.id', $productIds)
                ->orderBy('sd.date', 'desc')
                ->get()
                ->groupBy('product_id');
        }

        $results = collect($paginator->items())->map(function ($product) use ($details) {
            $productItems = $details->get($product->id, collect());
            $productTotal = $productItems->sum('total_price');

            // Optional: Include SKU alongside name matching the screenshot "PAKU ... (SKU/0051)"
            $fullName = $product->name . ($product->sku ? ' (' . $product->sku . ')' : '');

            return [
                'id' => $product->id,
                'name' => $fullName,
                'total' => $productTotal,
                'items' => $productItems->map(fn($item) => (array) $item)->toArray(),
            ];
        });

        // Global total
        $globalTotal = $this->getBaseDeliveryQuery()
            ->when($this->search, function ($q) {
                $q->where('p.name', 'like', "%{$this->search}%");
            })
            ->sum(DB::raw('sdi.quantity * COALESCE(soi.unit_price, p.sell_price)'));

        $viewData['results'] = $results;
        $viewData['paginator'] = $paginator;
        $viewData['totalCount'] = $paginator->total();
        $viewData['globalTotal'] = (float) $globalTotal;

        return $viewData;
    }
}
