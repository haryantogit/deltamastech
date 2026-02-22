<?php

namespace App\Filament\Widgets;

use App\Models\SalesInvoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TopCustomersChart extends ChartWidget
{
    protected ?string $heading = 'PENJUALAN PER PELANGGAN';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 4;

    protected ?string $maxHeight = '275px';

    protected $listeners = ['update-sales-overview-filter' => '$refresh'];

    protected function getData(): array
    {
        $filter = request()->query('filter', 'year');
        $now = Carbon::now();

        if ($filter === 'year') {
            $start = $now->copy()->startOfYear();
            $end = $now->copy()->endOfYear();
            $periodLabel = 'Tahun Ini';
        } else {
            $start = $now->copy()->startOfMonth();
            $end = $now->copy()->endOfMonth();
            $periodLabel = 'Bulan Ini';
        }

        $dateLabel = $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y');
        $this->heading = "PENJUALAN PER PELANGGAN ({$dateLabel})";

        // Top 5 customers by total sales amount in this period
        $topCustomers = SalesInvoice::select('contact_id', DB::raw('SUM(total_amount) as total_sales'))
            ->whereBetween('transaction_date', [$start, $end])
            ->groupBy('contact_id')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->with('contact')
            ->get();

        $data = $topCustomers->pluck('total_sales')->toArray();
        $labels = $topCustomers->map(fn($item) => $item->contact->name ?? 'Unknown')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => $data,
                    'backgroundColor' => [
                        '#f43f5e',
                        '#fbbf24',
                        '#34d399',
                        '#60a5fa',
                        '#a78bfa',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => true,
            'aspectRatio' => 1.5,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        $filter = request()->query('filter', 'year');
        $periodLabel = $filter === 'year' ? 'Tahun Ini' : 'Bulan Ini';
        return "Top 5 pelanggan dengan total nilai penjualan {$periodLabel}";
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
