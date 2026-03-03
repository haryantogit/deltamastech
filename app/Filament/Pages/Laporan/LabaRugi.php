<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Account;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\EmbeddedSchema;

class LabaRugi extends Page
{
    use HasFiltersForm;
    public string $statsFilter = 'bulan';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.laporan.laba-rugi';

    protected static ?string $title = 'Laba Rugi';

    protected static ?string $slug = 'laba-rugi';

    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        $this->filters = [
            'period_type' => 'Quarter',
            'compare_periods' => 0,
            'endDate' => now()->toDateString(),
        ];
    }

    public function filtersForm(Schema $form): Schema
    {
        return $form
            ->schema([
                //
            ])
            ->columns(1)
            ->statePath('filters');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Laba Rugi',
        ];
    }

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $endDate = $this->filters['endDate'] ?? now()->toDateString();
        $endFmt = \Carbon\Carbon::parse($endDate)->format('d/m/Y');

        return new \Illuminate\Support\HtmlString('
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background-color: #f8fafc; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; font-size: 0.875rem; font-weight: 600; color: #475569;" class="dark:bg-white/5 dark:border-white/10 dark:text-gray-300">
                <svg style="width: 1.25rem; height: 1.25rem; opacity: 0.7;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>' . $endFmt . '</span>
            </div>
        ');
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
            Action::make('filter')
                ->label('Filter')
                ->icon('heroicon-m-funnel')
                ->color('gray')
                ->form([
                    Select::make('period_type')
                        ->label('Tipe Periode')
                        ->options([
                            'Custom' => 'Kustom Berdasarkan Tanggal',
                            'Month' => 'Bulanan',
                            'Quarter' => 'Kuartal',
                            'Year' => 'Tahunan',
                        ])
                        ->default($this->filters['period_type'] ?? 'Quarter')
                        ->required(),
                    Select::make('compare_periods')
                        ->label('Bandingkan Periode')
                        ->options([
                            0 => 'Tidak Ada',
                            1 => '1 Periode',
                            2 => '2 Periode',
                            3 => '3 Periode',
                            4 => '4 Periode',
                            5 => '5 Periode',
                            6 => '6 Periode',
                        ])
                        ->default($this->filters['compare_periods'] ?? 0)
                        ->required(),
                    DatePicker::make('endDate')
                        ->label('Sampai Tanggal')
                        ->default($this->filters['endDate'] ?? now()->toDateString())
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->filters['period_type'] = $data['period_type'];
                    $this->filters['compare_periods'] = $data['compare_periods'];
                    $this->filters['endDate'] = $data['endDate'];
                    $this->statsFilter = 'custom';
                }),
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
        $periodType = $this->filters['period_type'] ?? 'Quarter';
        $comparePeriods = (int) ($this->filters['compare_periods'] ?? 3);
        $endDate = $this->filters['endDate'] ?? now()->toDateString();

        $periods = [];
        $end = Carbon::parse($endDate);

        if ($periodType === 'Custom') {
            $periods[] = [
                'label' => $end->copy()->startOfDay()->isSameDay($end->copy()->endOfDay())
                    ? $end->format('d/m/Y')
                    : $end->copy()->startOfMonth()->format('d/m/Y') . ' - ' . $end->format('d/m/Y'), // fallback, though custom is usually single date or handled differently 
                'start' => $end->copy()->startOfDay(),
                'end' => $end->copy()->endOfDay()
            ];
        } else {
            $currentDate = $end->copy();

            for ($i = 0; $i <= $comparePeriods; $i++) {
                if ($periodType === 'Month') {
                    $startOfPeriod = $currentDate->copy()->startOfMonth();
                    $endOfPeriod = $currentDate->copy()->endOfMonth();
                    if ($endOfPeriod->isAfter($end)) {
                        $endOfPeriod = $end->copy();
                    }
                    $label = $startOfPeriod->format('M Y');

                    $periods[] = [
                        'label' => $label,
                        'start' => $startOfPeriod->copy(),
                        'end' => $endOfPeriod->copy()
                    ];
                    $currentDate->subMonth()->endOfMonth();
                } elseif ($periodType === 'Quarter') {
                    $startOfPeriod = $currentDate->copy()->firstOfQuarter();
                    $endOfPeriod = $currentDate->copy()->lastOfQuarter();
                    if ($endOfPeriod->isAfter($end)) {
                        $endOfPeriod = $end->copy();
                    }
                    $label = $endOfPeriod->format('d/m/Y');

                    $periods[] = [
                        'label' => $label,
                        'start' => $startOfPeriod->copy(),
                        'end' => $endOfPeriod->copy()
                    ];
                    $currentDate->subQuarter()->lastOfQuarter();
                } elseif ($periodType === 'Year') {
                    $startOfPeriod = $currentDate->copy()->startOfYear();
                    $endOfPeriod = $currentDate->copy()->endOfYear();
                    if ($endOfPeriod->isAfter($end)) {
                        $endOfPeriod = $end->copy();
                    }
                    $label = $startOfPeriod->isSameDay($endOfPeriod)
                        ? $endOfPeriod->format('d/m/Y')
                        : $startOfPeriod->format('d/m/Y') . ' - ' . $endOfPeriod->format('d/m/Y');

                    $periods[] = [
                        'label' => $label,
                        'start' => $startOfPeriod->copy(),
                        'end' => $endOfPeriod->copy()
                    ];
                    $currentDate->subYear()->endOfYear();
                }
            }
            $periods = array_reverse($periods);
        }

        // Always ensure at least one period if empty
        if (empty($periods)) {
            $startOfPeriod = $end->copy()->startOfMonth();
            $label = $startOfPeriod->isSameDay($end)
                ? $end->format('d/m/Y')
                : $startOfPeriod->format('d/m/Y') . ' - ' . $end->format('d/m/Y');

            $periods[] = [
                'label' => $label,
                'start' => $startOfPeriod,
                'end' => $end->copy()
            ];
        }

        // Build 6 months of start/end pairs for sparkline bars (fixed 6 months from endDate backwards)
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $ms = $end->copy()->subMonths($i)->startOfMonth();
            $me = ($i === 0) ? $end->copy() : $ms->copy()->endOfMonth();
            $months[] = [$ms, $me];
        }

        $getBalanceByCategory = function (array $categories) use ($periods, $months) {
            $accounts = Account::whereIn('category', $categories)
                ->whereNull('parent_id')
                ->with('children')
                ->orderBy('code')
                ->get();

            $sections = [];
            $totalsByPeriod = array_fill(0, count($periods), 0);

            // For Sparkline totals
            $sectionSparkTotals = array_fill(0, count($months), 0);

            foreach ($categories as $catKey => $catLabel) {
                $catAccounts = $accounts->where('category', $catKey)->values();
                $catTotalsByPeriod = array_fill(0, count($periods), 0);
                $rows = [];

                foreach ($catAccounts as $account) {
                    $balances = [];
                    $isRevenue = in_array($catKey, ['Pendapatan', 'Pendapatan Lainnya']);
                    $expr = $isRevenue ? 'credit - debit' : 'debit - credit';

                    // get normal multiple periods balances
                    foreach ($periods as $idx => $period) {
                        $balance = (float) JournalItem::where('account_id', $account->id)
                            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$period['start'], $period['end']]))
                            ->sum(DB::raw($expr));

                        $balances[$idx] = $balance;
                        $catTotalsByPeriod[$idx] += $balance;
                    }

                    // 6-month sparkline data
                    $sparkline = [];
                    foreach ($months as [$ms, $me]) {
                        $sparkline[] = (float) JournalItem::where('account_id', $account->id)
                            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$ms, $me]))
                            ->sum(DB::raw($expr));
                    }

                    $childrenData = [];
                    foreach ($account->children->sortBy('code') as $child) {
                        $childBalances = [];
                        foreach ($periods as $idx => $period) {
                            $childBalance = (float) JournalItem::where('account_id', $child->id)
                                ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$period['start'], $period['end']]))
                                ->sum(DB::raw($expr));
                            $childBalances[$idx] = $childBalance;
                        }

                        $childSpark = [];
                        foreach ($months as [$ms, $me]) {
                            $childSpark[] = (float) JournalItem::where('account_id', $child->id)
                                ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$ms, $me]))
                                ->sum(DB::raw($expr));
                        }

                        $childrenData[] = [
                            'code' => $child->code,
                            'name' => $child->name,
                            'balances' => $childBalances,
                            'sparkline' => $childSpark,
                        ];
                    }

                    $rows[] = [
                        'code' => $account->code,
                        'name' => $account->name,
                        'balances' => $balances,
                        'sparkline' => $sparkline,
                        'children' => $childrenData,
                    ];
                }

                foreach ($catTotalsByPeriod as $idx => $val) {
                    $totalsByPeriod[$idx] += $val;
                }

                // Section-level sparkline
                $sectionSpark = [];
                foreach ($months as $idx => [$ms, $me]) {
                    $colSum = 0;
                    foreach ($rows as $r) {
                        $colSum += $r['sparkline'][$idx];
                    }
                    $sectionSpark[] = $colSum;
                    $sectionSparkTotals[$idx] += $colSum;
                }

                $sections[] = [
                    'label' => $catLabel,
                    'rows' => $rows,
                    'totals' => $catTotalsByPeriod,
                    'sparkline' => $sectionSpark,
                ];
            }

            return ['sections' => $sections, 'totals' => $totalsByPeriod, 'sparkline' => $sectionSparkTotals];
        };

        $pendapatan = $getBalanceByCategory(['Pendapatan' => 'Pendapatan']);
        $pendapatanLain = $getBalanceByCategory(['Pendapatan Lainnya' => 'Pendapatan Lainnya']);
        $hpp = $getBalanceByCategory(['Harga Pokok Penjualan' => 'Harga Pokok Penjualan']);
        $beban = $getBalanceByCategory(['Beban' => 'Beban']);
        $bebanLain = $getBalanceByCategory(['Beban Lainnya' => 'Beban Lainnya']);

        $labaKotorTotals = [];
        $labaBersihTotals = [];

        $totalPendapatanAll = array_fill(0, count($periods), 0);
        $totalBebanAll = array_fill(0, count($periods), 0);

        for ($i = 0; $i < count($periods); $i++) {
            $totRev = $pendapatan['totals'][$i] + $pendapatanLain['totals'][$i];
            $totExp = $beban['totals'][$i] + $bebanLain['totals'][$i];

            $totalPendapatanAll[$i] = $totRev;
            $totalBebanAll[$i] = $totExp;

            $kotor = $totRev - $hpp['totals'][$i];
            $bersih = $kotor - $totExp;

            $labaKotorTotals[] = $kotor;
            $labaBersihTotals[] = $bersih;
        }

        $pctChange = function ($current, $previous) {
            if ($previous == 0)
                return $current != 0 ? 100 : 0;
            return round((($current - $previous) / abs($previous)) * 100, 1);
        };

        return [
            'today' => $end->format('d/m/Y'),
            'periods' => $periods,
            'pendapatan' => $pendapatan,
            'pendapatanLain' => $pendapatanLain,
            'hpp' => $hpp,
            'beban' => $beban,
            'bebanLain' => $bebanLain,
            'totalPendapatanAll' => $totalPendapatanAll,
            'totalBebanAll' => $totalBebanAll,
            'labaKotorTotals' => $labaKotorTotals,
            'labaBersihTotals' => $labaBersihTotals,
        ];
    }
}
