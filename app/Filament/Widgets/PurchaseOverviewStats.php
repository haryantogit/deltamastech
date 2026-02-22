<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseInvoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class PurchaseOverviewStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 12;

    protected $listeners = ['update-purchase-overview-filter' => '$refresh'];

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

        // 1. PEMBELIAN (Total Purchase Amount)
        $purchaseThisPeriod = PurchaseInvoice::whereBetween('date', [$startDate, $endDate])->sum('total_amount');
        $purchaseCount = PurchaseInvoice::whereBetween('date', [$startDate, $endDate])->count();

        $purchasePrevPeriod = PurchaseInvoice::whereBetween('date', [$startOfPrev, $endOfPrev])->sum('total_amount');

        $purchaseTrend = $purchasePrevPeriod > 0 ? (($purchaseThisPeriod - $purchasePrevPeriod) / $purchasePrevPeriod) * 100 : ($purchaseThisPeriod > 0 ? 100 : 0);
        $purchaseIcon = $purchaseTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';

        // 2. PEMBAYARAN TERKIRIM (Total Paid Amount)
        $invoices = PurchaseInvoice::whereBetween('date', [$startDate, $endDate])->get();
        $paymentSent = $invoices->sum(function ($invoice) {
            return $invoice->total_amount - $invoice->balance_due;
        });
        $paymentCount = PurchaseInvoice::whereBetween('date', [$startDate, $endDate])
            ->where(function ($query) {
                $query->where('status', 'paid')
                    ->orWhere('payment_status', 'paid');
            })
            ->count();

        // 3. MENUNGGU PEMBAYARAN (Unpaid Balance)
        $unpaidAmount = PurchaseInvoice::where('status', '!=', 'paid')
            ->where('payment_status', '!=', 'paid')
            ->get()
            ->sum('balance_due');
        $unpaidCount = PurchaseInvoice::where('status', '!=', 'paid')
            ->where('payment_status', '!=', 'paid')
            ->count();

        // 4. JATUH TEMPO (Overdue)
        $overdueAmount = PurchaseInvoice::where('status', '!=', 'paid')
            ->where('payment_status', '!=', 'paid')
            ->where('due_date', '<', $now)
            ->get()
            ->sum('balance_due');
        $overdueCount = PurchaseInvoice::where('status', '!=', 'paid')
            ->where('payment_status', '!=', 'paid')
            ->where('due_date', '<', $now)
            ->count();

        return [
            Stat::make('PEMBELIAN', 'Rp ' . number_format($purchaseThisPeriod, 0, ',', '.'))
                ->description($purchaseCount . ' Tagihan ' . $periodLabel)
                ->descriptionIcon($purchaseIcon)
                ->color('info')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('PEMBAYARAN TERKIRIM', 'Rp ' . number_format($paymentSent, 0, ',', '.'))
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
