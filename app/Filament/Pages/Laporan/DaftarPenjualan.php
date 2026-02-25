<?php

namespace App\Filament\Pages\Laporan;

use App\Models\ReceivablePayment;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class DaftarPenjualan extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected static string $paginationView = 'filament-actions::link-pagination';

    protected string $view = 'filament.pages.laporan.daftar-penjualan';

    protected static ?string $title = 'Daftar Penjualan';

    protected static ?string $slug = 'daftar-penjualan';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public int $perPage = 15;
    public array $expandedInvoices = [];

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function toggleInvoice($id): void
    {
        if (in_array($id, $this->expandedInvoices)) {
            $this->expandedInvoices = array_diff($this->expandedInvoices, [$id]);
        } else {
            $this->expandedInvoices[] = $id;
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

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Daftar Penjualan',
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
            Action::make('export')
                ->label('Ekspor')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->url('#'),
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
        $query = SalesInvoice::query()
            ->with(['contact', 'salesOrder', 'items.product', 'tags'])
            ->whereBetween('transaction_date', [$this->startDate, $this->endDate])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('invoice_number', 'desc');

        $paginator = $query->paginate($this->perPage);

        // Calculate page totals
        $pageSubtotal = 0;
        $pageTax = 0;
        $pageTotal = 0;
        $pagePaid = 0;
        $pageBalance = 0;

        foreach ($paginator->items() as $invoice) {
            $pageSubtotal += (float) $invoice->sub_total;
            $pageTax += (float) $invoice->total_tax;
            $pageTotal += (float) $invoice->total_amount;

            $receivable = \App\Models\Receivable::where('invoice_number', $invoice->invoice_number)->first();
            $paid = ($receivable ? $receivable->payments()->sum('amount') : 0) + ($invoice->down_payment ?? 0);

            $pagePaid += (float) $paid;
            $pageBalance += (float) $invoice->balance_due;
        }

        // Calculate global totals
        $globalStats = SalesInvoice::query()
            ->whereBetween('transaction_date', [$this->startDate, $this->endDate])
            ->selectRaw('SUM(sub_total) as sub_total, SUM(total_tax) as total_tax, SUM(total_amount) as total_amount, SUM(down_payment) as down_payment')
            ->first();

        // For global paid/balance, we need to consider payments
        $globalPaid = (float) \App\Models\ReceivablePayment::whereIn('receivable_id', function ($q) {
            $q->select('id')->from('receivables')->whereIn('invoice_number', function ($q) {
                $q->select('invoice_number')->from('sales_invoices')
                    ->whereBetween('transaction_date', [$this->startDate, $this->endDate]);
            });
        })->sum('amount') + (float) ($globalStats->down_payment ?? 0);
        $globalBalance = (float) (($globalStats->total_amount ?? 0) - $globalPaid);

        return [
            'invoices' => $paginator->items(),
            'paginator' => $paginator,
            'pageStats' => [
                'subtotal' => $pageSubtotal,
                'tax' => $pageTax,
                'total' => $pageTotal,
                'paid' => $pagePaid,
                'balance' => $pageBalance,
            ],
            'globalStats' => [
                'subtotal' => (float) ($globalStats->sub_total ?? 0),
                'tax' => (float) ($globalStats->total_tax ?? 0),
                'total' => (float) ($globalStats->total_amount ?? 0),
                'paid' => $globalPaid,
                'balance' => $globalBalance,
            ],
        ];
    }
}
