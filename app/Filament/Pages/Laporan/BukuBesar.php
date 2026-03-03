<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Account;
use App\Models\JournalItem;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Illuminate\Support\Collection;

use Livewire\WithPagination;

class BukuBesar extends Page implements HasActions
{
    use InteractsWithActions;
    use WithPagination;

    // Removal of custom pagination view to use the new standardized professional view

    protected string $view = 'filament.pages.laporan.buku-besar';

    protected static ?string $title = 'Buku Besar';

    protected static ?string $slug = 'buku-besar';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $viewMode = 'compact'; // 'compact' or 'expanded'
    public array $expandedRows = []; // IDs of expanded accounts
    public $perPage = 10;

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'viewMode' => ['except' => 'compact'],
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
            'Buku Besar',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
        if ($mode === 'expanded') {
            $this->expandedRows = Account::where('is_active', true)->pluck('id')->toArray();
        } else {
            $this->expandedRows = [];
        }
        $this->resetPage();
    }

    public function toggleRow(int $accountId): void
    {
        if (in_array($accountId, $this->expandedRows)) {
            $this->expandedRows = [];
        } else {
            $this->expandedRows = [$accountId];
        }
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

        if ($this->viewMode === 'compact') {
            // COMPACT MODE: List of Accounts with Summary
            $accountQuery = Account::where('is_active', true)
                ->whereHas('journalItems')
                ->orderBy('code');

            $perPageCount = $this->perPage === 'all' ? max(1, (clone $accountQuery)->count()) : $this->perPage;
            $paginatedAccounts = $accountQuery->paginate($perPageCount, ['*'], 'page');

            $rows = [];
            $transactionPaginator = null;
            foreach ($paginatedAccounts->items() as $account) {
                // Opening Balance
                $openingBalance = (float) JournalItem::where('account_id', $account->id)
                    ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<', $start))
                    ->sum(DB::raw('debit - credit'));

                // Period Stats
                $periodStats = JournalItem::where('account_id', $account->id)
                    ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
                    ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                    ->first();

                $debit = (float) ($periodStats->total_debit ?? 0);
                $credit = (float) ($periodStats->total_credit ?? 0);
                $saldo = $openingBalance + ($debit - $credit);

                // Fetch transactions if expanded (Accordion style in Compact)
                $transactions = [];
                if (in_array($account->id, $this->expandedRows)) {
                    $transactionQuery = JournalItem::where('account_id', $account->id)
                        ->whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
                        ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                        ->select('journal_items.*')
                        ->orderBy('journal_entries.transaction_date', 'asc')
                        ->orderBy('journal_entries.id', 'asc')
                        ->with(['journalEntry']);

                    $trxPerPageCount = $this->perPage === 'all' ? max(1, (clone $transactionQuery)->count()) : $this->perPage;
                    $transactionPaginator = $transactionQuery->paginate($trxPerPageCount, ['*'], 'trxPage');

                    // Calculate running balance for the current page
                    $previousItemsBalance = (float) JournalItem::where('account_id', $account->id)
                        ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                        ->whereBetween('journal_entries.transaction_date', [$start, $end])
                        ->where(function ($query) use ($transactionPaginator) {
                            $firstItem = collect($transactionPaginator->items())->first();
                            if ($firstItem) {
                                $query->where('journal_entries.transaction_date', '<', $firstItem->journalEntry->transaction_date)
                                    ->orWhere(function ($sub) use ($firstItem) {
                                        $sub->where('journal_entries.transaction_date', '=', $firstItem->journalEntry->transaction_date)
                                            ->where('journal_entries.id', '<', $firstItem->journal_entry_id);
                                    });
                            }
                        })
                        ->sum(DB::raw('debit - credit'));

                    $runningBalance = $openingBalance + $previousItemsBalance;

                    foreach ($transactionPaginator->items() as $item) {
                        $entry = $item->journalEntry;
                        $runningBalance += ($item->debit - $item->credit);

                        $sumber = 'Jurnal Umum';
                        $ref = (string) $entry->reference_number;
                        if (str_starts_with($ref, 'EXP/'))
                            $sumber = 'Biaya';
                        elseif (str_starts_with($ref, 'TR/'))
                            $sumber = 'Transfer';
                        elseif (str_starts_with($ref, 'PI/'))
                            $sumber = 'Pembelian';
                        elseif (str_starts_with($ref, 'SI/'))
                            $sumber = 'Penjualan';
                        elseif (str_starts_with($ref, 'PAY') || str_starts_with($ref, 'PP/') || str_starts_with($ref, 'SP/'))
                            $sumber = 'Pembayaran';

                        $transactions[] = [
                            'tanggal' => Carbon::parse($entry->transaction_date)->format('d/m/Y'),
                            'sumber' => $sumber,
                            'nomor' => $entry->reference_number ?: '-',
                            'deskripsi' => $entry->description ?: '-',
                            'debit' => (float) $item->debit,
                            'kredit' => (float) $item->credit,
                            'saldo' => $runningBalance,
                        ];
                    }
                }

                $rows[] = [
                    'id' => $account->id,
                    'name' => $account->name,
                    'code' => $account->code,
                    'openingBalance' => $openingBalance,
                    'debit' => $debit,
                    'credit' => $credit,
                    'saldo' => $saldo,
                    'transactions' => $transactions,
                ];
            }

            // Global Stats
            $grandStats = JournalItem::whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();

            return [
                'rows' => $rows,
                'paginator' => $paginatedAccounts,
                'transactionPaginator' => $transactionPaginator, // Internal pagination for expanded row
                'grandTotalDebit' => (float) ($grandStats->total_debit ?? 0),
                'grandTotalCredit' => (float) ($grandStats->total_credit ?? 0),
                'totalCount' => $paginatedAccounts->total(),
            ];
        } else {
            // EXPANDED MODE: Global Transaction List
            $transactionQuery = JournalItem::whereHas('journalEntry', fn($q) => $q->whereBetween('transaction_date', [$start, $end]))
                ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                ->join('accounts', 'journal_items.account_id', '=', 'accounts.id')
                ->select('journal_items.*', 'journal_entries.transaction_date', 'journal_entries.reference_number', 'journal_entries.description as entry_desc', 'accounts.name as account_name', 'accounts.code as account_code')
                ->orderBy('accounts.code', 'asc')
                ->orderBy('journal_entries.transaction_date', 'asc')
                ->orderBy('journal_entries.id', 'asc');

            $perPageCount = $this->perPage === 'all' ? max(1, (clone $transactionQuery)->count()) : $this->perPage;
            $paginatedTransactions = $transactionQuery->paginate($perPageCount);
            $items = $paginatedTransactions->items();

            $rows = [];
            foreach ($items as $item) {
                // Calculate Running Balance for this specific item within its account
                $openingBalance = (float) JournalItem::where('account_id', $item->account_id)
                    ->whereHas('journalEntry', fn($q) => $q->where('transaction_date', '<', $start))
                    ->sum(DB::raw('debit - credit'));

                $runningBalance = $openingBalance + (float) JournalItem::where('account_id', $item->account_id)
                    ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                    ->whereBetween('journal_entries.transaction_date', [$start, $item->transaction_date])
                    ->where(function ($q) use ($item) {
                        $q->where('journal_entries.transaction_date', '<', $item->transaction_date)
                            ->orWhere('journal_items.id', '<=', $item->id);
                    })
                    ->sum(DB::raw('debit - credit'));

                // Determine Source
                $sumber = 'Jurnal Umum';
                $ref = (string) $item->reference_number;
                if (str_starts_with($ref, 'EXP/'))
                    $sumber = 'Biaya';
                elseif (str_starts_with($ref, 'TR/'))
                    $sumber = 'Transfer';
                elseif (str_starts_with($ref, 'PI/'))
                    $sumber = 'Pembelian';
                elseif (str_starts_with($ref, 'SI/'))
                    $sumber = 'Penjualan';
                elseif (str_starts_with($ref, 'PAY') || str_starts_with($ref, 'PP/') || str_starts_with($ref, 'SP/'))
                    $sumber = 'Pembayaran';

                $rows[] = [
                    'account_id' => $item->account_id,
                    'account_name' => $item->account_name,
                    'account_code' => $item->account_code,
                    'tanggal' => Carbon::parse($item->transaction_date)->format('d/m/Y'),
                    'sumber' => $sumber,
                    'deskripsi' => $item->entry_desc ?: '-',
                    'referensi' => '', // Conceptual field
                    'nomor' => $item->reference_number ?: '-',
                    'debit' => (float) $item->debit,
                    'kredit' => (float) $item->credit,
                    'saldo_berjalan' => $runningBalance,
                ];
            }

            return [
                'rows' => $rows,
                'paginator' => $paginatedTransactions,
                'totalCount' => $paginatedTransactions->total(),
            ];
        }
    }
}

