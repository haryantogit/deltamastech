<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Account;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Livewire\WithPagination;

class TrialBalance extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    protected static string $paginationView = 'filament-actions::link-pagination';

    protected string $view = 'filament.pages.laporan.trial-balance';

    protected static ?string $title = 'Trial Balance';

    protected static ?string $slug = 'trial-balance';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public int $perPage = 100;
    public array $expandedCategories = [];

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
        // Optionally expand everything by default or keep collapsed
        // $this->expandedCategories = ['Kas & Bank', 'Akun Piutang', ...];
    }

    public function toggleCategory(string $category): void
    {
        if (in_array($category, $this->expandedCategories)) {
            $this->expandedCategories = array_diff($this->expandedCategories, [$category]);
        } else {
            $this->expandedCategories[] = $category;
        }
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }
    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Trial Balance',
        ];
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
                    \Filament\Forms\Components\DatePicker::make('startDate')
                        ->label('Tanggal Mulai')
                        ->default($this->startDate)
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('endDate')
                        ->label('Tanggal Akhir')
                        ->default($this->endDate)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->startDate = $data['startDate'];
                    $this->endDate = $data['endDate'];
                    $this->resetPage();
                }),
            Action::make('print')
                ->label('Print')
                ->color('gray')
                ->icon('heroicon-o-printer')
                ->action(fn() => $this->js('window.print()')),
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(\App\Filament\Pages\ReportPage::getUrl()),
        ];
    }

    public function getViewData(): array
    {
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        // Fetch all accounts that have transactions
        $accounts = Account::where('is_active', true)
            ->whereNull('parent_id')
            ->with([
                'children' => function ($q) {
                    $q->where('is_active', true)->orderBy('code');
                }
            ])
            ->orderBy('code')
            ->get();

        $rows = [];
        $grandTotals = [
            'opening_debit' => 0,
            'opening_credit' => 0,
            'movement_debit' => 0,
            'movement_credit' => 0,
            'ending_debit' => 0,
            'ending_credit' => 0
        ];

        foreach ($accounts as $parent) {
            if (!($parent instanceof Account))
                continue;

            $parentRow = $this->getAccountBalanceData($parent, $start, $end);
            $rows[] = array_merge($parentRow, ['is_parent' => true]);

            foreach ($parent->children as $child) {
                if (!($child instanceof Account))
                    continue;

                $childRow = $this->getAccountBalanceData($child, $start, $end);
                $rows[] = array_merge($childRow, ['is_parent' => false]);
            }
        }

        // Calculate Grand Totals
        foreach ($rows as $row) {
            $grandTotals['opening_debit'] += $row['opening_debit'];
            $grandTotals['opening_credit'] += $row['opening_credit'];
            $grandTotals['movement_debit'] += $row['movement_debit'];
            $grandTotals['movement_credit'] += $row['movement_credit'];
            $grandTotals['ending_debit'] += $row['ending_debit'];
            $grandTotals['ending_credit'] += $row['ending_credit'];
        }

        // Stats Calculation (Neraca Style)
        $stats = $this->getStatsOverview($start, $end);

        return [
            'rows' => $rows,
            'grandTotals' => $grandTotals,
            'stats' => $stats,
        ];
    }

    private function getAccountBalanceData(Account $account, Carbon $start, Carbon $end): array
    {
        // Opening Balance (before start)
        $opening = JournalItem::where('account_id', $account->id)
            ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<', $start->format('Y-m-d')))
            ->selectRaw('SUM(debit) as debit, SUM(credit) as credit')
            ->first();

        $openingDebit = (float) ($opening->debit ?? 0);
        $openingCredit = (float) ($opening->credit ?? 0);

        // Movement (between start and end)
        $movement = JournalItem::where('account_id', $account->id)
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start->format('Y-m-d'), $end->format('Y-m-d')]))
            ->selectRaw('SUM(debit) as debit, SUM(credit) as credit')
            ->first();

        $movementDebit = (float) ($movement->debit ?? 0);
        $movementCredit = (float) ($movement->credit ?? 0);

        // Ending Balance
        $totalDebit = $openingDebit + $movementDebit;
        $totalCredit = $openingCredit + $movementCredit;

        $endingDebit = 0;
        $endingCredit = 0;

        if ($totalDebit > $totalCredit) {
            $endingDebit = $totalDebit - $totalCredit;
        } else {
            $endingCredit = $totalCredit - $totalDebit;
        }

        return [
            'id' => $account->id,
            'name' => $account->name,
            'code' => $account->code,
            'category' => $account->category,
            'opening_debit' => $openingDebit,
            'opening_credit' => $openingCredit,
            'movement_debit' => $movementDebit,
            'movement_credit' => $movementCredit,
            'ending_debit' => $endingDebit,
            'ending_credit' => $endingCredit,
        ];
    }

    private function getStatsOverview(Carbon $start, Carbon $end): array
    {
        $prevStart = $start->copy()->subMonth()->startOfMonth();
        $prevEnd = $start->copy()->subDay();

        $getMetric = function ($categories, $dateRange) {
            $accountIds = Account::whereIn('category', (array) $categories)->pluck('id')->toArray();
            return (float) JournalItem::whereIn('account_id', $accountIds)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$dateRange[0]->format('Y-m-d'), $dateRange[1]->format('Y-m-d')]))
                ->sum(DB::raw('debit - credit'));
        };

        // Example metrics matching Trial Balance screenshot top cards
        $metrics = [
            'kas' => ['label' => 'Kas & Bank', 'cats' => ['Kas & Bank']],
            'piutang' => ['label' => 'Saldo Piutang', 'cats' => ['Akun Piutang']],
            'hutang' => ['label' => 'Saldo Hutang', 'cats' => ['Akun Hutang']],
            'net' => ['label' => 'Net Profit/Loss', 'cats' => ['Pendapatan', 'Beban', 'Beban Atas Pendapatan', 'Pendapatan Lainnya', 'Beban Lainnya']],
        ];

        $results = [];
        foreach ($metrics as $key => $m) {
            $currentValue = $getMetric($m['cats'], [$start, $end]);
            $prevValue = $getMetric($m['cats'], [$prevStart, $prevEnd]);

            // For liability/equity, flip sign for display if needed
            if ($key === 'hutang')
                $currentValue = abs($currentValue);

            $results[$key] = [
                'label' => $m['label'],
                'value' => $currentValue,
                'trend' => $this->calculateTrend($currentValue, $prevValue),
            ];
        }

        return $results;
    }

    private function calculateTrend($current, $previous): array
    {
        if ($previous == 0) {
            return ['pct' => $current > 0 ? 100 : 0, 'icon' => 'heroicon-m-arrow-trending-up', 'color' => 'success'];
        }
        $pct = round((($current - $previous) / abs($previous)) * 100, 1);
        return [
            'pct' => $pct,
            'icon' => $pct >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down',
            'color' => $pct >= 0 ? 'success' : 'danger',
        ];
    }
}
