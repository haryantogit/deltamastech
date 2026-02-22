<?php

namespace App\Filament\Widgets;

use App\Models\ExpenseItem;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ExpenseCategoryChart extends ChartWidget
{
    protected ?string $heading = 'BIAYA';
    protected ?string $maxHeight = '300px';
    protected int|string|array $columnSpan = 2;

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn($i) => Carbon::now()->subMonths($i));
        $labels = $months->map(fn($m) => $m->translatedFormat('M'))->toArray();

        // Get top 5 account names by spent amount
        $topAccounts = DB::table('expense_items')
            ->select('accounts.name', DB::raw('SUM(expense_items.amount) as total'))
            ->join('accounts', 'expense_items.account_id', '=', 'accounts.id')
            ->groupBy('accounts.name')
            ->orderByDesc('total')
            ->limit(5)
            ->pluck('accounts.name')
            ->toArray();

        // Modern Premium Palette
        $colors = [
            '#6366f1', // Indigo
            '#10b981', // Emerald
            '#f43f5e', // Rose
            '#f59e0b', // Amber
            '#3b82f6', // Blue
            '#8b5cf6', // Violet
            '#94a3b8', // Slate
        ];

        // Optimized query: get all sums at once
        $queryData = DB::table('expense_items')
            ->select(
                'accounts.name as account_name',
                DB::raw("DATE_FORMAT(expenses.transaction_date, '%Y-%m') as month_key"),
                DB::raw('SUM(expense_items.amount) as total')
            )
            ->join('expenses', 'expense_items.expense_id', '=', 'expenses.id')
            ->join('accounts', 'expense_items.account_id', '=', 'accounts.id')
            ->where('expenses.transaction_date', '>=', $months->first()->startOfMonth())
            ->groupBy('account_name', 'month_key')
            ->get()
            ->groupBy('account_name');

        $datasets = [];
        foreach ($topAccounts as $index => $accountName) {
            $accountData = $queryData->get($accountName) ?? collect();
            $data = $months->map(function ($month) use ($accountData) {
                $monthKey = $month->format('Y-m');
                return (float) ($accountData->firstWhere('month_key', $monthKey)?->total ?? 0);
            })->toArray();

            $datasets[] = [
                'label' => $accountName,
                'data' => $data,
                'borderColor' => $colors[$index % count($colors)],
                'backgroundColor' => 'rgba(' . $this->hexToRgb($colors[$index % count($colors)]) . ', 0.1)',
                'fill' => 'origin',
                'tension' => 0.4,
                'pointRadius' => 2,
            ];
        }

        // Others
        $othersData = $months->map(function ($month) use ($queryData, $topAccounts) {
            $monthKey = $month->format('Y-m');
            $total = 0;
            foreach ($queryData as $name => $items) {
                if (!in_array($name, $topAccounts)) {
                    $total += (float) ($items->firstWhere('month_key', $monthKey)?->total ?? 0);
                }
            }
            return $total;
        })->toArray();

        $datasets[] = [
            'label' => 'Lainnya',
            'data' => $othersData,
            'borderColor' => $colors[6],
            'backgroundColor' => 'rgba(' . $this->hexToRgb($colors[6]) . ', 0.1)',
            'fill' => 'origin',
            'tension' => 0.4,
            'pointRadius' => 2,
        ];

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function hexToRgb($hex)
    {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "$r, $g, $b";
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "Rp " + new Intl.NumberFormat("id-ID").format(value); }',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { 
                            let label = context.dataset.label || "";
                            if (label) { label += ": "; }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(context.parsed.y);
                            }
                            return label;
                        }',
                    ],
                ],
            ],
        ];
    }
}
