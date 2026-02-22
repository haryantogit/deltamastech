<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class CompanyCashStats extends BaseWidget
{
    use \Filament\Widgets\Concerns\InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $startDate = $this->filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $this->filters['endDate'] ?? now()->toDateString();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30)->startOfDay();

        $cashAccountIds = Account::where('category', 'Kas & Bank')->pluck('id')->toArray();

        if (empty($cashAccountIds)) {
            return [];
        }

        $baseQuery = JournalItem::whereIn('account_id', $cashAccountIds);

        // 1. SALDO (Cumulative up to endDate)
        $currentBalance = (clone $baseQuery)
            ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<=', $end))
            ->sum(DB::raw('debit - credit'));

        $balance30DaysAgo = (clone $baseQuery)
            ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<', $end->copy()->subDays(30)))
            ->sum(DB::raw('debit - credit'));

        $balanceTrend = $this->calculateTrend($currentBalance, $balance30DaysAgo);

        // 2. MASUK (Within range)
        $masukBulanIniQuery = (clone $baseQuery)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]));

        $masukBulanIni = $masukBulanIniQuery->sum('debit');

        // Comparison
        $diff = $start->diffInDays($end) + 1;
        $prevStart = $start->copy()->subDays($diff);
        $prevEnd = $start->copy()->subDay();

        $masukBulanLalu = (clone $baseQuery)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$prevStart, $prevEnd]))
            ->sum('debit');

        $masukTrend = $this->calculateTrend($masukBulanIni, $masukBulanLalu);

        // 3. KELUAR
        $keluarBulanIniQuery = (clone $baseQuery)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]));

        $keluarBulanIni = $keluarBulanIniQuery->sum('credit');

        $keluarBulanLalu = (clone $baseQuery)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$prevStart, $prevEnd]))
            ->sum('credit');

        $keluarTrend = $this->calculateTrend($keluarBulanIni, $keluarBulanLalu);

        // 4. NET
        $netBulanIni = $masukBulanIni - $keluarBulanIni;
        $netBulanLalu = $masukBulanLalu - $keluarBulanLalu;
        $netTrend = $this->calculateTrend($netBulanIni, $netBulanLalu);

        return [
            Stat::make('Saldo Saat Ini', 'Rp ' . number_format($currentBalance, 0, ',', '.'))
                ->description('Hari ini vs 30 hari lalu')
                ->descriptionIcon($balanceTrend['icon'])
                ->color($balanceTrend['color'])
                ->chart([7, 4, 6, 8, 10, 12, 11, 13, 15]),
            Stat::make('Masuk Bulan Ini', 'Rp ' . number_format($masukBulanIni, 0, ',', '.'))
                ->description('Total penerimaan bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([3, 5, 7, 6, 9, 11, 10, 13, 15]),
            Stat::make('Keluar Bulan Ini', 'Rp ' . number_format($keluarBulanIni, 0, ',', '.'))
                ->description('Total pengeluaran bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart([15, 13, 14, 11, 9, 10, 7, 5, 3]),
            Stat::make('Net Bulan Ini', 'Rp ' . number_format($netBulanIni, 0, ',', '.'))
                ->description('Selisih masuk & keluar')
                ->descriptionIcon($netTrend['icon'])
                ->color($netTrend['color'])
                ->chart([2, 4, 3, 6, 5, 8, 7, 9, 10]),
        ];
    }

    protected function calculateTrend($current, $previous): array
    {
        if ($previous == 0) {
            $percentage = $current > 0 ? 100 : 0;
        } else {
            $percentage = round((($current - $previous) / abs($previous)) * 100, 1);
        }

        $icon = $percentage >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $color = $percentage >= 0 ? 'success' : 'danger';

        return [
            'percentage' => number_format(abs($percentage), 1, ',', '.'),
            'icon' => $icon,
            'color' => $color,
        ];
    }
}
