<?php

namespace App\Filament\Widgets;

use App\Models\JournalItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class AccountBalanceChart extends ChartWidget
{
    use InteractsWithPageFilters;

    public function getHeading(): ?string
    {
        return 'HUTANG & PIUTANG';
    }

    public ?string $accountId = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public ?string $filter = 'year';

    protected function getFilters(): ?array
    {
        return [
            'year' => 'Bulanan', // Shows Jan-Dec
            'month' => 'Harian', // Shows 1-31
        ];
    }

    protected function getData(): array
    {
        $startDate = $this->filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $this->filters['endDate'] ?? now()->toDateString();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $accountId = $this->accountId;
        if (!$accountId) {
            return ['datasets' => [], 'labels' => []];
        }

        $activeFilter = $this->filter;

        $labels = [];
        $netData = [];
        $piutangData = []; // Debit (Money In)
        $hutangData = [];  // Credit (Money Out)

        if ($activeFilter === 'month') {
            // Harian (Use range from global filter)
            $groupBy = 'date';
            $format = 'd';
            $period = \Carbon\CarbonPeriod::create($start, $end);
        } else {
            // Bulanan (Use range from global filter)
            $groupBy = 'month';
            $format = 'M Y';
            $period = \Carbon\CarbonPeriod::create($start->copy()->startOfMonth(), '1 month', $end->copy()->endOfMonth());
        }

        // Running balance for Net Line
        // We will show Net Change per period (Cash Flow) instead of Running Balance
        // This makes the Net line align with the Bars (which are monthly totals)

        // Fetch Data
        if ($activeFilter === 'month') {
            $data = JournalItem::where('account_id', $accountId)
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw('DATE(created_at) as date, SUM(debit) as debit, SUM(credit) as credit')
                ->groupBy('date')
                ->get();

            foreach ($period as $date) {
                $dStr = $date->format('Y-m-d');
                $row = $data->firstWhere('date', $dStr);

                $debit = $row ? (float) $row->debit : 0;
                $credit = $row ? (float) $row->credit : 0;
                $netChange = $debit - $credit;

                $labels[] = $date->format($format);
                $piutangData[] = $debit;
                $hutangData[] = $credit * -1;
                $netData[] = $netChange;
            }
        } else {
            // Year/Month view (aggregated by month)
            $data = JournalItem::where('account_id', $accountId)
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(debit) as debit, SUM(credit) as credit')
                ->groupBy('year', 'month')
                ->get();

            foreach ($period as $date) {
                $labels[] = $date->format($format);

                $row = $data->where('year', $date->year)->firstWhere('month', $date->month);
                $debit = $row ? (float) $row->debit : 0;
                $credit = $row ? (float) $row->credit : 0;
                $netChange = $debit - $credit;

                $piutangData[] = $debit;
                $hutangData[] = $credit * -1;
                $netData[] = $netChange;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Net',
                    'data' => $netData,
                    'type' => 'line',
                    'borderColor' => '#a855f7', // Purple
                    'backgroundColor' => 'transparent',
                    'pointBackgroundColor' => '#a855f7',
                    'tension' => 0.4,
                    'order' => 1, // Draw on top
                ],
                [
                    'label' => 'Piutang',
                    'data' => $piutangData,
                    'type' => 'bar',
                    'backgroundColor' => '#2dd4bf', // Teal/Cyan
                    'borderColor' => '#2dd4bf',
                    'barThickness' => 10,
                    'order' => 2,
                ],
                [
                    'label' => 'Hutang',
                    'data' => $hutangData,
                    'type' => 'bar',
                    'backgroundColor' => '#f43f5e', // Red
                    'borderColor' => '#f43f5e',
                    'barThickness' => 10,
                    'order' => 3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Mixed chart, base type bar
    }
}
