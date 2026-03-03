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
    public $perPage = 10;
    public $groupBy = 'pelanggan'; // 'pelanggan', 'pengiriman', 'produk'
    public array $expandedGroups = [];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
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
        $this->expandedGroups = [];
    }

    public function toggleGroup($id): void
    {
        if (in_array($id, $this->expandedGroups)) {
            $this->expandedGroups = array_values(array_diff($this->expandedGroups, [$id]));
        } else {
            $this->expandedGroups[] = $id;
        }
    }
    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $startDate = $this->startDate ?? now()->startOfYear()->toDateString();
        $endDate = $this->endDate ?? now()->toDateString();
        $startFmt = \Carbon\Carbon::parse($startDate)->format('d/m/Y');
        $endFmt = \Carbon\Carbon::parse($endDate)->format('d/m/Y');

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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter')
                ->label('Filter')
                ->icon('heroicon-m-funnel')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('startDate')
                        ->hiddenLabel()
                        ->default($this->startDate)
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('endDate')
                        ->hiddenLabel()
                        ->default($this->endDate)
                        ->required(),
                    \Filament\Forms\Components\Select::make('groupBy')
                        ->hiddenLabel()
                        ->options([
                            'pelanggan' => 'Pelanggan',
                            'pengiriman' => 'Pengiriman',
                            'produk' => 'Produk',
                        ])
                        ->default($this->groupBy)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->startDate = $data['startDate'];
                    $this->endDate = $data['endDate'];
                    $this->groupBy = $data['groupBy'];
                    $this->resetPage();
                }),
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

        $perPage = $this->perPage === 'all' ? max(1, $customerQuery->count()) : $this->perPage;
        $paginator = $customerQuery->paginate($perPage);
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

        $perPage = $this->perPage === 'all' ? max(1, $deliveryQuery->count()) : $this->perPage;
        $paginator = $deliveryQuery->paginate($perPage);
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

        $perPage = $this->perPage === 'all' ? max(1, $productQuery->count()) : $this->perPage;
        $paginator = $productQuery->paginate($perPage);
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


