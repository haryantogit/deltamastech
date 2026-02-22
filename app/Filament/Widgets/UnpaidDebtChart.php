<?php

namespace App\Filament\Widgets;

use App\Models\Debt;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class UnpaidDebtChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'TAGIHAN YANG PERLU KAMU BAYAR';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '520px';

    protected function getData(): array
    {
        // Tagihan Aging: <1 month, 1 month, 2 months, 3 months, Older
        $filters = $this->filters ?? request()->input('filters') ?? [];
        $endDate = $filters['endDate'] ?? now()->endOfYear()->toDateString();
        $anchor = Carbon::parse($endDate);

        $buckets = [
            '<1 months' => 0,
            '1 months' => 0,
            '2 months' => 0,
            '3 months' => 0,
            'Older' => 0,
        ];

        // Fetch unpaid debts up to anchor
        $unpaidDebts = Debt::where('payment_status', '!=', 'paid')
            ->where('date', '<=', $anchor)
            ->get();

        foreach ($unpaidDebts as $debt) {
            if (!$debt->due_date)
                continue;

            $dueDate = Carbon::parse($debt->due_date);
            $diffInMonths = $dueDate->diffInMonths($anchor);

            // Logic: if due_date is in future, it's <1 month (or upcoming)
            // if due_date is past:
            // 0-1 month overdue -> 1 months
            // 1-2 months overdue -> 2 months...

            // Simplified for bucket mapping
            if ($dueDate->isFuture()) {
                $buckets['<1 months'] += $debt->total_amount;
            } else {
                if ($diffInMonths < 1)
                    $buckets['<1 months'] += $debt->total_amount;
                elseif ($diffInMonths < 2)
                    $buckets['1 months'] += $debt->total_amount;
                elseif ($diffInMonths < 3)
                    $buckets['2 months'] += $debt->total_amount;
                elseif ($diffInMonths < 4)
                    $buckets['3 months'] += $debt->total_amount;
                else
                    $buckets['Older'] += $debt->total_amount;
            }
        }

        // Fallback demo data if empty
        if ($unpaidDebts->isEmpty()) {
            // 0 for all as per screenshot "Tagihan... 0 Menunggu pembayaran"
            // Screenshot has empty chart with 0s. 
            // We return 0s.
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Amount',
                    'data' => array_values($buckets),
                    'backgroundColor' => '#f43f5e', // Red
                    'barThickness' => 20,
                ],
            ],
            'labels' => array_keys($buckets),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
