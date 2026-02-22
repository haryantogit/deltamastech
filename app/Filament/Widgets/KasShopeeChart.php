<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\JournalItem;
use Filament\Widgets\ChartWidget;

use Illuminate\Support\Carbon;

class KasShopeeChart extends ChartWidget
{
    use \Filament\Widgets\Concerns\InteractsWithPageFilters;

    protected ?string $heading = 'KAS SHOPEE';

    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $filters = $this->filters ?? request()->input('filters') ?? [];
        $startDate = $filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $filters['endDate'] ?? now()->endOfYear()->toDateString();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Try to find the "Kas Shopee" account
        $account = Account::where('name', 'like', '%Shopee%')
            ->first();

        $data = [];
        $labels = [];

        if ($account) {
            $current = $start->copy()->startOfMonth();
            while ($current->lte($end)) {
                $monthStart = $current->copy()->startOfMonth();
                $monthEnd = $current->copy()->endOfMonth();

                if ($monthStart->lt($start))
                    $monthStart = $start->copy();
                if ($monthEnd->gt($end))
                    $monthEnd = $end->copy();

                $labels[] = $current->format('M Y');

                $netChange = JournalItem::query()
                    ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                    ->where('account_id', $account->id)
                    ->whereBetween('journal_entries.transaction_date', [$monthStart, $monthEnd])
                    ->selectRaw('SUM(debit) - SUM(credit) as net')
                    ->value('net');

                $data[] = (float) $netChange ?? 0;
                $current->addMonth();
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Saldo Kas Shopee',
                    'data' => $data,
                    'fill' => true,
                    'backgroundColor' => 'rgba(249, 115, 22, 0.1)', // Orange tint
                    'borderColor' => '#f97316', // Orange
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
