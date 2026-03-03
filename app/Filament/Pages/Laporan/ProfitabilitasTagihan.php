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
    public $perPage = 10;
    public $expandedInvoices = [];

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
            'Profitabilitas per Tagihan',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $startDate = $this->startDate ?? now()->startOfYear()->toDateString();
        $endDate = $this->endDate ?? now()->toDateString();
        $startFmt = Carbon::parse($startDate)->format('d/m/Y');
        $endFmt = Carbon::parse($endDate)->format('d/m/Y');

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

        $perPage = $this->perPage === 'all' ? max(1, $query->count()) : $this->perPage;
        $paginator = $query->paginate($perPage);

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


