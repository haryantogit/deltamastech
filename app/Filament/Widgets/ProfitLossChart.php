<?php

namespace App\Filament\Widgets;

use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class ProfitLossChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'LABA RUGI';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $filters = $this->filters ?? request()->input('filters') ?? [];
        $startDate = $filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $filters['endDate'] ?? now()->endOfYear()->toDateString();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $data = [];
        $labels = [];

        $current = $start->copy()->startOfMonth();
        while ($current->lte($end)) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            if ($monthStart->lt($start))
                $monthStart = $start->copy();
            if ($monthEnd->gt($end))
                $monthEnd = $end->copy();

            $labels[] = $current->format('M Y');

            $income = SalesInvoice::whereBetween('transaction_date', [$monthStart, $monthEnd])->sum('total_amount');
            $expense = PurchaseInvoice::whereBetween('date', [$monthStart, $monthEnd])->sum('total_amount');

            $data[] = $income - $expense;

            $current->addMonth();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Laba/Rugi',
                    'data' => $data,
                    'backgroundColor' => array_map(fn($val) => $val >= 0 ? '#10b981' : '#f59e0b', $data), // Green if positive, Orange if negative
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
