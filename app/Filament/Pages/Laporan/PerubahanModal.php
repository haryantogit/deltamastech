<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Account;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;

class PerubahanModal extends Page
{
    use HasFiltersForm;
    public string $statsFilter = 'bulan';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.laporan.perubahan-modal';

    protected static ?string $title = 'Perubahan Modal';

    protected static ?string $slug = 'perubahan-modal';

    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        $this->filters = [
            'startDate' => now()->startOfYear()->toDateString(),
            'endDate' => now()->toDateString(),
            'compare_periods' => 0,
        ];
    }

    public function filtersForm(Schema $form): Schema
    {
        return $form
            ->schema([
                //
            ])
            ->columns(1)
            ->statePath('filters');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Perubahan Modal',
        ];
    }

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $startDate = $this->filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $this->filters['endDate'] ?? now()->toDateString();
        $startFmt = \Carbon\Carbon::parse($startDate)->format('d/m/Y');
        $endFmt = \Carbon\Carbon::parse($endDate)->format('d/m/Y');

        $dateDisplay = $startFmt === $endFmt
            ? $startFmt
            : $startFmt . ' &mdash; ' . $endFmt;

        return new \Illuminate\Support\HtmlString('
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background-color: #f8fafc; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; font-size: 0.875rem; font-weight: 600; color: #475569;" class="dark:bg-white/5 dark:border-white/10 dark:text-gray-300">
                <svg style="width: 1.25rem; height: 1.25rem; opacity: 0.7;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>' . $dateDisplay . '</span>
            </div>
        ');
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
            Action::make('filter')
                ->label('Filter')
                ->icon('heroicon-m-funnel')
                ->color('gray')
                ->form([
                    DatePicker::make('startDate')
                        ->hiddenLabel()
                        ->default($this->filters['startDate'])
                        ->required(),
                    DatePicker::make('endDate')
                        ->hiddenLabel()
                        ->default($this->filters['endDate'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->filters['startDate'] = $data['startDate'];
                    $this->filters['endDate'] = $data['endDate'];
                    $this->statsFilter = 'custom';
                }),
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
        $startDate = $this->filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $this->filters['endDate'] ?? now()->toDateString();
        $comparePeriods = (int) ($this->filters['compare_periods'] ?? 3);

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $periodLength = $start->diffInDays($end);

        // Build periods: current + comparison periods going backwards
        $periods = [];
        $currentStart = $start->copy();
        $currentEnd = $end->copy();

        for ($i = 0; $i <= $comparePeriods; $i++) {
            $label = $currentStart->isSameDay($currentEnd)
                ? $currentEnd->format('d/m/Y')
                : $currentStart->format('d/m/Y') . ' - ' . $currentEnd->format('d/m/Y');

            $periods[] = [
                'label' => $label,
                'start' => $currentStart->copy(),
                'end' => $currentEnd->copy(),
            ];
            // Shift backwards by the same period length
            $currentEnd = $currentStart->copy()->subDay();
            $currentStart = $currentEnd->copy()->subDays($periodLength);
        }

        // Equity accounts
        $accounts = Account::where('category', 'Ekuitas')
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('code')
            ->get();

        // Build data for each period
        $periodSections = [];
        foreach ($periods as $period) {
            $rows = [];
            $totalAwal = 0;
            $totalDebit = 0;
            $totalCredit = 0;
            $totalAkhir = 0;

            foreach ($accounts as $account) {
                $childIds = $account->children->pluck('id')->toArray();
                $allIds = array_merge([$account->id], $childIds);

                $awal = (float) JournalItem::whereIn('account_id', $allIds)
                    ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<', $period['start']))
                    ->sum(DB::raw('credit - debit'));

                $debit = (float) JournalItem::whereIn('account_id', $allIds)
                    ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$period['start'], $period['end']]))
                    ->sum('debit');

                $credit = (float) JournalItem::whereIn('account_id', $allIds)
                    ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$period['start'], $period['end']]))
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

            $periodSections[] = [
                'label' => $period['label'],
                'rows' => $rows,
                'totalAwal' => $totalAwal,
                'totalDebit' => $totalDebit,
                'totalCredit' => $totalCredit,
                'totalAkhir' => $totalAkhir,
                'pergerakan' => $pergerakan,
            ];
        }

        return [
            'today' => $end->format('d/m/Y'),
            'periodSections' => $periodSections,
        ];
    }
}

