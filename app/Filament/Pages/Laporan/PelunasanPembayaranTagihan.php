<?php

namespace App\Filament\Pages\Laporan;

use App\Models\SalesInvoice;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Livewire\WithPagination;

class PelunasanPembayaranTagihan extends Page implements \Filament\Actions\Contracts\HasActions
{
    use \Filament\Actions\Concerns\InteractsWithActions;
    use WithPagination;

    protected static ?string $navigationLabel = 'Pelunasan Pembayaran Tagihan';
    protected static ?string $title = 'Pelunasan Pembayaran Tagihan';
    protected static ?string $slug = 'pelunasan-pembayaran-tagihan';
    protected string $view = 'filament.pages.laporan.pelunasan-pembayaran-tagihan';
    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $perPage = 10;
    public $search = '';

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'perPage' => ['except' => 10],
        'search' => ['except' => ''],
    ];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfYear()->toDateString();
        $this->endDate = Carbon::now()->toDateString();
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
            'Pelunasan Pembayaran Tagihan',
        ];
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['startDate', 'endDate', 'perPage', 'search'])) {
            $this->resetPage();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filterReport')
                ->label('Filter')
                ->icon('heroicon-m-funnel')
                ->color('gray')
                ->form([
                    DatePicker::make('startDate')
                        ->label('Tanggal Mulai')
                        ->default($this->startDate)
                        ->required(),
                    DatePicker::make('endDate')
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
        $query = DB::table('sales_invoices as si')
            ->leftJoin('contacts as c', 'si.contact_id', '=', 'c.id')
            ->whereBetween('si.transaction_date', [$this->startDate, $this->endDate])
            ->where('si.status', '!=', 'cancelled');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('si.invoice_number', 'like', "%{$this->search}%")
                    ->orWhere('c.name', 'like', "%{$this->search}%");
            });
        }

        // Subquery for payment aggregation
        $paymentAgg = DB::table('receivable_payments as rp')
            ->join('receivables as r', 'rp.receivable_id', '=', 'r.id')
            ->select(
                'r.invoice_number',
                DB::raw('MIN(rp.date) as first_payment_date'),
                DB::raw('MAX(rp.date) as settlement_date')
            )
            ->groupBy('r.invoice_number');

        $query->leftJoinSub($paymentAgg, 'pa', 'si.invoice_number', '=', 'pa.invoice_number')
            ->select(
                'si.invoice_number',
                'c.name as contact_name',
                'si.transaction_date',
                'si.total_amount',
                'si.status',
                'si.down_payment',
                DB::raw('CASE WHEN si.down_payment > 0 THEN si.transaction_date ELSE pa.first_payment_date END as display_first_payment'),
                DB::raw('CASE WHEN si.status = "paid" THEN pa.settlement_date ELSE NULL END as display_settlement_date')
            )
            ->orderBy('si.transaction_date', 'desc');

        $perPageCount = $this->perPage === 'all' ? max(1, (clone $query)->count()) : $this->perPage;
        $paginator = $query->paginate($perPageCount);

        // Calculate subtotal for current page
        $pageSubtotal = collect($paginator->items())->sum('total_amount');

        // Calculate global total for filtered range
        $globalTotal = DB::table('sales_invoices as si')
            ->leftJoin('contacts as c', 'si.contact_id', '=', 'c.id')
            ->whereBetween('si.transaction_date', [$this->startDate, $this->endDate])
            ->where('si.status', '!=', 'cancelled')
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('si.invoice_number', 'like', "%{$this->search}%")
                        ->orWhere('c.name', 'like', "%{$this->search}%");
                });
            })
            ->sum('si.total_amount');

        return [
            'results' => $paginator->items(),
            'paginator' => $paginator,
            'pageSubtotal' => $pageSubtotal,
            'globalTotal' => $globalTotal,
        ];
    }
}


