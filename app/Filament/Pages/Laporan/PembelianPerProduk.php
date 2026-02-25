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
    public ?string $search = null;
    public int $perPage = 15;
    public array $expandedProducts = [];

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function toggleProduct($id): void
    {
        if (in_array($id, $this->expandedProducts)) {
            $this->expandedProducts = array_diff($this->expandedProducts, [$id]);
        } else {
            $this->expandedProducts[] = $id;
        }
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

    public function updatedPerPage(): void
    {
        $this->resetPage();
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

        $paginator = $query->paginate($this->perPage);

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
                $nestedData[$item->product_id][] = $item;
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
            'products' => $paginator->items(),
            'paginator' => $paginator,
            'nestedData' => $nestedData,
            'grandTotalQty' => $globalTotals->grand_total_qty ?? 0,
            'grandTotalAmount' => $globalTotals->grand_total_amount ?? 0,
        ];
    }
}
