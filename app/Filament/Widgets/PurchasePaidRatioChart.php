<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseInvoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class PurchasePaidRatioChart extends ChartWidget
{
    protected ?string $heading = 'RASIO LUNAS (PEMBELIAN)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 4;

    protected ?string $maxHeight = '275px';


    protected $listeners = ['update-purchase-overview-filter' => '$refresh'];

    protected function getData(): array
    {
        $filter = request()->query('filter', 'year');
        $now = Carbon::now();

        if ($filter === 'year') {
            $startDate = $now->copy()->startOfYear();
            $endDate = $now->copy()->endOfYear();
        } else {
            $startDate = $now->copy()->startOfMonth();
            $endDate = $now->copy()->endOfMonth();
        }

        $paidCount = PurchaseInvoice::whereBetween('date', [$startDate, $endDate])
            ->where(function ($query) {
                $query->where('status', 'paid')
                    ->orWhere('payment_status', 'paid');
            })
            ->count();
        $totalCount = PurchaseInvoice::whereBetween('date', [$startDate, $endDate])->count();
        $unpaidCount = $totalCount - $paidCount;

        return [
            'datasets' => [
                [
                    'label' => 'Rasio Lunas',
                    'data' => [$paidCount, $totalCount > 0 ? $unpaidCount : 1],
                    'backgroundColor' => [
                        '#3b82f6', // Progress color (Blue)
                        '#f3f4f6', // Background color (Gray)
                    ],
                    'borderWidth' => 0,
                    'borderRadius' => 10,
                ],
            ],
            'labels' => ['Lunas', 'Belum Lunas'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => true,
            'aspectRatio' => 1.5,
            'circumference' => 180,
            'rotation' => 270,
            'cutout' => '80%',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        $filter = request()->query('filter', 'year');
        $now = Carbon::now();

        if ($filter === 'year') {
            $startDate = $now->copy()->startOfYear();
            $endDate = $now->copy()->endOfYear();
            $periodLabel = 'Tahun Ini';
        } else {
            $startDate = $now->copy()->startOfMonth();
            $endDate = $now->copy()->endOfMonth();
            $periodLabel = 'Bulan Ini';
        }

        $paidCount = PurchaseInvoice::whereBetween('date', [$startDate, $endDate])
            ->where(function ($query) {
                $query->where('status', 'paid')
                    ->orWhere('payment_status', 'paid');
            })
            ->count();
        $totalCount = PurchaseInvoice::whereBetween('date', [$startDate, $endDate])->count();
        $percentage = $totalCount > 0 ? round(($paidCount / $totalCount) * 100, 1) : 0;

        return "{$percentage}% Tagihan lunas vs total Tagihan {$periodLabel}";
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
