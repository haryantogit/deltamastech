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
    public ?string $search = null;
    public string $dateType = 'date'; // date (Tanggal Tagihan), settlement (Tanggal Pelunasan)
    public int $perPage = 15;

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDateType(): void
    {
        $this->resetPage();
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Pelunasan Pembayaran Tagihan Pembelian',
        ];
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
            Action::make('bagikan')
                ->label('Bagikan')
                ->icon('heroicon-o-share')
                ->color('gray'),
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
        // Subquery to get payment dates from DebtPayment
        $paymentDatesSubquery = DB::table('debt_payments')
            ->select(
                'debt_id',
                DB::raw('MIN(date) as first_payment_date'),
                DB::raw('MAX(date) as full_settlement_date')
            )
            ->groupBy('debt_id');

        $query = PurchaseInvoice::query()
            ->join('contacts', 'purchase_invoices.supplier_id', '=', 'contacts.id')
            ->leftJoin('debts', 'purchase_invoices.number', '=', 'debts.reference')
            ->leftJoinSub($paymentDatesSubquery, 'p', function ($join) {
                $join->on('debts.id', '=', 'p.debt_id');
            })
            ->select(
                'purchase_invoices.id',
                'purchase_invoices.number',
                'contacts.name as supplier_name',
                'purchase_invoices.date as invoice_date',
                'purchase_invoices.total_amount',
                'purchase_invoices.status',
                'purchase_invoices.payment_status',
                'purchase_invoices.down_payment',
                DB::raw('CASE WHEN purchase_invoices.down_payment > 0 THEN purchase_invoices.date ELSE p.first_payment_date END as display_first_payment'),
                DB::raw('CASE WHEN purchase_invoices.payment_status = "paid" THEN p.full_settlement_date ELSE NULL END as display_full_settlement')
            );

        // Filter based on dateType
        if ($this->dateType === 'date') {
            $query->whereBetween('purchase_invoices.date', [$this->startDate, $this->endDate]);
        } elseif ($this->dateType === 'settlement') {
            $query->whereBetween('p.full_settlement_date', [$this->startDate, $this->endDate])
                ->where('purchase_invoices.payment_status', '=', 'paid');
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('purchase_invoices.number', 'like', "%{$this->search}%")
                    ->orWhere('contacts.name', 'like', "%{$this->search}%");
            });
        }

        $paginator = $query->orderBy('invoice_date', 'desc')->paginate($this->perPage);

        return [
            'items' => $paginator->items(),
            'paginator' => $paginator,
            'grandTotal' => $query->sum('purchase_invoices.total_amount'),
        ];
    }
}
