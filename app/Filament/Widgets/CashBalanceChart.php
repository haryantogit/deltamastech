<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\JournalItem;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class CashBalanceChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'KAS';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $filters = $this->filters ?? request()->input('filters') ?? [];
        $startDate = $filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $filters['endDate'] ?? now()->endOfYear()->toDateString();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Try to find the "Kas" account
        $account = Account::where('name', 'Kas')
            ->orWhere('code', '1-10001')
            ->first();

        $data = [];
        $labels = [];

        if (!$account) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $current = $start->copy()->startOfMonth();
        while ($current->lte($end)) {
            $monthEnd = $current->copy()->endOfMonth();
            if ($monthEnd->gt($end))
                $monthEnd = $end->copy();

            $labels[] = $current->format('M Y');

            $runningBalance = $account->opening_balance ?? 0;

            // Total changes up to the end of this month
            $runningBalance += JournalItem::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($query) use ($monthEnd) {
                    $query->where('transaction_date', '<=', $monthEnd);
                })
                ->sum(\DB::raw('debit - credit'));

            $data[] = (float) $runningBalance;
            $current->addMonth();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Saldo Kas',
                    'data' => $data,
                    'fill' => true,
                    'backgroundColor' => 'rgba(234, 179, 8, 0.2)', // Yellow-500 with opacity
                    'borderColor' => '#eab308', // Yellow-500
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line'; // Area chart is a line chart with 'fill' => true
    }
}
