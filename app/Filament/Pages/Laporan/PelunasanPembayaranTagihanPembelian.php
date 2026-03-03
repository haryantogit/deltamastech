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

class PelunasanPembayaranTagihanPembelian extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected static string $paginationView = 'filament-actions::link-pagination';

    protected string $view = 'filament.pages.laporan.pelunasan-pembayaran-tagihan-pembelian';

    protected static ?string $title = 'Pelunasan Pembayaran Tagihan Pembelian';

    protected static ?string $slug = 'pelunasan-pembayaran-tagihan-pembelian';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $search = '';
    public string $dateType = 'date'; // date (Tanggal Tagihan), settlement (Tanggal Pelunasan)
    public $perPage = 10;

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'dateType' => ['except' => 'date'],
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
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
            'Pelunasan Pembayaran Tagihan Pembelian',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filterReport')
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
                    \Filament\Forms\Components\Select::make('dateType')
                        ->label('Filter Berdasarkan')
                        ->options([
                            'date' => 'Tanggal Tagihan',
                            'settlement' => 'Tanggal Pelunasan',
                        ])
                        ->default($this->dateType)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->startDate = $data['startDate'];
                    $this->endDate = $data['endDate'];
                    $this->dateType = $data['dateType'];
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
        $paymentDatesFullSubquery = DB::table('debt_payments')
            ->select(
                'debt_id',
                DB::raw('MIN(date) as first_payment_date'),
                DB::raw('MAX(date) as full_settlement_date')
            )
            ->groupBy('debt_id');

        // 1. Build Base Query for filtering
        $baseQuery = PurchaseInvoice::query()
            ->join('contacts', 'purchase_invoices.supplier_id', '=', 'contacts.id')
            ->leftJoin('debts', 'purchase_invoices.number', '=', 'debts.reference')
            ->leftJoinSub($paymentDatesFullSubquery, 'p', 'debts.id', '=', 'p.debt_id')
            ->where('purchase_invoices.status', '!=', 'cancelled');

        // Apply filters
        if ($this->dateType === 'date') {
            $baseQuery->whereBetween('purchase_invoices.date', [$this->startDate, $this->endDate]);
        } elseif ($this->dateType === 'settlement') {
            $baseQuery->whereBetween('p.full_settlement_date', [$this->startDate, $this->endDate])
                ->where('purchase_invoices.payment_status', '=', 'paid');
        }

        if ($this->search) {
            $baseQuery->where(function ($q) {
                $q->where('purchase_invoices.number', 'like', "%{$this->search}%")
                    ->orWhere('contacts.name', 'like', "%{$this->search}%")
                    ->orWhere('purchase_invoices.reference', 'like', "%{$this->search}%");
            });
        }

        // 2. Chart Data (using clone of filtered query)
        $dateColumn = $this->dateType === 'date' ? 'purchase_invoices.date' : 'p.full_settlement_date';

        $chartResults = (clone $baseQuery)
            ->select(
                DB::raw("DATE_FORMAT({$dateColumn}, '%Y-%m') as period"),
                DB::raw('SUM(purchase_invoices.total_amount) as total_amount')
            )
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get()
            ->keyBy('period');

        // Generate all months in range for chart
        $chartData = collect();
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        $current = $start->copy()->startOfMonth();
        $endPeriod = $end->format('Y-m');

        while ($current->format('Y-m') <= $endPeriod) {
            $periodKey = $current->format('Y-m');
            $label = $current->format('M Y');
            $data = $chartResults->get($periodKey);
            $chartData->put($periodKey, [
                'label' => $label,
                'amount' => $data ? (float) $data->total_amount : 0,
            ]);
            $current->addMonth();
        }

        $chartTitle = $this->dateType === 'date' ? 'TREN TAGIHAN PEMBELIAN' : 'TREN PELUNASAN PEMBAYARAN';

        $perPageCount = $this->perPage === 'all' ? max(1, (clone $baseQuery)->count()) : $this->perPage;
        $paginator = (clone $baseQuery)
            ->select(
                'purchase_invoices.*',
                'contacts.name as supplier_name',
                DB::raw('CASE WHEN purchase_invoices.down_payment > 0 THEN purchase_invoices.date ELSE p.first_payment_date END as display_first_payment'),
                DB::raw('CASE WHEN purchase_invoices.payment_status = "paid" THEN p.full_settlement_date ELSE NULL END as display_full_settlement')
            )
            ->orderBy('purchase_invoices.date', 'desc')
            ->paginate($perPageCount);

        return [
            'items' => collect($paginator->items())->map(fn($item) => [
                'number' => $item->number,
                'supplier_name' => $item->supplier_name,
                'invoice_date' => $item->date ? Carbon::parse($item->date)->format('d/m/Y') : '-',
                'display_first_payment' => $item->display_first_payment ? Carbon::parse($item->display_first_payment)->format('d/m/Y') : '-',
                'display_full_settlement' => $item->display_full_settlement ? Carbon::parse($item->display_full_settlement)->format('d/m/Y') : '-',
                'total_amount' => (float) $item->total_amount,
            ]),
            'paginator' => $paginator,
            'grandTotal' => (float) (clone $baseQuery)->sum('purchase_invoices.total_amount'),
            'chartLabels' => $chartData->pluck('label')->toArray(),
            'chartAmountData' => $chartData->pluck('amount')->toArray(),
            'chartTitle' => $chartTitle,
        ];
    }
}

