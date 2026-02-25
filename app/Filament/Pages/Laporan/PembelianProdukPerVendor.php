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
    public int $perPage = 15;
    public array $expandedVendors = [];

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function toggleVendor($id): void
    {
        if (in_array($id, $this->expandedVendors)) {
            $this->expandedVendors = array_diff($this->expandedVendors, [$id]);
        } else {
            $this->expandedVendors[] = $id;
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
        // 1. Chart Data: Pembelian Produk per Vendor (Quantity)
        $chartData = DB::table('purchase_invoice_items as pii')
            ->join('purchase_invoices as pi', 'pii.purchase_invoice_id', '=', 'pi.id')
            ->join('contacts as c', 'pi.supplier_id', '=', 'c.id')
            ->whereBetween('pi.date', [$this->startDate, $this->endDate])
            ->where('pi.status', '!=', 'cancelled')
            ->select('c.name', DB::raw('SUM(pii.quantity) as total_qty'))
            ->groupBy('c.id', 'c.name')
            ->orderBy('total_qty', 'desc')
            ->limit(10)
            ->get();

        // 2. Table Data (Paginated Vendors)
        $query = Contact::query()
            ->join('purchase_invoices', 'contacts.id', '=', 'purchase_invoices.supplier_id')
            ->join('purchase_invoice_items', 'purchase_invoices.id', '=', 'purchase_invoice_items.purchase_invoice_id')
            ->whereBetween('purchase_invoices.date', [$this->startDate, $this->endDate])
            ->where('purchase_invoices.status', '!=', 'cancelled')
            ->select(
                'contacts.id as vendor_id',
                'contacts.name as vendor_name',
                DB::raw('SUM(purchase_invoice_items.quantity) as total_qty')
            )
            ->groupBy('contacts.id', 'contacts.name');

        if ($this->search) {
            $query->where('contacts.name', 'like', "%{$this->search}%");
        }

        $paginator = $query->orderBy('total_qty', 'desc')->paginate($this->perPage);

        // 3. Nested Data for expanded vendors
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
