<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class RingkasanBankStatsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now();
        $prevStart = Carbon::now()->subMonth()->startOfMonth();
        $prevEnd = Carbon::now()->subMonth()->endOfMonth();

        $bankIds = Account::where('category', 'Kas & Bank')
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        if (empty($bankIds)) {
            return [
                Stat::make('Saldo', '0')->color('info'),
                Stat::make('Net', '0')->color('info'),
                Stat::make('Masuk', '0')->color('success'),
                Stat::make('Keluar', '0')->color('danger'),
            ];
        }

        // Current period
        $saldoAwal = (float) JournalItem::whereIn('account_id', $bankIds)
            ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<', $start))
            ->sum(DB::raw('debit - credit'));

        $masuk = (float) JournalItem::whereIn('account_id', $bankIds)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
            ->sum('debit');

        $keluar = (float) JournalItem::whereIn('account_id', $bankIds)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
            ->sum('credit');

        $saldo = $saldoAwal + $masuk - $keluar;
        $net = $masuk - $keluar;

        // Previous period for comparison
        $prevMasuk = (float) JournalItem::whereIn('account_id', $bankIds)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$prevStart, $prevEnd]))
            ->sum('debit');

        $prevKeluar = (float) JournalItem::whereIn('account_id', $bankIds)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$prevStart, $prevEnd]))
            ->sum('credit');

        $prevSaldoAwal = (float) JournalItem::whereIn('account_id', $bankIds)
            ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<', $prevStart))
            ->sum(DB::raw('debit - credit'));

        $prevSaldo = $prevSaldoAwal + $prevMasuk - $prevKeluar;
        $prevNet = $prevMasuk - $prevKeluar;

        $fmt = fn($v) => number_format($v, 0, ',', '.');

        $pct = function ($curr, $prev) {
            if ($prev == 0)
                return $curr != 0 ? '100%' : '0%';
            return round(($curr - $prev) / abs($prev) * 100, 1) . '%';
        };

        $trend = fn($curr, $prev) => $curr >= $prev ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $trendColor = fn($curr, $prev) => $curr >= $prev ? 'success' : 'danger';

        return [
            Stat::make('Saldo', $fmt($saldo))
                ->description('Bulan ini')
                ->descriptionIcon($trend($saldo, $prevSaldo))
                ->color($trendColor($saldo, $prevSaldo))
                ->chart([4, 5, 6, 3, 7, 5, 6]),

            Stat::make('Net', $fmt($net))
                ->description('Bulan ini')
                ->descriptionIcon($trend($net, $prevNet))
                ->color($trendColor($net, $prevNet))
                ->chart([3, 5, 4, 6, 5, 7, 6]),

            Stat::make('Masuk', $fmt($masuk))
                ->description('Bulan ini')
                ->descriptionIcon($trend($masuk, $prevMasuk))
                ->color('success')
                ->chart([5, 6, 7, 8, 7, 6, 5]),

            Stat::make('Keluar', $fmt($keluar))
                ->description('Bulan ini')
                ->descriptionIcon($trend($keluar, $prevKeluar))
                ->color('danger')
                ->chart([6, 7, 8, 7, 9, 8, 10]),
        ];
    }
}
