<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseInvoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class PurchasePaymentSentChart extends ChartWidget
{
    protected ?string $heading = 'PEMBAYARAN TERKIRIM';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 8;

    protected ?string $maxHeight = '275px';


    protected $listeners = ['update-purchase-overview-filter' => '$refresh'];

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

        // Approximate payment sent logic
        $data = [];
        foreach ($months as $date) {
            $sent = PurchaseInvoice::whereMonth('date', $date->month)
                ->whereYear('date', $date->year)
                ->get()
                ->sum(fn($inv) => $inv->total_amount - $inv->balance_due);
            $data[] = $sent;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pembayaran Terkirim',
                    'data' => $data,
                    'borderColor' => '#3b82f6',
                    'fill' => false,
                    'tension' => 0.4,
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
        ];
    }

    public function getDescription(): ?string
    {
        $filter = request()->query('filter', 'year');
        $periodLabel = $filter === 'year' ? 'Tahun Ini' : 'Bulan Ini';
        return "Tren pembayaran yang telah dikirim {$periodLabel}";
    }

    protected function getType(): string
    {
        return 'line';
    }
}
