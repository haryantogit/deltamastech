<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Budget;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class ManajemenAnggaran extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected string $view = 'filament.pages.laporan.manajemen-anggaran';

    protected static ?string $title = 'Manajemen Anggaran (Budget vs Aktual)';

    protected static ?string $slug = 'manajemen-anggaran';

    protected static bool $shouldRegisterNavigation = false;

    public $budgetId;
    public $search = '';

    public function mount()
    {
        $latestBudget = Budget::latest()->first();
        $this->budgetId = $latestBudget?->id;
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Manajemen Anggaran',
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
                ->label('Filter Anggaran')
                ->icon('heroicon-m-funnel')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\Select::make('budgetId')
                        ->label('Pilih Anggaran')
                        ->options(Budget::pluck('name', 'id'))
                        ->default($this->budgetId)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->budgetId = $data['budgetId'];
                }),
            Action::make('buat')
                ->label('Kelola Anggaran')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('primary')
                ->url(\App\Filament\Resources\BudgetResource::getUrl()),
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(\App\Filament\Pages\ReportPage::getUrl()),
        ];
    }

    public function getViewData(): array
    {
        if (!$this->budgetId) {
            return [
                'budget' => null,
                'items' => collect(),
            ];
        }

        $budget = Budget::with('items.account')->find($this->budgetId);

        if (!$budget) {
            return [
                'budget' => null,
                'items' => collect(),
            ];
        }

        $startDate = \Carbon\Carbon::parse($budget->start_date)->startOfDay();
        $endDate = \Carbon\Carbon::parse($budget->end_date)->endOfDay();

        $items = $budget->items->map(function ($bi) use ($startDate, $endDate) {
            // Calculate actual from journal items
            $actual = JournalItem::where('account_id', $bi->account_id)
                ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                })
                ->select(DB::raw('SUM(debit - credit) as balance'))
                ->value('balance') ?? 0;

            // For Income categories, credit is positive. For Expense, debit is positive.
            // Let's use absolute value or logic based on account category if needed.
            // Simplified: If category is Pendapatan, result should be credit - debit.
            $category = $bi->account->category;
            if (in_array($category, ['Pendapatan', 'Pendapatan Lainnya'])) {
                $actual = -$actual; // reverse if debit - credit was calculated
            }

            $diff = $bi->amount - $actual;
            $percentage = $bi->amount > 0 ? ($actual / $bi->amount) * 100 : 0;

            return (object) [
                'account_name' => $bi->account->name,
                'account_code' => $bi->account->code,
                'target' => (float) $bi->amount,
                'actual' => (float) $actual,
                'difference' => (float) $diff,
                'percentage' => (float) $percentage,
            ];
        });

        if ($this->search) {
            $items = $items->filter(function ($item) {
                return str_contains(strtolower($item->account_name), strtolower($this->search)) ||
                    str_contains(strtolower($item->account_code), strtolower($this->search));
            });
        }

        return [
            'budget' => $budget,
            'items' => $items,
            'total_target' => $items->sum('target'),
            'total_actual' => $items->sum('actual'),
        ];
    }
}
