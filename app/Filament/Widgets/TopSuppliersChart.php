<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseInvoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TopSuppliersChart extends ChartWidget
{
    protected ?string $heading = 'PEMBELIAN PER PEMASOK';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 4;

    protected ?string $maxHeight = '275px';

    protected $listeners = ['update-purchase-overview-filter' => '$refresh'];

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

        $this->heading = 'PEMBELIAN PER PEMASOK (' . $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y') . ')';

        // Top 5 suppliers by total purchase amount in this period
        $topSuppliers = PurchaseInvoice::select('supplier_id', DB::raw('SUM(total_amount) as total_purchase'))
            ->whereBetween('date', [$start, $end])
            ->groupBy('supplier_id')
            ->orderByDesc('total_purchase')
            ->limit(5)
            ->with('supplier')
            ->get();

        $data = $topSuppliers->pluck('total_purchase')->toArray();
        $labels = $topSuppliers->map(fn($item) => $item->supplier->name ?? 'Unknown')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Purchase',
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
        return "Top 5 pemasok dengan total nilai pembelian {$periodLabel}";
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
