<?php

namespace App\Filament\Widgets;

use App\Models\SalesInvoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class SalesOverviewStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 12;

    protected $listeners = ['update-sales-overview-filter' => '$refresh'];

    protected function getStats(): array
    {
        $filter = request()->query('filter', 'year');
        $now = Carbon::now();

        if ($filter === 'year') {
            $startDate = $now->copy()->startOfYear();
            $endDate = $now->copy()->endOfYear();
            $periodLabel = 'Tahun Ini';
        } else {
            $startDate = $now->copy()->startOfMonth();
            $endDate = $now->copy()->endOfMonth();
            $periodLabel = 'Bulan Ini';
        }

        // Comparison period
        $diff = $startDate->diffInDays($endDate) + 1;
        $startOfPrev = $startDate->copy()->subDays($diff);
        $endOfPrev = $startDate->copy()->subDay();

        // 1. PENJUALAN (Total Sales Amount)
        $salesThisPeriod = SalesInvoice::whereBetween('transaction_date', [$startDate, $endDate])->sum('total_amount');
        $salesCount = SalesInvoice::whereBetween('transaction_date', [$startDate, $endDate])->count();

        $salesPrevPeriod = SalesInvoice::whereBetween('transaction_date', [$startOfPrev, $endOfPrev])->sum('total_amount');

        $salesTrend = $salesPrevPeriod > 0 ? (($salesThisPeriod - $salesPrevPeriod) / $salesPrevPeriod) * 100 : ($salesThisPeriod > 0 ? 100 : 0);
        $salesIcon = $salesTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';

        // 2. PEMBAYARAN DITERIMA (Total Paid Amount)
        $invoices = SalesInvoice::whereBetween('transaction_date', [$startDate, $endDate])->get();
        $paymentReceived = $invoices->sum(function ($invoice) {
            return $invoice->total_amount - $invoice->balance_due;
        });
        $paymentCount = SalesInvoice::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->count();

        // 3. MENUNGGU PEMBAYARAN (Unpaid Balance)
        $unpaidAmount = SalesInvoice::where('status', '!=', 'paid')->sum('balance_due');
        $unpaidCount = SalesInvoice::where('status', '!=', 'paid')->count();

        // 4. JATUH TEMPO (Overdue)
        $overdueAmount = SalesInvoice::where('status', '!=', 'paid')
            ->where('due_date', '<', $now)
            ->sum('balance_due');
        $overdueCount = SalesInvoice::where('status', '!=', 'paid')
            ->where('due_date', '<', $now)
            ->count();

        return [
            Stat::make('PENJUALAN', 'Rp ' . number_format($salesThisPeriod, 0, ',', '.'))
                ->description($salesCount . ' Tagihan ' . $periodLabel)
                ->descriptionIcon($salesIcon)
                ->color('info')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('PEMBAYARAN DITERIMA', 'Rp ' . number_format($paymentReceived, 0, ',', '.'))
                ->description($paymentCount . ' Tagihan Lunas ' . $periodLabel)
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([10, 12, 14, 15, 14, 13, 15]),

            Stat::make('MENUNGGU PEMBAYARAN', 'Rp ' . number_format($unpaidAmount, 0, ',', '.'))
                ->description($unpaidCount . ' Tagihan Belum Lunas')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([15, 4, 10, 2, 12, 4, 12]),

            Stat::make('JATUH TEMPO', 'Rp ' . number_format($overdueAmount, 0, ',', '.'))
                ->description($overdueCount . ' Tagihan Melewati Jatuh Tempo')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->chart([2, 10, 5, 15, 8, 20, 15]),
        ];
    }
}
