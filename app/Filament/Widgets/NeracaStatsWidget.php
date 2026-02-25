<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class NeracaStatsWidget extends BaseWidget
{
    use \Filament\Widgets\Concerns\InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $endDate = $this->filters['endDate'] ?? now()->toDateString();
        $end = Carbon::parse($endDate);

        // Previous period for comparison (30 days before)
        $prevEnd = $end->copy()->subDays(30);

        // Get account IDs by category
        $asetLancarCats = ['Kas & Bank', 'Akun Piutang', 'Persediaan', 'Aktiva Lancar Lainnya'];
        $kasBankCat = ['Kas & Bank'];
        $piutangCat = ['Akun Piutang'];
        $liabilitasPendekCats = ['Akun Hutang', 'Kewajiban Lancar Lainnya'];
        $ekuitasCats = ['Ekuitas'];

        $getBalance = function (array $categories, Carbon $asOf) {
            $accountIds = Account::whereIn('category', $categories)->pluck('id')->toArray();
            if (empty($accountIds))
                return 0;

            return (float) JournalItem::whereIn('account_id', $accountIds)
                ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<=', $asOf))
                ->sum(DB::raw('debit - credit'));
        };

        // Current balances
        $kasBank = $getBalance($kasBankCat, $end);
        $piutang = $getBalance($piutangCat, $end);
        $asetLancar = $getBalance($asetLancarCats, $end);
        $liabilitasPendek = abs($getBalance($liabilitasPendekCats, $end));
        $ekuitas = abs($getBalance($ekuitasCats, $end));
        $totalAset = $asetLancar + $getBalance(['Aktiva Tetap'], $end) + $getBalance(['Depresiasi & Amortisasi'], $end);

        // Previous balances
        $prevKasBank = $getBalance($kasBankCat, $prevEnd);
        $prevPiutang = $getBalance($piutangCat, $prevEnd);
        $prevAsetLancar = $getBalance($asetLancarCats, $prevEnd);
        $prevLiabilitasPendek = abs($getBalance($liabilitasPendekCats, $prevEnd));
        $prevEkuitas = abs($getBalance($ekuitasCats, $prevEnd));
        $prevTotalAset = $prevAsetLancar + $getBalance(['Aktiva Tetap'], $prevEnd) + $getBalance(['Depresiasi & Amortisasi'], $prevEnd);

        // Calculate ratios
        $safeDivide = fn($a, $b) => $b != 0 ? round($a / $b, 2) : 0;

        $quickRatio = $safeDivide($kasBank + $piutang, $liabilitasPendek);
        $currentRatio = $safeDivide($asetLancar, $liabilitasPendek);
        $debtEquityRatio = $safeDivide($liabilitasPendek, $ekuitas);
        $equityRatio = $safeDivide($ekuitas, abs($totalAset));

        $prevQuickRatio = $safeDivide($prevKasBank + $prevPiutang, $prevLiabilitasPendek);
        $prevCurrentRatio = $safeDivide($prevAsetLancar, $prevLiabilitasPendek);
        $prevDebtEquityRatio = $safeDivide($prevLiabilitasPendek, $prevEkuitas);
        $prevEquityRatio = $safeDivide($prevEkuitas, abs($prevTotalAset));

        $trend = function ($current, $previous) {
            if ($previous == 0) {
                $pct = $current > 0 ? 100 : 0;
            } else {
                $pct = round((($current - $previous) / abs($previous)) * 100, 1);
            }
            return [
                'pct' => $pct,
                'icon' => $pct >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down',
                'color' => $pct >= 0 ? 'success' : 'danger',
                'desc' => number_format(abs($pct), 1, ',', '.') . '% vs bulan lalu',
            ];
        };

        $qrTrend = $trend($quickRatio, $prevQuickRatio);
        $crTrend = $trend($currentRatio, $prevCurrentRatio);
        $derTrend = $trend($debtEquityRatio, $prevDebtEquityRatio);
        // For DER, lower is better, so invert color
        $derTrend['color'] = $derTrend['pct'] <= 0 ? 'success' : 'danger';
        $erTrend = $trend($equityRatio, $prevEquityRatio);

        return [
            Stat::make('Quick Ratio', number_format($quickRatio, 1, ',', '.'))
                ->description($qrTrend['desc'])
                ->descriptionIcon($qrTrend['icon'])
                ->color($qrTrend['color'])
                ->chart([7, 4, 6, 8, 5, 9, 7]),

            Stat::make('Current Ratio', number_format($currentRatio, 1, ',', '.'))
                ->description($crTrend['desc'])
                ->descriptionIcon($crTrend['icon'])
                ->color($crTrend['color'])
                ->chart([3, 5, 7, 6, 9, 11, 10]),

            Stat::make('Debt Equity Ratio', number_format($debtEquityRatio, 1, ',', '.'))
                ->description($derTrend['desc'])
                ->descriptionIcon($derTrend['icon'])
                ->color($derTrend['color'])
                ->chart([10, 8, 9, 7, 6, 5, 4]),

            Stat::make('Equity Ratio', number_format($equityRatio, 2, ',', '.'))
                ->description($erTrend['desc'])
                ->descriptionIcon($erTrend['icon'])
                ->color($erTrend['color'])
                ->chart([4, 5, 6, 7, 8, 9, 10]),
        ];
    }
}
