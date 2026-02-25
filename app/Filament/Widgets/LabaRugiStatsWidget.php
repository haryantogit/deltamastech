<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class LabaRugiStatsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $end = Carbon::now();
        $start = $end->copy()->startOfMonth();
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->startOfMonth();

        $getSum = function (array $categories, Carbon $periodStart, Carbon $periodEnd, bool $isRevenue = true) {
            $accountIds = Account::whereIn('category', $categories)->pluck('id')->toArray();
            if (empty($accountIds))
                return 0;
            $expr = $isRevenue ? 'credit - debit' : 'debit - credit';
            return (float) JournalItem::whereIn('account_id', $accountIds)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$periodStart, $periodEnd]))
                ->sum(DB::raw($expr));
        };

        // Current period
        $revenue = $getSum(['Pendapatan', 'Pendapatan Lainnya'], $start, $end, true);
        $cogs = $getSum(['Harga Pokok Penjualan'], $start, $end, false);
        $expenses = $getSum(['Beban', 'Beban Lainnya'], $start, $end, false);
        $grossProfit = $revenue - $cogs;
        $netProfit = $grossProfit - $expenses;
        $grossMargin = $revenue != 0 ? round(($grossProfit / $revenue) * 100, 1) : 0;
        $expenseRatio = $revenue != 0 ? round(($expenses / $revenue) * 100, 1) : 0;
        $netMargin = $revenue != 0 ? round(($netProfit / $revenue) * 100, 1) : 0;

        // Previous period
        $prevRevenue = $getSum(['Pendapatan', 'Pendapatan Lainnya'], $prevStart, $prevEnd, true);
        $prevCogs = $getSum(['Harga Pokok Penjualan'], $prevStart, $prevEnd, false);
        $prevExpenses = $getSum(['Beban', 'Beban Lainnya'], $prevStart, $prevEnd, false);
        $prevGross = $prevRevenue - $prevCogs;
        $prevNet = $prevGross - $prevExpenses;

        $trend = function ($current, $previous) {
            if ($previous == 0) {
                $pct = $current != 0 ? 100 : 0;
            } else {
                $pct = round((($current - $previous) / abs($previous)) * 100, 1);
            }
            return [
                'icon' => $pct >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down',
                'color' => $pct >= 0 ? 'success' : 'danger',
                'desc' => number_format(abs($pct), 1, ',', '.') . '% vs bulan lalu',
            ];
        };

        $fmt = fn($v) => number_format($v, 0, ',', '.');

        $revTrend = $trend($revenue, $prevRevenue);
        $grossTrend = $trend($grossProfit, $prevGross);
        $expTrend = $trend($expenses, $prevExpenses);
        // For expenses, lower is better
        $expTrend['color'] = $expTrend['color'] === 'success' ? 'danger' : 'success';
        $netTrend = $trend($netProfit, $prevNet);

        return [
            Stat::make('Pendapatan', $fmt($revenue))
                ->description($revTrend['desc'])
                ->descriptionIcon($revTrend['icon'])
                ->color($revTrend['color'])
                ->chart([4, 6, 8, 5, 7, 9, 10]),

            Stat::make('Margin Laba Bersih', $netMargin . '%')
                ->description($netTrend['desc'])
                ->descriptionIcon($netTrend['icon'])
                ->color($netTrend['color'])
                ->chart([3, 5, 4, 7, 6, 8, 7]),

            Stat::make('Laba Kotor', $fmt($grossProfit))
                ->description($grossTrend['desc'])
                ->descriptionIcon($grossTrend['icon'])
                ->color($grossTrend['color'])
                ->chart([6, 5, 7, 8, 6, 9, 8]),

            Stat::make('Laba Bersih', $fmt($netProfit))
                ->description($netTrend['desc'])
                ->descriptionIcon($netTrend['icon'])
                ->color($netTrend['color'])
                ->chart([5, 4, 6, 7, 5, 8, 9]),

            Stat::make('Rasio Laba Kotor', $grossMargin . '%')
                ->description($grossTrend['desc'])
                ->descriptionIcon($grossTrend['icon'])
                ->color($grossTrend['color'])
                ->chart([7, 8, 6, 9, 7, 10, 8]),

            Stat::make('Biaya Operasional', $fmt($expenses))
                ->description($expTrend['desc'])
                ->descriptionIcon($expTrend['icon'])
                ->color($expTrend['color'])
                ->chart([8, 9, 7, 10, 8, 11, 9]),

            Stat::make('Rasio Biaya Operasional', $expenseRatio . '%')
                ->description($expTrend['desc'])
                ->descriptionIcon($expTrend['icon'])
                ->color($expTrend['color'])
                ->chart([9, 8, 10, 7, 9, 6, 8]),

            Stat::make('Beban Pokok Penjualan', $fmt($cogs))
                ->description($trend($cogs, $prevCogs)['desc'])
                ->descriptionIcon($trend($cogs, $prevCogs)['icon'])
                ->color($trend($cogs, $prevCogs)['color'])
                ->chart([5, 7, 6, 8, 7, 9, 8]),
        ];
    }
}
