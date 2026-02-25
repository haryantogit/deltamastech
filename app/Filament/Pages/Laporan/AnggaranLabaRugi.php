<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Account;
use App\Models\Budget;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class AnggaranLabaRugi extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected string $view = 'filament.pages.laporan.anggaran-laba-rugi';

    protected static ?string $title = 'Anggaran Laba Rugi';

    protected static ?string $slug = 'laporan/anggaran-laba-rugi';

    protected static bool $shouldRegisterNavigation = false;

    public ?int $budgetId = null;

    public function mount(): void
    {
        // Pick the latest budget by default if none selected
        if (!$this->budgetId) {
            $this->budgetId = Budget::latest()->first()?->id;
        }
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\AnggaranPage::getUrl() => 'Anggaran',
            'Anggaran Laba Rugi',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter')
                ->label('Pilih Anggaran')
                ->icon('heroicon-m-funnel')
                ->form([
                    Select::make('budgetId')
                        ->label('Anggaran')
                        ->options(Budget::pluck('name', 'id'))
                        ->default($this->budgetId)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->budgetId = $data['budgetId'];
                }),
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->outlined()
                ->icon('heroicon-o-arrow-left')
                ->url(\App\Filament\Pages\AnggaranPage::getUrl()),
        ];
    }

    public function getViewData(): array
    {
        $budget = Budget::with('items.account')->find($this->budgetId);

        if (!$budget) {
            return [
                'budget' => null,
                'sections' => [],
            ];
        }

        $startDate = $budget->start_date;
        $endDate = $budget->end_date;

        // Group accounts by category
        $groupedItems = $budget->items->groupBy(function ($item) {
            return $item->account->category;
        });

        $sections = [];
        $categories = [
            'Pendapatan' => ['color' => '#2563eb', 'bg' => '#dbeafe'],
            'Harga Pokok Penjualan' => ['color' => '#dc2626', 'bg' => '#fee2e2'],
            'Beban' => ['color' => '#d97706', 'bg' => '#fef3c7'],
            'Pendapatan Lainnya' => ['color' => '#9333ea', 'bg' => '#f3e8ff'],
            'Beban Lainnya' => ['color' => '#db2777', 'bg' => '#fce7f3'],
        ];

        $totalBudgetRevenue = 0;
        $totalActualRevenue = 0;
        $totalBudgetExpense = 0;
        $totalActualExpense = 0;
        $totalBudgetHPP = 0;
        $totalActualHPP = 0;

        foreach ($categories as $category => $style) {
            $items = $groupedItems->get($category, collect());
            if ($items->isEmpty())
                continue;

            $rows = [];
            $catBudgetTotal = 0;
            $catActualTotal = 0;

            foreach ($items as $item) {
                $actual = JournalItem::where('account_id', $item->account_id)
                    ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('transaction_date', [$startDate, $endDate]);
                    })
                    ->select(DB::raw('SUM(debit - credit) as balance'))
                    ->value('balance') ?? 0;

                // Adjust based on normal balance
                if (in_array($category, ['Pendapatan', 'Pendapatan Lainnya'])) {
                    $actual = -$actual;
                }

                $catBudgetTotal += $item->amount;
                $catActualTotal += $actual;

                $diff = $item->amount - $actual;
                $percentage = $item->amount > 0 ? ($actual / $item->amount) * 100 : 0;

                $rows[] = [
                    'account' => $item->account->name,
                    'code' => $item->account->code,
                    'budget' => $item->amount,
                    'actual' => $actual,
                    'diff' => $diff,
                    'percentage' => $percentage,
                ];
            }

            $sections[] = [
                'category' => $category,
                'rows' => $rows,
                'budgetTotal' => $catBudgetTotal,
                'actualTotal' => $catActualTotal,
                'style' => $style,
            ];

            // Aggregates
            if (str_contains($category, 'Pendapatan')) {
                $totalBudgetRevenue += $catBudgetTotal;
                $totalActualRevenue += $catActualTotal;
            } elseif ($category === 'Harga Pokok Penjualan') {
                $totalBudgetHPP += $catBudgetTotal;
                $totalActualHPP += $catActualTotal;
            } else {
                $totalBudgetExpense += $catBudgetTotal;
                $totalActualExpense += $catActualTotal;
            }
        }

        $budgetLabaKotor = $totalBudgetRevenue - $totalBudgetHPP;
        $actualLabaKotor = $totalActualRevenue - $totalActualHPP;
        $budgetLabaBersih = $budgetLabaKotor - $totalBudgetExpense;
        $actualLabaBersih = $actualLabaKotor - $totalActualExpense;

        return [
            'budget' => $budget,
            'sections' => $sections,
            'summary' => [
                'totalBudgetRevenue' => $totalBudgetRevenue,
                'totalActualRevenue' => $totalActualRevenue,
                'totalBudgetHPP' => $totalBudgetHPP,
                'totalActualHPP' => $totalActualHPP,
                'labaKotorBudget' => $budgetLabaKotor,
                'labaKotorActual' => $actualLabaKotor,
                'totalBudgetExpense' => $totalBudgetExpense,
                'totalActualExpense' => $totalActualExpense,
                'labaBersihBudget' => $budgetLabaBersih,
                'labaBersihActual' => $actualLabaBersih,
            ]
        ];
    }
}
