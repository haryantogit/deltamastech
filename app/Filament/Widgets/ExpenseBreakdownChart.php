<?php

namespace App\Filament\Widgets;

use App\Models\JournalItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ExpenseBreakdownChart extends ChartWidget
{
    use \Filament\Widgets\Concerns\InteractsWithPageFilters;

    protected ?string $heading = 'RINCIAN BIAYA';

    public function getDescription(): ?string
    {
        $filters = $this->filters ?? request()->input('filters') ?? [];
        $startDate = $filters['startDate'] ?? now()->startOfMonth()->toDateString();
        $endDate = $filters['endDate'] ?? now()->endOfYear()->toDateString();

        return \Illuminate\Support\Carbon::parse($startDate)->format('d/m/Y') . ' - ' . \Illuminate\Support\Carbon::parse($endDate)->format('d/m/Y');
    }

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $filters = $this->filters ?? request()->input('filters') ?? [];
        $startDate = $filters['startDate'] ?? now()->startOfMonth()->toDateString();
        $endDate = $filters['endDate'] ?? now()->endOfYear()->toDateString();

        $expenses = DB::table('expense_items')
            ->join('accounts', 'expense_items.account_id', '=', 'accounts.id')
            ->join('expenses', 'expense_items.expense_id', '=', 'expenses.id')
            ->whereBetween('expenses.transaction_date', [$startDate, $endDate])
            ->selectRaw('accounts.name as label, SUM(expense_items.amount) as total')
            ->groupBy('accounts.name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        if ($expenses->isEmpty()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Take top 5 and group the rest as "Other"
        $topExpenses = $expenses->take(5);
        $otherTotal = $expenses->skip(5)->sum('total');

        $labels = $topExpenses->pluck('label')->toArray();
        $data = $topExpenses->pluck('total')->toArray();

        if ($otherTotal > 0) {
            $labels[] = 'Other';
            $data[] = $otherTotal;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Biaya',
                    'data' => $data,
                    'backgroundColor' => [
                        '#f87171', // Red - Upah
                        '#fbbf24', // Amber - Listrik
                        '#5eead4', // Teal - Gaji
                        '#60a5fa', // Blue - Biaya Sewa
                        '#7dd3fc', // Light Blue - Iuran & Langganan
                        '#c084fc', // Purple - Other
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getFooter(): ?string
    {
        $lastMonth = now()->subMonth();

        $expenses = DB::table('expense_items')
            ->join('accounts', 'expense_items.account_id', '=', 'accounts.id')
            ->join('expenses', 'expense_items.expense_id', '=', 'expenses.id')
            ->whereMonth('expenses.transaction_date', $lastMonth->month)
            ->whereYear('expenses.transaction_date', $lastMonth->year)
            ->selectRaw('accounts.name as label, SUM(expense_items.amount) as total')
            ->groupBy('accounts.name')
            ->orderByDesc('total')
            ->get();

        if ($expenses->isEmpty()) {
            return null;
        }

        $total = $expenses->sum('total');

        $rows = $expenses->map(function ($expense) {
            return '<tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="py-2 text-sm text-gray-700 dark:text-gray-300">' . e($expense->label) . '</td>
                <td class="py-2 text-sm text-right text-gray-900 dark:text-gray-100 font-medium">' . number_format($expense->total, 0, ',', '.') . '</td>
            </tr>';
        })->join('');

        return '<div class="mt-4">
            <table class="w-full">
                <tbody>
                    ' . $rows . '
                    <tr class="border-t-2 border-gray-300 dark:border-gray-600 font-bold">
                        <td class="py-2 text-sm text-gray-900 dark:text-gray-100">Total</td>
                        <td class="py-2 text-sm text-right text-gray-900 dark:text-gray-100">' . number_format($total, 0, ',', '.') . '</td>
                    </tr>
                </tbody>
            </table>
        </div>';
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
