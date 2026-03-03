<?php

namespace App\Filament\Pages\Laporan;

use App\Models\PurchaseInvoice;
use App\Models\Debt;
use App\Models\DebtPayment;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class DetailPembelian extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected static string $paginationView = 'filament-actions::link-pagination';

    protected string $view = 'filament.pages.laporan.detail-pembelian';

    protected static ?string $title = 'Detail Pembelian';

    protected static ?string $slug = 'detail-pembelian';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $search = '';
    public $perPage = 10;
    public array $expandedInvoices = [];

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'perPage' => ['except' => 10],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        if (!$this->startDate) {
            $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        }
        if (!$this->endDate) {
            $this->endDate = Carbon::now()->format('Y-m-d');
        }
    }

    public function toggleInvoice($id): void
    {
        if (in_array($id, $this->expandedInvoices)) {
            $this->expandedInvoices = array_values(array_diff($this->expandedInvoices, [$id]));
        } else {
            $this->expandedInvoices[] = $id;
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

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

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Detail Pembelian',
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
        $query = PurchaseInvoice::query()
            ->with(['supplier', 'purchaseOrder', 'items.product', 'items.unit', 'tags'])
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->when($this->search, function ($q) {
                $q->where(function ($sq) {
                    $sq->where('number', 'like', "%{$this->search}%")
                        ->orWhere('reference', 'like', "%{$this->search}%")
                        ->orWhereHas('supplier', fn($cq) => $cq->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->orderBy('date', 'desc')
            ->orderBy('number', 'desc');

        $perPageCount = $this->perPage === 'all' ? max(1, (clone $query)->count()) : $this->perPage;
        $paginator = $query->paginate($perPageCount);

        // Process invoices for display
        $invoices = collect($paginator->items())->map(function ($invoice) {
            $debt = Debt::where('reference', $invoice->number)->first();
            $paid = ($debt ? $debt->payments()->sum('amount') : 0) + ($invoice->down_payment ?? 0);

            return [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'po_number' => $invoice->purchaseOrder->number ?? '-',
                'supplier_name' => $invoice->supplier->name ?? '-',
                'date' => $invoice->date->format('d M Y'),
                'status' => $invoice->status,
                'sub_total' => (float) $invoice->sub_total,
                'tax_amount' => (float) ($invoice->tax_amount ?? 0),
                'total_amount' => (float) $invoice->total_amount,
                'total_paid' => (float) $paid,
                'balance_due' => (float) $invoice->balance_due,
                'reference' => $invoice->reference ?? '-',
                'tags' => $invoice->tags,
                'items' => $invoice->items->map(fn($item) => [
                    'name' => $item->product->name ?? '-',
                    'sku' => $item->product->sku ?? '-',
                    'qty' => (float) $item->quantity,
                    'unit' => $item->unit->name ?? '-',
                    'price' => (float) ($item->unit_price ?? 0),
                    'discount' => (float) ($item->discount_percent ?? 0),
                    'tax' => (float) ($item->tax_amount ?? 0),
                    'subtotal' => (float) ($item->total_price ?? 0),
                    'description' => $item->description,
                ])->toArray(),
            ];
        });

        // Page stats
        $pageStats = [
            'sub_total' => $invoices->sum('sub_total'),
            'total_tax' => $invoices->sum('tax_amount'),
            'total_amount' => $invoices->sum('total_amount'),
            'total_paid' => $invoices->sum('total_paid'),
            'balance_due' => $invoices->sum('balance_due'),
        ];

        // Global stats
        $allInvoices = PurchaseInvoice::query()
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->get();

        $globalPaid = 0;
        foreach ($allInvoices as $inv) {
            $debt = Debt::where('reference', $inv->number)->first();
            $globalPaid += ($debt ? $debt->payments()->sum('amount') : 0) + ($inv->down_payment ?? 0);
        }

        $globalStats = [
            'sub_total' => (float) $allInvoices->sum('sub_total'),
            'total_tax' => (float) $allInvoices->sum('tax_amount'),
            'total_amount' => (float) $allInvoices->sum('total_amount'),
            'total_paid' => (float) $globalPaid,
            'balance_due' => (float) $allInvoices->sum('balance_due'),
        ];

        return [
            'invoices' => $invoices,
            'paginator' => $paginator,
            'pageStats' => $pageStats,
            'globalStats' => $globalStats,
        ];
    }
}

