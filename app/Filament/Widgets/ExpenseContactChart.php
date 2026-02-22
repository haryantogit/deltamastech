<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ExpenseContactChart extends ChartWidget
{
    protected ?string $heading = 'BIAYA';
    protected ?string $maxHeight = '250px';
    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $topContacts = DB::table('expenses')
            ->select('contacts.name', DB::raw('SUM(total_amount) as total'))
            ->join('contacts', 'expenses.contact_id', '=', 'contacts.id')
            ->groupBy('contacts.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $labels = $topContacts->pluck('name')->toArray();
        $data = $topContacts->pluck('total')->toArray();

        $colors = [
            '#6366f1', // Indigo
            '#f59e0b', // Amber
            '#10b981', // Emerald
            '#f43f5e', // Rose
            '#8b5cf6', // Violet
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Total Biaya',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderColor' => 'white',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { 
                            let label = context.label || "";
                            if (label) { label += ": "; }
                            if (context.parsed !== null) {
                                label += new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(context.parsed);
                            }
                            return label;
                        }',
                    ],
                ],
            ],
            'cutout' => '80%',
        ];
    }
}
