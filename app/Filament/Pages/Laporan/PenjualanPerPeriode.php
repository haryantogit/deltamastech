<?php

namespace App\Filament\Pages\Laporan;

use App\Models\SalesInvoice;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class PenjualanPerPeriode extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.laporan.penjualan-per-periode';

    protected static ?string $title = 'Penjualan per Periode';

    protected static ?string $slug = 'penjualan-per-periode';

    protected static bool $shouldRegisterNavigation = false;

    public $startDate;
    public $endDate;
    public $periodType = 'monthly'; // daily, monthly, quarterly, yearly

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Penjualan per Periode',
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
                    \Filament\Forms\Components\Select::make('periodType')
                        ->label('Jenis Periode')
                        ->options([
                            'daily' => 'Harian',
                            'monthly' => 'Bulanan',
                            'yearly' => 'Tahunan',
                        ])
                        ->default($this->periodType)
                        ->required(),
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
                    $this->periodType = $data['periodType'];
                    $this->startDate = $data['startDate'];
                    $this->endDate = $data['endDate'];
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
        $groupBy = match ($this->periodType) {
            'daily' => "DATE_FORMAT(si.transaction_date, '%Y-%m-%d')",
            'yearly' => "DATE_FORMAT(si.transaction_date, '%Y')",
            default => "DATE_FORMAT(si.transaction_date, '%Y-%m')",
        };

        $results = DB::table('sales_invoices as si')
            ->join('sales_invoice_items as sii', 'si.id', '=', 'sii.sales_invoice_id')
            ->whereBetween('si.transaction_date', [$this->startDate, $this->endDate])
            ->where('si.status', '!=', 'cancelled')
            ->select(
                DB::raw("$groupBy as period"),
                DB::raw('SUM(sii.qty) as total_qty'),
                DB::raw('SUM(si.total_amount) as total_amount') // This might over-sum if multiple items, wait
            )
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get();

        // Correcting total_amount: si.total_amount is per invoice. 
        // If we join with items, we should sum sii.subtotal or group invoices first.
        // Let's use a subquery for better accuracy.

        $results = DB::table(function ($query) {
            $query->from('sales_invoices as si')
                ->join('sales_invoice_items as sii', 'si.id', '=', 'sii.sales_invoice_id')
                ->whereBetween('si.transaction_date', [$this->startDate, $this->endDate])
                ->where('si.status', '!=', 'cancelled')
                ->select(
                    'si.id',
                    'si.transaction_date',
                    'si.total_amount',
                    DB::raw('SUM(sii.qty) as invoice_qty')
                )
                ->groupBy('si.id', 'si.transaction_date', 'si.total_amount');
        }, 'agg')
            ->select(
                DB::raw(match ($this->periodType) {
                    'daily' => "DATE_FORMAT(transaction_date, '%Y-%m-%d')",
                    'yearly' => "DATE_FORMAT(transaction_date, '%Y')",
                    default => "DATE_FORMAT(transaction_date, '%Y-%m')",
                } . " as period"),
                DB::raw('SUM(invoice_qty) as total_qty'),
                DB::raw('SUM(total_amount) as total_amount')
            )
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get();

        // Format labels for charts and table
        foreach ($results as $row) {
            $dt = null;
            if ($this->periodType === 'daily') {
                $dt = Carbon::parse($row->period);
                $row->period_label = $dt->format('d M Y');
            } elseif ($this->periodType === 'yearly') {
                $row->period_label = $row->period;
            } else {
                $dt = Carbon::parse($row->period . '-01');
                $row->period_label = $dt->format('M Y');
            }
        }

        $grandTotalQty = $results->sum('total_qty');
        $grandTotalAmount = $results->sum('total_amount');

        return [
            'results' => $results,
            'grandTotalQty' => $grandTotalQty,
            'grandTotalAmount' => $grandTotalAmount,
            'chartLabels' => $results->pluck('period_label')->toArray(),
            'chartQtyData' => $results->pluck('total_qty')->toArray(),
            'chartAmountData' => $results->pluck('total_amount')->toArray(),
        ];
    }
}
