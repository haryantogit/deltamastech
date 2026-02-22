<?php

namespace App\Filament\Widgets;

use App\Models\SalesInvoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SalesPersonPerformanceChart extends ChartWidget
{
    protected ?string $heading = 'PENJUALAN PER SALES PERSON';

    protected static ?int $sort = 6;

    protected ?string $maxHeight = '275px';

    protected int|string|array $columnSpan = 8;

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

        $this->heading = "PENJUALAN PER SALES PERSON {$periodLabel}";

        // Group sales invoices by contact (sales person from the invoice's contact/created_by)
        // Using contact as the grouping dimension for sales person performance
        $salesByPerson = SalesInvoice::select(
            'contacts.name as person_name',
            DB::raw('SUM(sales_invoices.total_amount) as total_sales')
        )
            ->join('contacts', 'contacts.id', '=', 'sales_invoices.contact_id')
            ->whereBetween('sales_invoices.transaction_date', [$start, $end])
            ->groupBy('contacts.id', 'contacts.name')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();

        $data = $salesByPerson->pluck('total_sales')->toArray();
        $labels = $salesByPerson->pluck('person_name')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Total Penjualan',
                    'data' => $data,
                    'backgroundColor' => [
                        '#f472b6',
                        '#fbbf24',
                        '#22d3ee',
                        '#818cf8',
                        '#34d399',
                        '#f43f5e',
                        '#fb923c',
                        '#a78bfa',
                        '#38bdf8',
                        '#c084fc',
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
        return "Performa penjualan berdasarkan pelanggan {$periodLabel}";
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
