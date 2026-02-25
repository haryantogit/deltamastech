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
            ->orderBy('journal_entries.transaction_date', 'asc')
            ->orderBy('journal_entries.created_at', 'asc')
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
        $transactions = $query->paginate(30); // Increased for better view like screenshot

        // Calculate Running Balances for the current page
        $page = $this->getPage();
        $perPage = 30;
        $offset = ($page - 1) * $perPage;

        // Balance at start of THIS page = OpeningBalance + Sum(Net of all items in range BEFORE this page)
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
            ->orderBy('journal_entries.transaction_date', 'asc')
            ->orderBy('journal_entries.created_at', 'asc')
            ->limit($offset)
            ->get();

        $prevDebit = $previousItemsSumQuery->sum('debit');
        $prevCredit = $previousItemsSumQuery->sum('credit');
        $prevNet = $isDebitNormal ? ($prevDebit - $prevCredit) : ($prevCredit - $prevDebit);

        $runningBalance = $openingBalance + $prevNet;

        // Transform transactions to include running balance
        $formattedTransactions = $transactions->getCollection()->map(function ($item) use (&$runningBalance, $isDebitNormal) {
            $net = $isDebitNormal ? ($item->debit - $item->credit) : ($item->credit - $item->debit);
            $runningBalance += $net;

            // Add custom attributes
            $item->net_amount = $net;
            $item->running_balance = $runningBalance;
            $item->transaction_date = $item->journalEntry->transaction_date;
            $item->description = $item->journalEntry->description;
            $item->nomor = $item->journalEntry->reference_number;
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
