<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

use Livewire\Attributes\On;

class RingkasanBankStatsWidget extends BaseWidget
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
        $activeFilter = $this->statsFilter;

        $start = Carbon::parse($this->filters['startDate'] ?? now()->startOfMonth());
        $end = Carbon::parse($this->filters['endDate'] ?? now());

        $daysCount = $start->diffInDays($end) + 1;
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($daysCount - 1);

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

        $label = 'vs periode sebelumnya';

        return [
            Stat::make('Saldo', $fmt($saldo))
                ->description($label)
                ->descriptionIcon($trend($saldo, $prevSaldo))
                ->color($trendColor($saldo, $prevSaldo))
                ->chart([4, 5, 6, 3, 7, 5, 6]),

            Stat::make('Net', $fmt($net))
                ->description($label)
                ->descriptionIcon($trend($net, $prevNet))
                ->color($trendColor($net, $prevNet))
                ->chart([3, 5, 4, 6, 5, 7, 6]),

            Stat::make('Masuk', $fmt($masuk))
                ->description($label)
                ->descriptionIcon($trend($masuk, $prevMasuk))
                ->color('success')
                ->chart([5, 6, 7, 8, 7, 6, 5]),

            Stat::make('Keluar', $fmt($keluar))
                ->description($label)
                ->descriptionIcon($trend($keluar, $prevKeluar))
                ->color('danger')
                ->chart([6, 7, 8, 7, 9, 8, 10]),
        ];
    }
}
