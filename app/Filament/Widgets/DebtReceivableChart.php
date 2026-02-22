<?php

namespace App\Filament\Widgets;

use App\Models\Debt;
use App\Models\Receivable;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class DebtReceivableChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'HUTANG & PIUTANG';

    protected ?string $maxHeight = '480px';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $filters = $this->filters ?? request()->input('filters') ?? [];

        $startDate = $filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $filters['endDate'] ?? now()->endOfYear()->toDateString();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $labels = [];
        $debtData = [];
        $receivableData = [];
        $netData = [];

        // Explicitly generate months to avoid any CarbonPeriod edge cases
        $current = $start->copy()->startOfMonth();
        while ($current->lte($end)) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            // Constrain to the actual selected range
            if ($monthStart->lt($start))
                $monthStart = $start->copy();
            if ($monthEnd->gt($end))
                $monthEnd = $end->copy();

            $labels[] = $current->format('M Y');

            $r = Receivable::where('invoice_number', 'like', 'DM/%')
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->sum('total_amount');

            $d = Debt::where('number', 'like', 'CM/%')
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->sum('total_amount');

            $receivableData[] = $r;
            $debtData[] = -1 * abs($d);
            $netData[] = $r - $d;

            $current->addMonth();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Net',
                    'data' => $netData,
                    'type' => 'line',
                    'borderColor' => '#c084fc', // Purple
                    'backgroundColor' => 'rgba(192, 132, 252, 0.1)',
                    'pointBackgroundColor' => '#c084fc',
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Piutang',
                    'data' => $receivableData,
                    'backgroundColor' => '#2dd4bf', // Teal
                    'borderColor' => '#2dd4bf',
                ],
                [
                    'label' => 'Hutang',
                    'data' => $debtData,
                    'backgroundColor' => '#f43f5e', // Red/Rose
                    'borderColor' => '#f43f5e',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getFilters(): ?array
    {
        return null;
    }

    protected function getType(): string
    {
        return 'bar'; // Base type bar, allows mixed types in datasets
    }
}
