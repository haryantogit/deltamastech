<?php

namespace App\Filament\Widgets;

use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AccountStats extends BaseWidget
{
    use \Filament\Widgets\Concerns\InteractsWithPageFilters;

    public $account = null;
    // public $filters = []; // Removed so trait property is used

    protected function getStats(): array
    {
        if (!$this->account) {
            return [];
        }

        $filters = $this->filters ?? request()->input('filters') ?? [];
        $startDate = $filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $filters['endDate'] ?? now()->endOfYear()->toDateString();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $sameDayLastMonth = $now->copy()->subMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();

        $today = $now->copy()->startOfDay();
        $thirtyDaysAgo = $now->copy()->subDays(30)->startOfDay();

        $baseQuery = JournalItem::where('account_id', $this->account->id);

        // 1. SALDO (Always total cumulative up to endDate)
        $currentBalance = (clone $baseQuery)
            ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<=', $end))
            ->sum(DB::raw('debit - credit'));

        $balance30DaysAgo = (clone $baseQuery)
            ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<', $end->copy()->subDays(30)))
            ->sum(DB::raw('debit - credit'));

        $balanceTrend = $this->calculateTrend($currentBalance, $balance30DaysAgo);

        // 2. MASUK (In the selected range)
        $masukBulanIniQuery = (clone $baseQuery)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]));

        $masukBulanIni = $masukBulanIniQuery->sum('debit');

        // Comparison (Previous equivalent period)
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
            Stat::make('Saldo Akhir', 'Rp ' . number_format($currentBalance, 0, ',', '.'))
                ->description('Per tanggal ' . $end->format('d/m/Y'))
                ->descriptionIcon($balanceTrend['icon'])
                ->color($balanceTrend['color'])
                ->chart([7, 4, 6, 8, 10, 12, 15]),
            Stat::make('Masuk (Range)', 'Rp ' . number_format($masukBulanIni, 0, ',', '.'))
                ->description('Total penerimaan periode ini')
                ->descriptionIcon($masukTrend['icon'])
                ->color($masukTrend['color'])
                ->chart([3, 5, 7, 9, 11, 13, 15]),
            Stat::make('Keluar (Range)', 'Rp ' . number_format($keluarBulanIni, 0, ',', '.'))
                ->description('Total pengeluaran periode ini')
                ->descriptionIcon($keluarTrend['icon'])
                ->color($keluarTrend['color'])
                ->chart([15, 13, 11, 9, 7, 5, 3]),
            Stat::make('Net (Range)', 'Rp ' . number_format($netBulanIni, 0, ',', '.'))
                ->description('Selisih masuk & keluar')
                ->descriptionIcon($netTrend['icon'])
                ->color($netTrend['color'])
                ->chart([2, 4, 3, 6, 5, 8, 7]),
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
