<?php

namespace App\Filament\Widgets;

use App\Models\JournalItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

use Livewire\Attributes\On;

class AccountBalanceChart extends ChartWidget
{
    public function getHeading(): ?string
    {
        return 'Aliran Kas';
    }

    public ?string $accountId = null;

    public array $filters = [];

    #[On('filtersUpdated')]
    public function updateFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public ?string $filter = null;

    protected function getFilters(): ?array
    {
        return [];
    }

    protected function getData(): array
    {
        $startDate = $this->filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $this->filters['endDate'] ?? now()->endOfYear()->toDateString();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $accountId = $this->accountId;
        if (!$accountId) {
            return ['datasets' => [], 'labels' => []];
        }

        $labels = [];
        $netData = [];
        $piutangData = []; // Uang Masuk
        $hutangData = [];  // Uang Keluar

        // Default to monthly view within the range
        $groupBy = 'month';
        $format = 'M Y';
        $period = \Carbon\CarbonPeriod::create($start->copy()->startOfMonth(), '1 month', $end->copy()->endOfMonth());

        // Running balance for Net Line
        // We will show Net Change per period (Cash Flow) instead of Running Balance
        // This makes the Net line align with the Bars (which are monthly totals)

        // Fetch Data
        // Year/Month view (aggregated by month)
        $data = JournalItem::where('account_id', $accountId)
            ->whereHas('journalEntry', function (Builder $query) use ($start, $end) {
                $query->whereBetween('transaction_date', [$start, $end]);
            })
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->selectRaw('YEAR(journal_entries.transaction_date) as year, MONTH(journal_entries.transaction_date) as month, SUM(debit) as debit, SUM(credit) as credit')
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
                    'label' => 'Uang Masuk',
                    'data' => $piutangData,
                    'type' => 'bar',
                    'backgroundColor' => '#2dd4bf', // Teal/Cyan
                    'borderColor' => '#2dd4bf',
                    'barThickness' => 10,
                    'order' => 2,
                ],
                [
                    'label' => 'Uang Keluar',
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
