<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseInvoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PurchaseByWarehouseChart extends ChartWidget
{
    protected ?string $heading = 'PEMBELIAN PER GUDANG';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 8;

    protected ?string $maxHeight = '275px';


    protected $listeners = ['update-purchase-overview-filter' => '$refresh'];

    protected function getData(): array
    {
        $filter = request()->query('filter', 'year');

        $query = PurchaseInvoice::select('warehouse_id', DB::raw('SUM(total_amount) as total_amount'));

        if ($filter === 'year') {
            $query->whereYear('date', now()->year);
            $this->heading = 'PEMBELIAN PER GUDANG TAHUN INI';
        } else {
            $query->whereMonth('date', now()->month)
                ->whereYear('date', now()->year);
            $this->heading = 'PEMBELIAN PER GUDANG BULAN INI';
        }

        $data = $query->groupBy('warehouse_id')
            ->with('warehouse')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Pembelian',
                    'data' => $data->pluck('total_amount')->toArray(),
                    'backgroundColor' => ['#f472b6', '#fbbf24', '#22d3ee', '#818cf8'],
                ],
            ],
            'labels' => $data->map(fn($item) => $item->warehouse->name ?? 'Unknown')->toArray(),
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
        ];
    }

    public function getDescription(): ?string
    {
        $filter = request()->query('filter', 'year');
        $periodLabel = $filter === 'year' ? 'Tahun Ini' : 'Bulan Ini';
        return "Distribusi pembelian berdasarkan gudang {$periodLabel}";
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
