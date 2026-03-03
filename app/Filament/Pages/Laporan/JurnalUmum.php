<?php

namespace App\Filament\Pages\Laporan;

use App\Models\JournalEntry;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Livewire\WithPagination;

class JurnalUmum extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    // Standardizing pagination view

    protected string $view = 'filament.pages.laporan.jurnal-umum';

    protected static ?string $title = 'Jurnal Umum';

    protected static ?string $slug = 'jurnal-umum';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public $perPage = 10;

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }
    public function updatedEndDate(): void
    {
        $this->resetPage();
    }
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Jurnal Umum',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $startDate = $this->startDate ?? now()->startOfYear()->toDateString();
        $endDate = $this->endDate ?? now()->toDateString();
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter')
                ->label('Filter')
                ->icon('heroicon-m-funnel')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('startDate')
                        ->hiddenLabel()
                        ->default($this->startDate)
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('endDate')
                        ->hiddenLabel()
                        ->default($this->endDate)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->startDate = $data['startDate'];
                    $this->endDate = $data['endDate'];
                    $this->resetPage();
                }),
            Action::make('print')
                ->label('Print')
                ->color('gray')
                ->icon('heroicon-o-printer')
                ->action(fn() => $this->js('window.print()')),
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(\App\Filament\Pages\ReportPage::getUrl()),
        ];
    }

    public function getViewData(): array
    {
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        $query = JournalEntry::whereBetween('transaction_date', [$this->startDate, $this->endDate])
            ->with(['items.account'])
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc');

        $perPageCount = $this->perPage === 'all' ? max(1, (clone $query)->count()) : $this->perPage;
        $paginator = $query->paginate($perPageCount);

        // Stats Calculation (Current Month vs Last Month)
        // Current Range (User Selected)
        $currentStats = DB::table('journal_items')
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->whereBetween('journal_entries.transaction_date', [$this->startDate, $this->endDate])
            ->selectRaw('SUM(debit) as total_debit, COUNT(DISTINCT journal_entry_id) as entry_count, COUNT(journal_items.id) as item_count')
            ->first();

        // Comparison Range (Same number of days before startDate)
        $daysCount = $start->diffInDays($end) + 1;
        $prevStart = $start->copy()->subDays($daysCount)->format('Y-m-d');
        $prevEnd = $start->copy()->subDay()->format('Y-m-d');

        $prevStats = DB::table('journal_items')
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->whereBetween('journal_entries.transaction_date', [$prevStart, $prevEnd])
            ->selectRaw('SUM(debit) as total_debit, COUNT(DISTINCT journal_entry_id) as entry_count, COUNT(journal_items.id) as item_count')
            ->first();

        $calculateTrend = function ($current, $previous) {
            if ($previous == 0) {
                return ['pct' => $current > 0 ? 100 : 0, 'icon' => 'heroicon-m-arrow-trending-up', 'color' => 'success'];
            }
            $pct = round((($current - $previous) / $previous) * 100, 1);
            return [
                'pct' => $pct,
                'icon' => $pct >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down',
                'color' => $pct >= 0 ? 'success' : 'danger',
            ];
        };

        $filterLabel = 'vs periode sebelumnya';

        return [
            'entries' => $paginator->items(),
            'paginator' => $paginator,
            'totalCount' => $paginator->total(),
            'filterLabel' => $filterLabel,
            'stats' => [
                'nilai' => [
                    'label' => 'Nilai Transaksi',
                    'value' => (float) ($currentStats->total_debit ?? 0),
                    'trend' => $calculateTrend((float) $currentStats->total_debit, (float) $prevStats->total_debit),
                ],
                'jumlah' => [
                    'label' => 'Jumlah Transaksi',
                    'value' => (int) ($currentStats->entry_count ?? 0),
                    'trend' => $calculateTrend((int) $currentStats->entry_count, (int) $prevStats->entry_count),
                ],
                'baris' => [
                    'label' => 'Baris Jurnal',
                    'value' => (int) ($currentStats->item_count ?? 0),
                    'trend' => $calculateTrend((int) $currentStats->item_count, (int) $prevStats->item_count),
                ],
                'totalKredit' => (float) ($currentStats->total_credit ?? 0), // Kept for balance check if needed
            ],
            // For the balance card in table footer if needed
            'totalDebit' => (float) ($currentStats->total_debit ?? 0),
            'totalCredit' => DB::table('journal_items')
                ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                ->whereBetween('journal_entries.transaction_date', [$this->startDate, $this->endDate])
                ->sum('credit'),
        ];
    }
}

