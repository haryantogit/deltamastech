<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AccountTransactionsTable extends Component
{
    use WithPagination;

    public $accountId;
    public $startDate;
    public $endDate;
    public $search = '';

    public function paginationView()
    {
        return 'livewire.clean-pagination';
    }

    public function mount($accountId)
    {
        $this->accountId = $accountId;
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStartDate()
    {
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->resetPage();
    }

    public function getAccountProperty()
    {
        return Account::find($this->accountId);
    }

    public function render()
    {
        $account = $this->account;

        $debitNormalCategories = [
            'Kas & Bank',
            'Akun Piutang',
            'Persediaan',
            'Aktiva Lancar Lainnya',
            'Aktiva Tetap',
            'Depresiasi & Amortisasi',
            'Aktiva Lainnya',
            'Harga Pokok Penjualan',
            'Beban',
            'Beban Lainnya',
        ];

        $isDebitNormal = in_array($account->category, $debitNormalCategories);

        // Base Query
        $query = JournalItem::with(['journalEntry'])
            ->where('account_id', $this->accountId)
            ->whereHas('journalEntry', function (Builder $q) {
                $q->when($this->startDate, fn($q) => $q->whereDate('transaction_date', '>=', $this->startDate))
                    ->when($this->endDate, fn($q) => $q->whereDate('transaction_date', '<=', $this->endDate))
                    ->when($this->search, fn($q) => $q->where(function ($subQ) {
                        $subQ->where('description', 'like', '%' . $this->search . '%')
                            ->orWhere('reference_number', 'like', '%' . $this->search . '%');
                    }));
            })
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.transaction_date', 'desc')
            ->orderBy('journal_entries.created_at', 'desc')
            ->select('journal_items.*');

        // Opening Balance Calculation (Before Start Date)
        $openingBalanceQuery = JournalItem::where('account_id', $this->accountId)
            ->whereHas('journalEntry', function ($q) {
                $q->whereDate('transaction_date', '<', $this->startDate);
            });

        $opDebit = $openingBalanceQuery->sum('debit');
        $opCredit = $openingBalanceQuery->sum('credit');

        $openingBalance = $isDebitNormal ? ($opDebit - $opCredit) : ($opCredit - $opDebit);

        // Summaries for Footer (Visible Page vs Total?)
        // Calculate Range Totals BEFORE map to use in start balance calculation
        $totalRangeQuery = JournalItem::where('account_id', $this->accountId)
            ->whereHas('journalEntry', function (Builder $q) {
                $q->when($this->startDate, fn($q) => $q->whereDate('transaction_date', '>=', $this->startDate))
                    ->when($this->endDate, fn($q) => $q->whereDate('transaction_date', '<=', $this->endDate))
                    ->when($this->search, fn($q) => $q->where(function ($subQ) {
                        $subQ->where('description', 'like', '%' . $this->search . '%')
                            ->orWhere('reference_number', 'like', '%' . $this->search . '%');
                    }));
            });

        $totalDebit = $totalRangeQuery->sum('debit');
        $totalCredit = $totalRangeQuery->sum('credit');
        $netChange = $isDebitNormal ? ($totalDebit - $totalCredit) : ($totalCredit - $totalDebit);
        $closingBalance = $openingBalance + $netChange;

        // Pagination
        $transactions = $query->paginate(10);

        // Calculate Running Balances for the current page
        // We need the cumulative balance existing BEFORE the first item of this page, 
        // but within the selected date range + Opening Balance.

        // 1. Get all items in range BEFORE the current page's items to calculate "Page Start Balance"
        // This is tricky with simple pagination.
        // Easier way: 
        // Iterate and calculate. But standard pagination only gives slicing.

        // Alternative: Calculate running balance strictly on the Collection for view purposes, 
        // assuming we can get the "Balance at Start of Page".

        // "Balance at Start of Page" = OpeningBalance + Sum(Net) of all items matching filter BUT before offset.
        $page = $this->getPage();
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        // For DESC order:
        // Page 1 contains newest items. First row balance = closingBalance.
        // Page 2 contains items older than Page 1. First row balance = closingBalance - Sum(Net of items on Page 1).

        $totalNetInRange = $isDebitNormal ? ($totalDebit - $totalCredit) : ($totalCredit - $totalDebit);
        $rangeClosingBalance = $openingBalance + $totalNetInRange;

        $previousItemsSumQuery = JournalItem::where('account_id', $this->accountId)
            ->whereHas('journalEntry', function (Builder $q) {
                $q->when($this->startDate, fn($q) => $q->whereDate('transaction_date', '>=', $this->startDate))
                    ->when($this->endDate, fn($q) => $q->whereDate('transaction_date', '<=', $this->endDate))
                    ->when($this->search, fn($q) => $q->where(function ($subQ) {
                        $subQ->where('description', 'like', '%' . $this->search . '%')
                            ->orWhere('reference_number', 'like', '%' . $this->search . '%');
                    }));
            })
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.transaction_date', 'desc') // MUST match main query
            ->orderBy('journal_entries.created_at', 'desc')
            ->limit($offset) // Items on NEWER pages (since DESC)
            ->get();

        $prevDebit = $previousItemsSumQuery->sum('debit');
        $prevCredit = $previousItemsSumQuery->sum('credit');
        $prevNet = $isDebitNormal ? ($prevDebit - $prevCredit) : ($prevCredit - $prevDebit);

        $runningBalance = $rangeClosingBalance - $prevNet;

        // Transform transactions to include running balance
        $formattedTransactions = $transactions->getCollection()->map(function ($item) use (&$runningBalance, $isDebitNormal) {
            $net = $isDebitNormal ? ($item->debit - $item->credit) : ($item->credit - $item->debit);

            $item_balance = $runningBalance;
            $runningBalance -= $net; // Next item (older) will have this balance minus current net

            // Add custom attributes
            $item->net_amount = $net;
            $item->running_balance = $item_balance;
            $item->transaction_date = $item->journalEntry->transaction_date;
            $item->description = $item->journalEntry->description;
            // Map 'Nomor' to reference_number
            $item->nomor = $item->journalEntry->reference_number;
            // Map 'Referensi' to memo (or tags/contact if needed)
            $item->referensi = $item->journalEntry->memo;

            // Determine 'Sumber' based on reference number logic matching the screenshot
            $ref = $item->journalEntry->reference_number;
            $url = '#'; // Default no link

            if (str_starts_with($ref, 'EXP')) {
                $item->sumber = 'Biaya';
                // Find related Expense ID efficiently? 
                // For now, let's try to query. Optimal: Eager load or separate query.
                // Given pagination is small (10), separate query is okay.
                $expense = \App\Models\Expense::where('reference_number', $ref)->first();
                if ($expense) {
                    $url = \App\Filament\Resources\ExpenseResource::getUrl('view', ['record' => $expense->id]);
                }
            } elseif (str_starts_with($ref, 'INV')) {
                $item->sumber = 'Tagihan';
                // Logic for Invoice linking can be added here
            } elseif (str_starts_with($ref, 'TR')) {
                $item->sumber = 'Transfer';
            } else {
                $item->sumber = 'Jurnal Umum';
            }

            $item->transaction_url = $url;

            return $item;
        });

        $transactions->setCollection($formattedTransactions);

        // Summaries for Footer (Visible Page vs Total?)
        // Usually Totals are for the Range.
        // Let's get Range Totals.
        $netChange = $isDebitNormal ? ($totalDebit - $totalCredit) : ($totalCredit - $totalDebit);
        $closingBalance = $openingBalance + $netChange;

        return view('livewire.account-transactions-table', [
            'transactions' => $transactions,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
        ]);
    }
}
