<?php

namespace App\Filament\Pages\Laporan;

use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Receivable;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class DaftarPenjualan extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected string $view = 'filament.pages.laporan.daftar-penjualan';

    protected static ?string $title = 'Detail Penjualan';
    protected static ?string $navigationLabel = 'Detail Penjualan';
    protected static ?string $slug = 'detail-penjualan';
    protected static bool $shouldRegisterNavigation = false;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $search = '';
    public $perPage = 10;

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'perPage' => ['except' => 10],
        'search' => ['except' => ''],
    ];
    public array $expandedInvoices = [];

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function toggleInvoice($id): void
    {
        if (in_array($id, $this->expandedInvoices)) {
            $this->expandedInvoices = array_values(array_diff($this->expandedInvoices, [$id]));
        } else {
            $this->expandedInvoices[] = $id;
        }
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Detail Penjualan',
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
            ->with(['contact', 'salesOrder', 'items.product', 'items.unit', 'receivable'])
            ->whereBetween('transaction_date', [$this->startDate, $this->endDate])
            ->when($this->search, function ($q) {
                $q->where(function ($sq) {
                    $sq->where('invoice_number', 'like', "%{$this->search}%")
                        ->orWhere('reference', 'like', "%{$this->search}%")
                        ->orWhereHas('contact', fn($cq) => $cq->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->orderBy('transaction_date', 'desc')
            ->orderBy('invoice_number', 'desc');

        $perPageCount = $this->perPage === 'all' ? max(1, (clone $query)->count()) : $this->perPage;
        $paginator = $query->paginate($perPageCount);

        // Process invoices for display
        $invoices = collect($paginator->items())->map(function ($invoice) {
            $receivable = Receivable::where('invoice_number', $invoice->invoice_number)->first();
            $totalPaid = ($receivable ? $receivable->payments()->sum('amount') : 0) + ($invoice->down_payment ?? 0);

            // Get retur amount from negative items
            $returAmount = abs($invoice->items->where('qty', '<', 0)->sum('subtotal'));

            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'reference' => $invoice->reference ?? '-',
                'contact_name' => $invoice->contact->name ?? '-',
                'date' => $invoice->transaction_date->format('d M Y'),
                'status' => $invoice->status,
                'sub_total' => (float) $invoice->sub_total,
                'total_tax' => (float) ($invoice->total_tax ?? 0),
                'discount_total' => (float) ($invoice->discount_total ?? 0),
                'shipping_cost' => (float) ($invoice->shipping_cost ?? 0),
                'total_amount' => (float) $invoice->total_amount,
                'total_paid' => (float) $totalPaid,
                'retur_amount' => (float) $returAmount,
                'balance_due' => (float) $invoice->balance_due,
                'items' => $invoice->items->map(fn($item) => [
                    'name' => $item->product->name ?? '-',
                    'sku' => $item->product->sku ?? '-',
                    'qty' => (float) $item->qty,
                    'unit' => $item->unit->name ?? '-',
                    'price' => (float) ($item->price ?? 0),
                    'discount' => (float) ($item->discount_percent ?? 0),
                    'tax' => (float) ($item->tax_amount ?? 0),
                    'subtotal' => (float) ($item->subtotal ?? 0),
                ])->toArray(),
            ];
        });

        // Page totals
        $pageStats = [
            'sub_total' => $invoices->sum('sub_total'),
            'total_tax' => $invoices->sum('total_tax'),
            'discount_total' => $invoices->sum('discount_total'),
            'total_amount' => $invoices->sum('total_amount'),
            'total_paid' => $invoices->sum('total_paid'),
            'retur_amount' => $invoices->sum('retur_amount'),
            'balance_due' => $invoices->sum('balance_due'),
        ];

        // Global totals (all invoices in date range, ignoring search/pagination)
        $allInvoices = SalesInvoice::query()
            ->with(['items'])
            ->whereBetween('transaction_date', [$this->startDate, $this->endDate])
            ->get();

        $globalPaid = 0;
        $globalRetur = 0;
        $globalBalanceDue = 0;
        foreach ($allInvoices as $inv) {
            $rec = Receivable::where('invoice_number', $inv->invoice_number)->first();
            $paid = ($rec ? $rec->payments()->sum('amount') : 0) + ($inv->down_payment ?? 0);
            $globalPaid += $paid;
            $globalRetur += abs($inv->items->where('qty', '<', 0)->sum('subtotal'));
            $globalBalanceDue += $inv->balance_due;
        }

        $globalStats = [
            'sub_total' => (float) $allInvoices->sum('sub_total'),
            'total_tax' => (float) $allInvoices->sum('total_tax'),
            'discount_total' => (float) $allInvoices->sum('discount_total'),
            'shipping_cost' => (float) $allInvoices->sum('shipping_cost'),
            'total_amount' => (float) $allInvoices->sum('total_amount'),
            'total_paid' => (float) $globalPaid,
            'retur_amount' => (float) $globalRetur,
            'balance_due' => (float) $globalBalanceDue,
        ];

        return [
            'invoices' => $invoices,
            'paginator' => $paginator,
            'pageStats' => $pageStats,
            'globalStats' => $globalStats,
        ];
    }
}

