<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PerubahanModalStatsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $end = Carbon::now();
        $start = $end->copy()->startOfMonth();
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->startOfMonth();

        $ekuitasIds = Account::where('category', 'Ekuitas')->pluck('id')->toArray();

        if (empty($ekuitasIds)) {
            return $this->emptyStats();
        }

        // Current period
        $saldoModal = (float) JournalItem::whereIn('account_id', $ekuitasIds)
            ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<=', $end))
            ->sum(DB::raw('credit - debit'));

        $penambahan = (float) JournalItem::whereIn('account_id', $ekuitasIds)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
            ->sum('credit');

        $pengurangan = (float) JournalItem::whereIn('account_id', $ekuitasIds)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
            ->sum('debit');

        $perubahanModal = $penambahan - $pengurangan;

        // Previous period
        $prevSaldo = (float) JournalItem::whereIn('account_id', $ekuitasIds)
            ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<=', $prevEnd))
            ->sum(DB::raw('credit - debit'));

        $prevPenambahan = (float) JournalItem::whereIn('account_id', $ekuitasIds)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$prevStart, $prevEnd]))
            ->sum('credit');

        $prevPengurangan = (float) JournalItem::whereIn('account_id', $ekuitasIds)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$prevStart, $prevEnd]))
            ->sum('debit');

        $prevPerubahan = $prevPenambahan - $prevPengurangan;

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

        $pmTrend = $trend($perubahanModal, $prevPerubahan);
        $smTrend = $trend($saldoModal, $prevSaldo);
        $pnTrend = $trend($penambahan, $prevPenambahan);
        $pgTrend = $trend($pengurangan, $prevPengurangan);
        // For pengurangan, lower is better
        $pgTrend['color'] = $pgTrend['color'] === 'success' ? 'danger' : 'success';

        return [
            Stat::make('Perubahan Modal', $fmt($perubahanModal))
                ->description($pmTrend['desc'])
                ->descriptionIcon($pmTrend['icon'])
                ->color($pmTrend['color'])
                ->chart([4, 5, 6, 3, 7, 5, 6]),

            Stat::make('Saldo Modal', $fmt($saldoModal))
                ->description($smTrend['desc'])
                ->descriptionIcon($smTrend['icon'])
                ->color($smTrend['color'])
                ->chart([6, 7, 8, 7, 9, 8, 10]),

            Stat::make('Penambahan Modal', $fmt($penambahan))
                ->description($pnTrend['desc'])
                ->descriptionIcon($pnTrend['icon'])
                ->color($pnTrend['color'])
                ->chart([3, 5, 4, 6, 5, 7, 6]),

            Stat::make('Pengurangan Modal', $fmt($pengurangan))
                ->description($pgTrend['desc'])
                ->descriptionIcon($pgTrend['icon'])
                ->color($pgTrend['color'])
                ->chart([7, 6, 8, 5, 9, 7, 8]),
        ];
    }

    private function emptyStats(): array
    {
        $empty = fn($label) => Stat::make($label, '0')
            ->description('0,0% vs bulan lalu')
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->color('gray')
            ->chart([0, 0, 0, 0, 0, 0, 0]);

        return [
            $empty('Perubahan Modal'),
            $empty('Saldo Modal'),
            $empty('Penambahan Modal'),
            $empty('Pengurangan Modal'),
        ];
    }
}
