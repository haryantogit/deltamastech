<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class ArusKasStatsWidget extends BaseWidget
{
    use \Filament\Widgets\Concerns\InteractsWithPageFilters;
    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    public string $statsFilter = 'bulan';

    #[On('updateStatsFilter')]
    public function updateStatsFilter(string $filter): void
    {
        $this->statsFilter = $filter;
    }

    protected function getStats(): array
    {
        $endDate = $this->filters['endDate'] ?? now()->toDateString();
        $startDate = $this->filters['startDate'] ?? now()->startOfMonth()->toDateString();

        $end = Carbon::parse($endDate);
        $start = Carbon::parse($startDate);

        $daysCount = $start->diffInDays($end) + 1;
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($daysCount - 1);

        // Get Kas & Bank account IDs
        $kasBankIds = Account::where('category', 'Kas & Bank')->pluck('id')->toArray();

        if (empty($kasBankIds)) {
            return $this->emptyStats();
        }

        // Current period cash flows
        $kasInCurrent = (float) JournalItem::whereIn('account_id', $kasBankIds)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
            ->sum('debit');

        $kasOutCurrent = (float) JournalItem::whereIn('account_id', $kasBankIds)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
            ->sum('credit');

        $perubahanKas = $kasInCurrent - $kasOutCurrent;

        // Closing balance = all-time net
        $saldoPenutupan = (float) JournalItem::whereIn('account_id', $kasBankIds)
            ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<=', $end))
            ->sum(DB::raw('debit - credit'));

        // Previous period cash flows
        $kasInPrev = (float) JournalItem::whereIn('account_id', $kasBankIds)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$prevStart, $prevEnd]))
            ->sum('debit');

        $kasOutPrev = (float) JournalItem::whereIn('account_id', $kasBankIds)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$prevStart, $prevEnd]))
            ->sum('credit');

        $prevPerubahan = $kasInPrev - $kasOutPrev;
        $prevSaldo = (float) JournalItem::whereIn('account_id', $kasBankIds)
            ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<=', $prevEnd))
            ->sum(DB::raw('debit - credit'));

        // Trend helper
        $trend = function ($current, $previous) {
            if ($previous == 0) {
                $pct = $current != 0 ? 100 : 0;
            } else {
                $pct = round((($current - $previous) / abs($previous)) * 100, 1);
            }
            $label = 'vs periode sebelumnya';
            return [
                'pct' => $pct,
                'icon' => $pct >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down',
                'color' => $pct >= 0 ? 'success' : 'danger',
                'desc' => number_format(abs($pct), 1, ',', '.') . '% ' . $label,
            ];
        };

        $fmt = fn($v) => number_format($v, 0, ',', '.');

        $perubahanTrend = $trend($perubahanKas, $prevPerubahan);
        $saldoTrend = $trend($saldoPenutupan, $prevSaldo);
        $kasKeluarTrend = $trend($kasOutCurrent, $kasOutPrev);
        // Kas keluar: higher = worse, invert color
        $kasKeluarTrend['color'] = $kasKeluarTrend['pct'] <= 0 ? 'success' : 'danger';
        $kasMasukTrend = $trend($kasInCurrent, $kasInPrev);

        return [
            Stat::make('Perubahan Kas', $fmt($perubahanKas))
                ->description($perubahanTrend['desc'])
                ->descriptionIcon($perubahanTrend['icon'])
                ->color($perubahanTrend['color'])
                ->chart([7, 4, 6, 8, 5, 3, 4]),

            Stat::make('Saldo Penutupan', $fmt($saldoPenutupan))
                ->description($saldoTrend['desc'])
                ->descriptionIcon($saldoTrend['icon'])
                ->color($saldoTrend['color'])
                ->chart([8, 6, 7, 5, 6, 4, 5]),

            Stat::make('Kas Keluar', $fmt($kasOutCurrent))
                ->description($kasKeluarTrend['desc'])
                ->descriptionIcon($kasKeluarTrend['icon'])
                ->color($kasKeluarTrend['color'])
                ->chart([5, 7, 9, 8, 10, 11, 9]),

            Stat::make('Kas Masuk', $fmt($kasInCurrent))
                ->description($kasMasukTrend['desc'])
                ->descriptionIcon($kasMasukTrend['icon'])
                ->color($kasMasukTrend['color'])
                ->chart([4, 5, 6, 7, 8, 9, 10]),
        ];
    }

    private function emptyStats(): array
    {
        $label = 'vs periode sebelumnya';
        return [
            Stat::make('Perubahan Kas', '0')
                ->description('0,0% ' . $label)
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('gray')
                ->chart([0, 0, 0, 0, 0, 0, 0]),
            Stat::make('Saldo Penutupan', '0')
                ->description('0,0% ' . $label)
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('gray')
                ->chart([0, 0, 0, 0, 0, 0, 0]),
            Stat::make('Kas Keluar', '0')
                ->description('0,0% ' . $label)
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('gray')
                ->chart([0, 0, 0, 0, 0, 0, 0]),
            Stat::make('Kas Masuk', '0')
                ->description('0,0% ' . $label)
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('gray')
                ->chart([0, 0, 0, 0, 0, 0, 0]),
        ];
    }
}
