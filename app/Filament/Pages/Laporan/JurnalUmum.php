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

    protected static string $paginationView = 'filament-actions::link-pagination';

    protected string $view = 'filament.pages.laporan.jurnal-umum';

    protected static ?string $title = 'Jurnal Umum';

    protected static ?string $slug = 'jurnal-umum';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public int $perPage = 15;

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter')
                ->label('Filter')
                ->icon('heroicon-m-funnel')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('startDate')
                        ->label('Tanggal Mulai')
                        ->default($this->startDate)
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('endDate')
                        ->label('Tanggal Akhir')
                        ->default($this->endDate)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->startDate = $data['startDate'];
                    $this->endDate = $data['endDate'];
                    $this->resetPage();
                }),
            Action::make('panduan')
                ->label('Panduan')
                ->color('gray')
                ->icon('heroicon-o-question-mark-circle')
                ->url('#'),
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

        $paginator = $query->paginate($this->perPage);

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

        return [
            'entries' => $paginator->items(),
            'paginator' => $paginator,
            'totalCount' => $paginator->total(),
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
