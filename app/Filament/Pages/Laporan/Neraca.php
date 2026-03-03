<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Account;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Livewire\Attributes\On;

class Neraca extends Page
{
    use HasFiltersForm;

    public string $statsFilter = 'bulan';

    public function mount(): void
    {
        $this->filters = [
            'period_type' => 'Custom',
            'compare_periods' => 0,
            'year' => now()->year,
            'endDate' => now()->toDateString(),
        ];
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.laporan.neraca';

    protected static ?string $title = 'Neraca';

    protected static ?string $slug = 'neraca';

    protected static bool $shouldRegisterNavigation = false;

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Neraca',
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter')
                ->label('Filter')
                ->icon('heroicon-m-funnel')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\Select::make('period_type')
                        ->label('Tipe Periode')
                        ->options([
                            'Custom' => 'Periode',
                            'Month' => 'Bulanan',
                            'Quarter' => 'Kuartalan',
                            'Year' => 'Tahunan',
                        ])
                        ->default($this->filters['period_type'] ?? 'Custom')
                        ->required(),
                    \Filament\Forms\Components\Select::make('compare_periods')
                        ->label('Bandingkan Periode')
                        ->options([
                            0 => 'Bandingkan',
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

    public function filtersForm(Schema $form): Schema
    {
        return $form
            ->schema([
                //
            ])
            ->columns(1)
            ->statePath('filters');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedSchema::make('filtersForm'),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\NeracaStatsWidget::class,
        ];
    }

    public function getViewData(): array
    {
        $periodType = $this->filters['period_type'] ?? 'Quarter';
        $comparePeriods = (int) ($this->filters['compare_periods'] ?? 3);
        $year = (int) ($this->filters['year'] ?? now()->year);
        $endDate = $this->filters['endDate'] ?? now()->toDateString();

        $periods = [];

        if ($periodType === 'Custom') {
            $end = Carbon::parse($endDate);
            $periods[] = [
                // Neraca is usually point in time, so a single date is always appropriate
                'label' => $end->format('d/m/Y'),
                'end' => $end->copy()
            ];
        } else {
            // Generate periods based on type
            $endOfSelectedYear = Carbon::create($year, 12, 31)->endOfDay();
            if ($year === now()->year) {
                $endOfSelectedYear = now()->endOfDay();
            }

            $currentDate = $endOfSelectedYear->copy();

            for ($i = 0; $i <= $comparePeriods; $i++) {
                if ($periodType === 'Month') {
                    $startOfPeriod = $currentDate->copy()->startOfMonth();
                    $endOfPeriod = $currentDate->copy()->endOfMonth();
                    $label = $startOfPeriod->format('M Y');

                    $periods[] = [
                        'label' => $label,
                        'end' => $endOfPeriod->copy()
                    ];
                    $currentDate->subMonth()->endOfMonth();
                } elseif ($periodType === 'Quarter') {
                    $startOfPeriod = $currentDate->copy()->firstOfQuarter();
                    $endOfPeriod = $currentDate->copy()->lastOfQuarter();
                    if ($endOfPeriod->isFuture()) {
                        $endOfPeriod = now()->endOfDay();
                    }
                    $label = $endOfPeriod->format('d/m/Y');

                    $periods[] = [
                        'label' => $label,
                        'end' => $endOfPeriod->copy()
                    ];
                    $currentDate->subQuarter()->lastOfQuarter();
                } elseif ($periodType === 'Year') {
                    $startOfPeriod = $currentDate->copy()->startOfYear();
                    $endOfPeriod = $currentDate->copy()->endOfYear();
                    if ($endOfPeriod->isFuture()) {
                        $endOfPeriod = now()->endOfDay();
                    }
                    $label = $startOfPeriod->format('Y');

                    $periods[] = [
                        'label' => $label,
                        'end' => $endOfPeriod->copy()
                    ];
                    $currentDate->subYear()->endOfYear();
                }
            }
            // Reverse so oldest is first if we want left-to-right (or keep newest first, we will reverse it for UI)
            $periods = array_reverse($periods);
        }

        // Helper to get account balance from journal entries up to a date
        $getBalanceByCategory = function (array $categories) use ($periods) {
            $accounts = Account::whereIn('category', $categories)
                ->whereNull('parent_id')
                ->with('children')
                ->orderBy('code')
                ->get();

            $sections = [];
            $totalsByPeriod = array_fill(0, count($periods), 0);

            foreach ($categories as $catKey => $catLabel) {
                $catAccounts = $accounts->where('category', $catKey)->values();
                $catTotalsByPeriod = array_fill(0, count($periods), 0);
                $rows = [];

                foreach ($catAccounts as $account) {
                    $balances = [];
                    foreach ($periods as $idx => $period) {
                        $balance = (float) JournalItem::where('account_id', $account->id)
                            ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<=', $period['end']))
                            ->sum(DB::raw('debit - credit'));

                        $balances[$idx] = $balance;
                        $catTotalsByPeriod[$idx] += $balance;
                    }

                    $children = [];
                    foreach ($account->children->sortBy('code') as $child) {
                        $childBalances = [];
                        foreach ($periods as $idx => $period) {
                            $childBalance = (float) JournalItem::where('account_id', $child->id)
                                ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<=', $period['end']))
                                ->sum(DB::raw('debit - credit'));
                            $childBalances[$idx] = $childBalance;
                        }

                        $children[] = [
                            'code' => $child->code,
                            'name' => $child->name,
                            'balances' => $childBalances,
                        ];
                    }

                    $rows[] = [
                        'code' => $account->code,
                        'name' => $account->name,
                        'balances' => $balances,
                        'children' => $children,
                    ];
                }

                foreach ($catTotalsByPeriod as $idx => $val) {
                    $totalsByPeriod[$idx] += $val;
                }

                $sections[] = [
                    'label' => $catLabel,
                    'rows' => $rows,
                    'totals' => $catTotalsByPeriod,
                ];
            }

            return ['sections' => $sections, 'totals' => $totalsByPeriod];
        };

        // Build all sections with dynamic data
        $kasBank = $getBalanceByCategory(['Kas & Bank' => 'Kas & Bank']);
        $piutang = $getBalanceByCategory(['Akun Piutang' => 'Akun Piutang']);
        $persediaan = $getBalanceByCategory(['Persediaan' => 'Persediaan']);
        $aktivaLancarLain = $getBalanceByCategory(['Aktiva Lancar Lainnya' => 'Aktiva Lancar Lainnya']);
        $asetTetap = $getBalanceByCategory(['Aktiva Tetap' => 'Aktiva Tetap']);
        $depresiasi = $getBalanceByCategory(['Depresiasi & Amortisasi' => 'Depresiasi & Amortisasi']);
        $hutang = $getBalanceByCategory(['Akun Hutang' => 'Akun Hutang']);
        $kewajibanLancar = $getBalanceByCategory(['Kewajiban Lancar Lainnya' => 'Kewajiban Lancar Lainnya']);
        $ekuitas = $getBalanceByCategory(['Ekuitas' => 'Ekuitas']);

        // Totals
        $totalAsetLancar = [];
        $totalAsetTetap = [];
        $totalDepresiasi = [];
        $totalAset = [];
        $totalLiabilitasPendek = [];
        $totalModal = [];
        $totalLiabilitasModal = [];

        foreach ($periods as $idx => $period) {
            $totalAsetLancar[$idx] = $kasBank['totals'][$idx] + $piutang['totals'][$idx] + $persediaan['totals'][$idx] + $aktivaLancarLain['totals'][$idx];
            $totalAsetTetap[$idx] = $asetTetap['totals'][$idx];
            $totalDepresiasi[$idx] = $depresiasi['totals'][$idx];
            $totalAset[$idx] = $totalAsetLancar[$idx] + $totalAsetTetap[$idx] + $totalDepresiasi[$idx];

            $totalLiabilitasPendek[$idx] = $hutang['totals'][$idx] + $kewajibanLancar['totals'][$idx];
            $totalModal[$idx] = $ekuitas['totals'][$idx];
            $totalLiabilitasModal[$idx] = $totalLiabilitasPendek[$idx] + $totalModal[$idx];
        }

        return [
            'periods' => $periods,
            'kasBank' => $kasBank,
            'piutang' => $piutang,
            'persediaan' => $persediaan,
            'aktivaLancarLain' => $aktivaLancarLain,
            'asetTetap' => $asetTetap,
            'depresiasi' => $depresiasi,
            'hutang' => $hutang,
            'kewajibanLancar' => $kewajibanLancar,
            'ekuitas' => $ekuitas,
            'totalAsetLancar' => $totalAsetLancar,
            'totalAsetTetap' => $totalAsetTetap,
            'totalDepresiasi' => $totalDepresiasi,
            'totalAset' => $totalAset,
            'totalLiabilitasPendek' => $totalLiabilitasPendek,
            'totalModal' => $totalModal,
            'totalLiabilitasModal' => $totalLiabilitasModal,
        ];
    }
}
