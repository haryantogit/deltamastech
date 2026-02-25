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
    public $perPage = 15;
    public $search = '';

    protected $queryString = [
        'startDate',
        'endDate',
        'perPage',
        'search',
    ];

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate = Carbon::now()->endOfMonth()->toDateString();
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
            Action::make('filter')
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

        $paginator = $query->paginate($this->perPage);

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
