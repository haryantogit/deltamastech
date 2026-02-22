<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use Filament\Widgets\ChartWidget;
use App\Models\JournalItem;
use Illuminate\Support\Carbon;

class KasPenjualanOnlineChart extends ChartWidget
{
    use \Filament\Widgets\Concerns\InteractsWithPageFilters;

    protected ?string $heading = 'KAS PENJUALAN ONLINE';

    protected static ?int $sort = 14;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $filters = $this->filters ?? request()->input('filters') ?? [];
        $startDate = $filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $filters['endDate'] ?? now()->endOfYear()->toDateString();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Try to find the account
        $account = Account::where('name', 'like', '%Penjualan Online%')->first();

        $labels = [];
        $data = [];
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
                    'label' => 'Saldo',
                    'data' => $data,
                    'fill' => true,
                    'backgroundColor' => 'rgba(244, 114, 182, 0.2)', // Pink-400 tint
                    'borderColor' => '#f472b6', // Pink-400
                    'tension' => 0.4,
                    'pointRadius' => 0,
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
