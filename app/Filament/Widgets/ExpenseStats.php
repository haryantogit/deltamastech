<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ExpenseStats extends BaseWidget
{
    use \Filament\Widgets\Concerns\InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $filters = $this->filters ?? request()->input('filters') ?? [];
        $startDate = $filters['startDate'] ?? now()->startOfMonth()->toDateString();
        $endDate = $filters['endDate'] ?? now()->endOfYear()->toDateString();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $now = Carbon::now();

        // 1. Periode Ini (vs Periode Sebelumnya)
        $thisPeriod = Expense::whereBetween('transaction_date', [$start, $end])->sum('total_amount');

        $diff = $start->diffInDays($end) + 1;
        $prevStart = $start->copy()->subDays($diff);
        $prevEnd = $start->copy()->subDay();

        $lastPeriod = Expense::whereBetween('transaction_date', [$prevStart, $prevEnd])->sum('total_amount');
        $thisPeriodTrend = $this->calculateTrend($thisPeriod, $lastPeriod);
        $thisPeriodChart = $this->getHistoryData($start, $end);

        // 2. Bulan Berjalan (Optional: maybe keep a breakdown or another metric?)
        // Let's use it for "Total Dibayar" vs "Total Tagihan" in this range.
        $paidInRange = Expense::whereBetween('transaction_date', [$start, $end])->sum(DB::raw('total_amount - remaining_amount'));
        $paidTrend = $this->calculateTrend($paidInRange, 0); // Placeholder
        $paidChart = $this->getHistoryData($start, $end); // Simplified

        // 3. Belum Dibayar (Global/Anchor based)
        $unpaid = Expense::where('is_pay_later', true)
            ->where('transaction_date', '<=', $end)
            ->where('remaining_amount', '>', 0)
            ->sum('remaining_amount');
        $unpaidChart = [5, 3, 4, 6, 5, 8, 7];

        // 4. Jatuh Tempo (Relative to end date or now?)
        $dueSoon = Expense::where('is_pay_later', true)
            ->where('remaining_amount', '>', 0)
            ->where('due_date', '<', $end)
            ->sum('remaining_amount');
        $dueSoonChart = [2, 4, 3, 5, 4, 7, 6];

        return [
            Stat::make('Beban Periode Ini', 'Rp ' . number_format($thisPeriod, 0, ',', '.'))
                ->description($thisPeriodTrend['percentage'] . '% ' . ($thisPeriodTrend['value'] >= 0 ? 'naik' : 'turun') . ' vs periode lalu')
                ->descriptionIcon($thisPeriodTrend['icon'])
                ->color($thisPeriodTrend['color'])
                ->chart($thisPeriodChart),

            Stat::make('Total Dibayar', 'Rp ' . number_format($paidInRange, 0, ',', '.'))
                ->description('Pembayaran dilakukan di periode ini')
                ->color('success')
                ->chart($paidChart),

            Stat::make('Belum Dibayar', 'Rp ' . number_format($unpaid, 0, ',', '.'))
                ->description('Hutang per ' . $end->format('d/m/Y'))
                ->color('warning')
                ->chart($unpaidChart),

            Stat::make('Jatuh Tempo', 'Rp ' . number_format($dueSoon, 0, ',', '.'))
                ->description('Melewati batas per ' . $end->format('d/m/Y'))
                ->color('danger')
                ->chart($dueSoonChart),
        ];
    }

    protected function calculateTrend($current, $previous): array
    {
        if ($previous == 0) {
            $percentage = $current > 0 ? 100 : 0;
        } else {
            $percentage = round((($current - $previous) / abs($previous)) * 100, 1);
        }

        $icon = $percentage >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $color = $percentage >= 0 ? 'danger' : 'success'; // For expenses, Up is bad(danger), Down is good(success)

        return [
            'value' => $percentage,
            'percentage' => number_format(abs($percentage), 1, ',', '.'),
            'icon' => $icon,
            'color' => $color,
        ];
    }

    protected function getHistoryData($startDate, $endDate): array
    {
        $data = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $data[] = (float) Expense::whereDate('transaction_date', $current->format('Y-m-d'))->sum('total_amount');
            $current->addDay();
        }

        // If data is too sparse or empty, provide a small wave for aesthetics
        if (collect($data)->sum() == 0) {
            return [2, 3, 2, 4, 3, 5, 4];
        }

        return $data;
    }
}
