<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\DB;

class MoneyInOutChart extends ChartWidget
{
    use \Filament\Widgets\Concerns\InteractsWithPageFilters;

    protected ?string $heading = 'TOTAL KELUAR MASUK KAS';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $filters = $this->filters ?? request()->input('filters') ?? [];
        $startDate = $filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $filters['endDate'] ?? now()->endOfYear()->toDateString();

        $start = \Illuminate\Support\Carbon::parse($startDate);
        $end = \Illuminate\Support\Carbon::parse($endDate);

        $labels = [];
        $moneyIn = [];
        $moneyOut = [];
        $net = [];

        $current = $start->copy()->startOfMonth();
        while ($current->lte($end)) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            if ($monthStart->lt($start))
                $monthStart = $start->copy();
            if ($monthEnd->gt($end))
                $monthEnd = $end->copy();

            $labels[] = $current->format('M Y');

            $in = (float) SalesInvoice::whereBetween('transaction_date', [$monthStart, $monthEnd])->sum('total_amount');
            $out = (float) PurchaseInvoice::whereBetween('date', [$monthStart, $monthEnd])->sum('total_amount');

            $moneyIn[] = $in;
            $moneyOut[] = $out;
            $net[] = $in - $out;

            $current->addMonth();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Net',
                    'data' => $net,
                    'type' => 'line',
                    'borderColor' => '#a855f7', // Purple
                    'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                    'pointBackgroundColor' => '#a855f7',
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'In',
                    'data' => $moneyIn,
                    'backgroundColor' => '#2dd4bf', // Teal/Cyan
                    'borderColor' => '#2dd4bf',
                ],
                [
                    'label' => 'Out',
                    'data' => $moneyOut,
                    'backgroundColor' => '#f43f5e', // Rose/Red
                    'borderColor' => '#f43f5e',
                ],
            ],
            'labels' => array_values($labels),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
