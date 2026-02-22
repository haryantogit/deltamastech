<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class SalesChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'PENJUALAN';

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $filters = $this->filters ?? request()->input('filters') ?? [];
        $startDate = $filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $filters['endDate'] ?? now()->endOfYear()->toDateString();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $dataset = [];
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

            $dataset[] = (float) \App\Models\SalesInvoice::whereBetween('transaction_date', [$monthStart, $monthEnd])->sum('total_amount');

            $current->addMonth();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Penjualan (IDR)',
                    'data' => $dataset,
                    'borderColor' => '#3b82f6', // Blue
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
