<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Account;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class LabaRugi extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.laporan.laba-rugi';

    protected static ?string $title = 'Laba Rugi';

    protected static ?string $slug = 'laba-rugi';

    protected static bool $shouldRegisterNavigation = false;

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Laba Rugi',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\LabaRugiStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('panduan')
                ->label('Panduan')
                ->color('gray')
                ->icon('heroicon-o-question-mark-circle')
                ->url('#'),
            Action::make('ekspor')
                ->label('Ekspor')
                ->color('gray')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url('#'),
            Action::make('bagikan')
                ->label('Bagikan')
                ->color('gray')
                ->icon('heroicon-o-share')
                ->url('#'),
            Action::make('print')
                ->label('Print')
                ->color('gray')
                ->icon('heroicon-o-printer')
                ->url('#'),
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(\App\Filament\Pages\ReportPage::getUrl()),
        ];
    }

    public function getViewData(): array
    {
        $end = Carbon::now();
        $start = $end->copy()->startOfMonth();
        $today = $end->format('d/m/Y');

        // Previous period for comparison
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->startOfMonth();

        // Build 6 months of start/end pairs for sparkline bars
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $ms = $end->copy()->subMonths($i)->startOfMonth();
            $me = ($i === 0) ? $end->copy() : $ms->copy()->endOfMonth();
            $months[] = [$ms, $me];
        }

        // Helper to get balance for an account in a date range
        $getBalanceForAccount = function (int $accountId, string $expr, Carbon $from, Carbon $to) {
            return (float) JournalItem::where('account_id', $accountId)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$from, $to]))
                ->sum(DB::raw($expr));
        };

        $getAccountRows = function (array $categories, Carbon $periodStart, Carbon $periodEnd, Carbon $prevStart, Carbon $prevEnd, array $months) use ($getBalanceForAccount) {
            $accounts = Account::whereIn('category', $categories)
                ->whereNull('parent_id')
                ->with('children')
                ->orderBy('code')
                ->get();

            $sections = [];
            $grandTotal = 0;
            $prevGrandTotal = 0;

            foreach ($categories as $cat) {
                $catAccounts = $accounts->where('category', $cat)->values();
                $rows = [];
                $catTotal = 0;
                $prevCatTotal = 0;

                foreach ($catAccounts as $account) {
                    $isRevenue = in_array($cat, ['Pendapatan', 'Pendapatan Lainnya']);
                    $expr = $isRevenue ? 'credit - debit' : 'debit - credit';

                    $balance = $getBalanceForAccount($account->id, $expr, $periodStart, $periodEnd);
                    $prevBalance = $getBalanceForAccount($account->id, $expr, $prevStart, $prevEnd);

                    // 6-month sparkline data
                    $sparkline = [];
                    foreach ($months as [$ms, $me]) {
                        $sparkline[] = $getBalanceForAccount($account->id, $expr, $ms, $me);
                    }

                    $catTotal += $balance;
                    $prevCatTotal += $prevBalance;

                    $children = [];
                    foreach ($account->children->sortBy('code') as $child) {
                        $childBalance = $getBalanceForAccount($child->id, $expr, $periodStart, $periodEnd);
                        $childPrev = $getBalanceForAccount($child->id, $expr, $prevStart, $prevEnd);

                        $childSpark = [];
                        foreach ($months as [$ms, $me]) {
                            $childSpark[] = $getBalanceForAccount($child->id, $expr, $ms, $me);
                        }

                        $children[] = [
                            'code' => $child->code,
                            'name' => $child->name,
                            'balance' => $childBalance,
                            'prev' => $childPrev,
                            'sparkline' => $childSpark,
                        ];
                    }

                    $rows[] = [
                        'code' => $account->code,
                        'name' => $account->name,
                        'balance' => $balance,
                        'prev' => $prevBalance,
                        'sparkline' => $sparkline,
                        'children' => $children,
                    ];
                }

                $grandTotal += $catTotal;
                $prevGrandTotal += $prevCatTotal;

                // Section-level sparkline
                $sectionSpark = [];
                foreach ($months as $idx => [$ms, $me]) {
                    $colSum = 0;
                    foreach ($rows as $r) {
                        $colSum += $r['sparkline'][$idx];
                    }
                    $sectionSpark[] = $colSum;
                }

                $sections[] = [
                    'label' => $cat,
                    'rows' => $rows,
                    'total' => $catTotal,
                    'prevTotal' => $prevCatTotal,
                    'sparkline' => $sectionSpark,
                ];
            }

            return ['sections' => $sections, 'total' => $grandTotal, 'prevTotal' => $prevGrandTotal];
        };

        // Current + previous period
        $pendapatan = $getAccountRows(['Pendapatan'], $start, $end, $prevStart, $prevEnd, $months);
        $pendapatanLain = $getAccountRows(['Pendapatan Lainnya'], $start, $end, $prevStart, $prevEnd, $months);
        $hpp = $getAccountRows(['Harga Pokok Penjualan'], $start, $end, $prevStart, $prevEnd, $months);
        $beban = $getAccountRows(['Beban'], $start, $end, $prevStart, $prevEnd, $months);
        $bebanLain = $getAccountRows(['Beban Lainnya'], $start, $end, $prevStart, $prevEnd, $months);

        $totalPendapatan = $pendapatan['total'] + $pendapatanLain['total'];
        $totalHPP = $hpp['total'];
        $labaKotor = $totalPendapatan - $totalHPP;
        $totalBeban = $beban['total'] + $bebanLain['total'];
        $labaBersih = $labaKotor - $totalBeban;

        $prevTotalPendapatan = $pendapatan['prevTotal'] + $pendapatanLain['prevTotal'];
        $prevLabaKotor = $prevTotalPendapatan - $hpp['prevTotal'];
        $prevTotalBeban = $beban['prevTotal'] + $bebanLain['prevTotal'];
        $prevLabaBersih = $prevLabaKotor - $prevTotalBeban;

        $pctChange = function ($current, $previous) {
            if ($previous == 0)
                return $current != 0 ? 100 : 0;
            return round((($current - $previous) / abs($previous)) * 100, 1);
        };

        return [
            'today' => $today,
            'startDate' => $start->format('d/m/Y'),
            'endDate' => $end->format('d/m/Y'),
            'pendapatan' => $pendapatan,
            'pendapatanLain' => $pendapatanLain,
            'hpp' => $hpp,
            'beban' => $beban,
            'bebanLain' => $bebanLain,
            'totalPendapatan' => $totalPendapatan,
            'totalHPP' => $totalHPP,
            'labaKotor' => $labaKotor,
            'totalBeban' => $totalBeban,
            'labaBersih' => $labaBersih,
            'prevTotalPendapatan' => $prevTotalPendapatan,
            'prevLabaKotor' => $prevLabaKotor,
            'prevTotalBeban' => $prevTotalBeban,
            'prevLabaBersih' => $prevLabaBersih,
            'pctPendapatan' => $pctChange($totalPendapatan, $prevTotalPendapatan),
            'pctLabaKotor' => $pctChange($labaKotor, $prevLabaKotor),
            'pctBeban' => $pctChange($totalBeban, $prevTotalBeban),
            'pctLabaBersih' => $pctChange($labaBersih, $prevLabaBersih),
        ];
    }
}
