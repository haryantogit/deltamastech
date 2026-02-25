<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Account;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class PerubahanModal extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.laporan.perubahan-modal';

    protected static ?string $title = 'Perubahan Modal';

    protected static ?string $slug = 'perubahan-modal';

    protected static bool $shouldRegisterNavigation = false;

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Perubahan Modal',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\PerubahanModalStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('panduan')
                ->label('Panduan')
                ->color('gray')
                ->icon('heroicon-o-question-mark-circle')
                ->url('#'),
            Action::make('ekspor')
                ->label('Ekspor')
                ->color('gray')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url('#'),
            Action::make('bagikan')
                ->label('Bagikan')
                ->color('gray')
                ->icon('heroicon-o-share')
                ->url('#'),
            Action::make('print')
                ->label('Print')
                ->color('gray')
                ->icon('heroicon-o-printer')
                ->url('#'),
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(\App\Filament\Pages\ReportPage::getUrl()),
        ];
    }

    public function getViewData(): array
    {
        $end = Carbon::now();
        $start = $end->copy()->startOfMonth();
        $today = $end->format('d/m/Y');
        $periodLabel = $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y');

        // Previous period
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->startOfMonth();

        // Equity accounts — category 'Ekuitas'
        $accounts = Account::where('category', 'Ekuitas')
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('code')
            ->get();

        $rows = [];
        $totalAwal = 0;
        $totalDebit = 0;
        $totalCredit = 0;
        $totalAkhir = 0;

        foreach ($accounts as $account) {
            // For equity: normal credit balance → credit - debit
            // Awal = balance before the period
            $awal = (float) JournalItem::where('account_id', $account->id)
                ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<', $start))
                ->sum(DB::raw('credit - debit'));

            // Also sum up children balances
            $childIds = $account->children->pluck('id')->toArray();
            if (!empty($childIds)) {
                $awal += (float) JournalItem::whereIn('account_id', $childIds)
                    ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<', $start))
                    ->sum(DB::raw('credit - debit'));
            }

            // Period debit & credit
            $allIds = array_merge([$account->id], $childIds);

            $debit = (float) JournalItem::whereIn('account_id', $allIds)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
                ->sum('debit');

            $credit = (float) JournalItem::whereIn('account_id', $allIds)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
                ->sum('credit');

            $akhir = $awal + $credit - $debit;

            $totalAwal += $awal;
            $totalDebit += $debit;
            $totalCredit += $credit;
            $totalAkhir += $akhir;

            $rows[] = [
                'code' => $account->code,
                'name' => $account->name,
                'awal' => $awal,
                'debit' => $debit,
                'credit' => $credit,
                'akhir' => $akhir,
            ];
        }

        $pergerakan = $totalCredit - $totalDebit;

        // Previous period totals for stats
        $prevTotalAkhir = (float) JournalItem::whereIn(
            'account_id',
            Account::where('category', 'Ekuitas')->pluck('id')->toArray()
        )
            ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<=', $prevEnd))
            ->sum(DB::raw('credit - debit'));

        $prevDebit = (float) JournalItem::whereIn(
            'account_id',
            Account::where('category', 'Ekuitas')->pluck('id')->toArray()
        )
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$prevStart, $prevEnd]))
            ->sum('debit');

        $prevCredit = (float) JournalItem::whereIn(
            'account_id',
            Account::where('category', 'Ekuitas')->pluck('id')->toArray()
        )
            ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$prevStart, $prevEnd]))
            ->sum('credit');

        $prevPergerakan = $prevCredit - $prevDebit;

        return [
            'today' => $today,
            'periodLabel' => $periodLabel,
            'rows' => $rows,
            'totalAwal' => $totalAwal,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'totalAkhir' => $totalAkhir,
            'pergerakan' => $pergerakan,
            'prevTotalAkhir' => $prevTotalAkhir,
            'prevPergerakan' => $prevPergerakan,
            'prevCredit' => $prevCredit,
            'prevDebit' => $prevDebit,
        ];
    }
}
