<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Contact;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use App\Filament\Pages\ReportPage;

class PendapatanPelanggan extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected string $view = 'filament.pages.laporan.pendapatan-pelanggan';
    protected static ?string $title = 'Pendapatan per Pelanggan';
    protected static ?string $slug = 'pendapatan-pelanggan';
    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $search = '';
    public $perPage = 15;
    public $expandedContacts = [];

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
            'Pendapatan per Pelanggan',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function toggleContact($id)
    {
        if (in_array($id, $this->expandedContacts)) {
            $this->expandedContacts = array_values(array_diff($this->expandedContacts, [$id]));
        } else {
            $this->expandedContacts[] = $id;
        }
    }

    public function updatedSearch()
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

    public function getViewData(): array
    {
        // 1. Query contacts that have sales in the period
        $query = Contact::query()
            ->select('contacts.*')
            ->join(
                DB::raw('(SELECT c.id as contact_id, 
                                 COUNT(DISTINCT si.id) as total_transaksi, 
                                 SUM(sii.qty) as total_qty, 
                                 SUM(sii.subtotal) as total_pendapatan
                          FROM contacts c
                          JOIN sales_invoices si ON c.id = si.contact_id
                          JOIN sales_invoice_items sii ON si.id = sii.sales_invoice_id
                          WHERE si.transaction_date BETWEEN ? AND ?
                          GROUP BY c.id) as sales_agg'),
                'contacts.id',
                '=',
                'sales_agg.contact_id'
            )
            ->addSelect('sales_agg.total_transaksi', 'sales_agg.total_qty', 'sales_agg.total_pendapatan')
            ->setBindings([$this->startDate, $this->endDate], 'join')
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('contacts.name', 'like', "%{$this->search}%")
                        ->orWhere('contacts.company', 'like', "%{$this->search}%")
                        ->orWhere('contacts.phone', 'like', "%{$this->search}%");
                });
            })
            ->orderByDesc('sales_agg.total_pendapatan');

        $paginator = $query->paginate($this->perPage);

        // Fetch detailed items for expanded contacts
        $expandedContactIds = array_intersect(
            $paginator->pluck('id')->toArray(),
            $this->expandedContacts
        );

        $expandedItems = collect();
        if (!empty($expandedContactIds)) {
            $expandedItems = SalesInvoiceItem::query()
                ->select(
                    'sales_invoice_items.*',
                    'sales_invoices.contact_id',
                    'sales_invoices.invoice_number',
                    'sales_invoices.transaction_date',
                    'products.name as product_name',
                    'products.sku as product_sku',
                    'categories.name as category_name'
                )
                ->join('sales_invoices', 'sales_invoice_items.sales_invoice_id', '=', 'sales_invoices.id')
                ->join('products', 'sales_invoice_items.product_id', '=', 'products.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->whereIn('sales_invoices.contact_id', $expandedContactIds)
                ->whereBetween('sales_invoices.transaction_date', [$this->startDate, $this->endDate])
                ->orderBy('sales_invoices.transaction_date', 'desc')
                ->get()
                ->groupBy('contact_id');
        }

        // Map data to results
        $results = collect($paginator->items())->map(function ($contact) use ($expandedItems) {
            return [
                'id' => $contact->id,
                'name' => $contact->name,
                'company' => $contact->company ?? '-',
                'phone' => $contact->phone ?? '-',
                'total_transaksi' => (int) ($contact->total_transaksi ?? 0),
                'total_qty' => (float) ($contact->total_qty ?? 0),
                'pendapatan' => (float) ($contact->total_pendapatan ?? 0),
                'items' => $expandedItems->get($contact->id, collect())->map(function ($item) {
                    return [
                        'invoice_number' => $item->invoice_number,
                        'date' => Carbon::parse($item->transaction_date)->format('d/m/Y'),
                        'product_name' => $item->product_name,
                        'product_sku' => $item->product_sku ?? '-',
                        'category_name' => $item->category_name ?? 'Barang Jadi',
                        'description' => $item->description ?? '-',
                        'qty' => (float) $item->qty,
                        'price' => (float) $item->unit_price,
                        'total' => (float) $item->subtotal,
                    ];
                }),
            ];
        });

        // Global Totals via separate query for accuracy
        $globalStatsRaw = DB::query()
            ->selectRaw('SUM(sii.subtotal) as global_pendapatan')
            ->from('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'sii.sales_invoice_id', '=', 'si.id')
            ->whereBetween('si.transaction_date', [$this->startDate, $this->endDate])
            ->first();

        $globalPendapatan = (float) ($globalStatsRaw->global_pendapatan ?? 0);

        return [
            'results' => $results,
            'paginator' => $paginator,
            'totalCount' => $paginator->total(),
            'globalPendapatan' => $globalPendapatan,
        ];
    }
}
