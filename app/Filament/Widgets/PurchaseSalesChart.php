<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class PurchaseSalesChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'PEMBELIAN & PENJUALAN';

    protected ?string $maxHeight = '480px';

    protected static ?int $sort = 2; // Positioned after DebtReceivableChart

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $filters = $this->filters ?? request()->input('filters') ?? [];
        $startDate = $filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $filters['endDate'] ?? now()->endOfYear()->toDateString();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $labels = [];
        $purchaseData = [];
        $salesData = [];
        $netData = [];

        // Explicitly generate months to avoid any CarbonPeriod edge cases
        $current = $start->copy()->startOfMonth();
        while ($current->lte($end)) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            if ($monthStart->lt($start))
                $monthStart = $start->copy();
            if ($monthEnd->gt($end))
                $monthEnd = $end->copy();

            $labels[] = $current->format('M Y');

            $p = PurchaseInvoice::whereBetween('date', [$monthStart, $monthEnd])->sum('total_amount');
            $s = SalesInvoice::whereBetween('transaction_date', [$monthStart, $monthEnd])->sum('total_amount');

            $purchaseData[] = -1 * abs($p);
            $salesData[] = $s;
            $netData[] = $s - $p;

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
                    'label' => 'Penjualan',
                    'data' => $salesData,
                    'backgroundColor' => '#2dd4bf', // Teal
                    'borderColor' => '#2dd4bf',
                ],
                [
                    'label' => 'Pembelian',
                    'data' => $purchaseData,
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
        return 'bar';
    }
}
