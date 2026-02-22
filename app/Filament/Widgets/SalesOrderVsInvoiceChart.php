<?php

namespace App\Filament\Widgets;

use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SalesOrderVsInvoiceChart extends ChartWidget
{
    protected ?string $heading = 'TAGIHAN & PESANAN';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 8;

    protected ?string $maxHeight = '275px';

    protected $listeners = ['update-sales-overview-filter' => '$refresh'];



    protected function getData(): array
    {
        $filter = request()->query('filter', 'year');

        if ($filter === 'year') {
            // Show all months of the current year
            $months = collect(range(1, 12))->map(fn($i) => Carbon::now()->month($i));
        } else {
            // Show last 6 months
            $months = collect(range(5, 0))->map(fn($i) => Carbon::now()->subMonths($i));
        }

        $labels = $months->map(fn($date) => $date->format('M'))->toArray();

        $invoiceData = [];
        $orderData = [];

        foreach ($months as $date) {
            $month = $date->month;
            $year = $date->year;

            $invoiceData[] = SalesInvoice::whereMonth('transaction_date', $month)
                ->whereYear('transaction_date', $year)
                ->sum('total_amount');

            $orderData[] = SalesOrder::whereMonth('date', $month)
                ->whereYear('date', $year)
                ->sum('total_amount');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Tagihan',
                    'data' => $invoiceData,
                    'backgroundColor' => '#2dd4bf', // Teal
                    'barPercentage' => 0.6,
                ],
                [
                    'label' => 'Pesanan',
                    'data' => $orderData,
                    'backgroundColor' => '#fbbf24', // Amber/Yellow
                    'barPercentage' => 0.6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => true,
            'aspectRatio' => 3.0,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        $filter = request()->query('filter', 'year');
        $periodLabel = $filter === 'year' ? 'Tahun Ini' : 'Bulan Ini';
        return "Perbandingan total tagihan dan pesanan {$periodLabel}";
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
