<?php

namespace App\Filament\Pages\Laporan;

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

class ProfitabilitasTagihan extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected string $view = 'filament.pages.laporan.profitabilitas-tagihan';
    protected static ?string $title = 'Profitabilitas per Tagihan';
    protected static ?string $slug = 'profitabilitas-tagihan';
    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $search = '';
    public $perPage = 15;
    public $expandedInvoices = [];

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
            'Profitabilitas per Tagihan',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function toggleInvoice($id)
    {
        if (in_array($id, $this->expandedInvoices)) {
            $this->expandedInvoices = array_values(array_diff($this->expandedInvoices, [$id]));
        } else {
            $this->expandedInvoices[] = $id;
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
        $query = SalesInvoice::query()
            ->with(['contact', 'items.product'])
            ->whereBetween('transaction_date', [$this->startDate, $this->endDate])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('invoice_number', 'like', "%{$this->search}%")
                        ->orWhere('reference', 'like', "%{$this->search}%")
                        ->orWhereHas('contact', function ($cq) {
                            $cq->where('name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->orderBy('transaction_date', 'desc')
            ->orderBy('invoice_number', 'desc');

        $paginator = $query->paginate($this->perPage);

        // Calculate per-invoice profitability
        $invoices = collect($paginator->items())->map(function ($invoice) {
            $totalPenjualan = (float) $invoice->total_amount;

            // Calculate HPP: sum of (item qty * product cost_of_goods)
            $totalHpp = 0;
            $totalPemotongan = 0; // discount
            $items = [];

            foreach ($invoice->items as $item) {
                $qty = (float) ($item->qty ?? 0);
                $hpp = $qty * (float) ($item->product->cost_of_goods ?? 0);
                $itemSales = (float) ($item->subtotal ?? 0);
                $itemProfit = $itemSales - $hpp;
                $itemMargin = $itemSales > 0 ? ($itemProfit / $itemSales) * 100 : 0;

                $totalHpp += $hpp;

                $items[] = [
                    'name' => $item->product->name ?? '-',
                    'sku' => $item->product->sku ?? '-',
                    'qty' => $qty,
                    'total_penjualan' => $itemSales,
                    'total_hpp' => $hpp,
                    'total_profit' => $itemProfit,
                    'margin' => $itemMargin,
                ];
            }

            $totalProfit = $totalPenjualan - $totalHpp;
            $margin = $totalPenjualan > 0 ? ($totalProfit / $totalPenjualan) * 100 : 0;

            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'reference' => $invoice->reference ?? '-',
                'contact_name' => $invoice->contact->name ?? '-',
                'date' => $invoice->transaction_date->format('d M Y'),
                'total_penjualan' => $totalPenjualan,
                'total_hpp' => $totalHpp,
                'total_pemotongan' => $totalPemotongan,
                'total_profit' => $totalProfit,
                'margin' => $margin,
                'items' => $items,
            ];
        });

        // Page subtotals
        $pageStats = [
            'total_penjualan' => $invoices->sum('total_penjualan'),
            'total_hpp' => $invoices->sum('total_hpp'),
            'total_pemotongan' => $invoices->sum('total_pemotongan'),
            'total_profit' => $invoices->sum('total_profit'),
        ];

        // Global totals
        $allInvoiceIds = SalesInvoice::query()
            ->whereBetween('transaction_date', [$this->startDate, $this->endDate])
            ->pluck('id');

        $globalPenjualan = (float) SalesInvoice::whereIn('id', $allInvoiceIds)->sum('total_amount');

        $globalHpp = (float) SalesInvoiceItem::whereIn('sales_invoice_id', $allInvoiceIds)
            ->join('products', 'sales_invoice_items.product_id', '=', 'products.id')
            ->selectRaw('SUM(sales_invoice_items.qty * products.cost_of_goods) as total_hpp')
            ->value('total_hpp') ?? 0;

        $globalProfit = $globalPenjualan - $globalHpp;

        return [
            'invoices' => $invoices,
            'paginator' => $paginator,
            'totalCount' => $paginator->total(),
            'pageStats' => $pageStats,
            'globalStats' => [
                'total_penjualan' => $globalPenjualan,
                'total_hpp' => $globalHpp,
                'total_pemotongan' => 0,
                'total_profit' => $globalProfit,
            ],
        ];
    }
}
