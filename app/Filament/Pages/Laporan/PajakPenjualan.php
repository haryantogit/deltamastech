<?php

namespace App\Filament\Pages\Laporan;

use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class PajakPenjualan extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected string $view = 'filament.pages.laporan.pajak-penjualan';

    protected static ?string $title = 'Pajak Penjualan';

    protected static ?string $slug = 'pajak-penjualan';

    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $search = '';
    public array $expandedRows = [];

    public function updatedSearch(): void
    {
        // Re-calculate view data when search changes
    }

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
    ];

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

    public function toggleRow($row): void
    {
        if (in_array($row, $this->expandedRows)) {
            $this->expandedRows = array_diff($this->expandedRows, [$row]);
        } else {
            $this->expandedRows[] = $row;
        }
    }

    public function mount()
    {
        if (!$this->startDate) {
            $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        }
        if (!$this->endDate) {
            $this->endDate = Carbon::now()->format('Y-m-d');
        }
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Pajak Penjualan',
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
                ->url(\App\Filament\Pages\ReportPage::getUrl()),
        ];
    }

    public function getViewData(): array
    {
        $salesQuery = SalesInvoice::query()
            ->leftJoin('contacts', 'sales_invoices.contact_id', '=', 'contacts.id')
            ->whereBetween('transaction_date', [$this->startDate, $this->endDate])
            ->where('sales_invoices.status', '!=', 'cancelled');

        if (!empty($this->search)) {
            $salesQuery->where(function ($q) {
                $q->where('sales_invoices.invoice_number', 'like', '%' . $this->search . '%')
                    ->orWhere('contacts.name', 'like', '%' . $this->search . '%');
            });
        }

        $salesTax = (clone $salesQuery)
            ->select(
                DB::raw('SUM(sales_invoices.sub_total) as net'),
                DB::raw('SUM(sales_invoices.total_tax) as tax')
            )
            ->first();

        $purchaseQuery = PurchaseInvoice::query()
            ->leftJoin('contacts', 'purchase_invoices.supplier_id', '=', 'contacts.id')
            ->whereBetween('purchase_invoices.date', [$this->startDate, $this->endDate])
            ->where('purchase_invoices.status', '!=', 'cancelled');

        if (!empty($this->search)) {
            $purchaseQuery->where(function ($q) {
                $q->where('purchase_invoices.number', 'like', '%' . $this->search . '%')
                    ->orWhere('contacts.name', 'like', '%' . $this->search . '%');
            });
        }

        $purchaseTax = (clone $purchaseQuery)
            ->select(
                DB::raw('SUM(sub_total) as net'),
                DB::raw('SUM(tax_amount) as tax')
            )
            ->first();

        $salesDetails = [];
        if (in_array('sales', $this->expandedRows)) {
            $salesDetails = $salesQuery
                ->select('sales_invoices.id', 'sales_invoices.invoice_number as number', 'sales_invoices.transaction_date as date', 'sales_invoices.sub_total as net', 'sales_invoices.total_tax as tax', 'sales_invoices.total_amount as total', 'contacts.name as contact_name')
                ->orderBy('sales_invoices.transaction_date', 'desc')
                ->get();
        }

        $purchaseDetails = [];
        if (in_array('purchase', $this->expandedRows)) {
            $purchaseDetails = $purchaseQuery
                ->select('purchase_invoices.id', 'purchase_invoices.number', 'purchase_invoices.date', 'purchase_invoices.sub_total as net', 'purchase_invoices.tax_amount as tax', 'purchase_invoices.total_amount as total', 'contacts.name as contact_name')
                ->orderBy('purchase_invoices.date', 'desc')
                ->get();
        }

        return [
            'sales' => $salesTax,
            'purchase' => $purchaseTax,
            'salesDetails' => $salesDetails,
            'purchaseDetails' => $purchaseDetails,
            'totalNet' => ($salesTax->net ?? 0) + ($purchaseTax->net ?? 0),
            'totalTax' => ($salesTax->tax ?? 0) - ($purchaseTax->tax ?? 0), // PPN Keluaran - PPN Masukan
            'chartData' => [
                ['label' => 'PPN Keluaran', 'value' => (float) ($salesTax->tax ?? 0), 'color' => '#10b981'],
                ['label' => 'PPN Masukan', 'value' => abs((float) ($purchaseTax->tax ?? 0)), 'color' => '#f59e0b'],
            ],
        ];
    }
}
