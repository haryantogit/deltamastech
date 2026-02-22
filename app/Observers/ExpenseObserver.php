<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\JournalItem;

class ExpenseObserver
{
    public function created(Expense $expense): void
    {
        $this->handleJournalEntry($expense);
    }

    public function updated(Expense $expense): void
    {
        $this->handleJournalEntry($expense);
    }

    public function deleted(Expense $expense): void
    {
        $existingEntry = JournalEntry::where('reference_number', $expense->reference_number)->first();
        if ($existingEntry) {
            $existingEntry->items()->delete();
            $existingEntry->delete();
        }
    }

    public function handleJournalEntry(Expense $expense): void
    {
        // Prevent duplicate entries by removing old one for this reference
        $existingEntry = JournalEntry::where('reference_number', $expense->reference_number)->first();
        if ($existingEntry) {
            $existingEntry->items()->delete();
            $existingEntry->delete();
        }

        $items = $expense->items;
        if ($items->isEmpty()) {
            return;
        }

        // Create description from expense items with custom format
        $itemDescriptions = $items->pluck('description')->filter()->implode(', ');

        if ($itemDescriptions) {
            // Format: "Pembayaran biaya: [item descriptions] [reference_number]"
            $description = "Pembayaran biaya: " . $itemDescriptions . " " . $expense->reference_number;
        } else {
            // Fallback if no item descriptions
            $description = 'Biaya #' . $expense->reference_number;
        }

        // Create memo with account information
        $memo = null;
        if ($expense->account) {
            $memo = $expense->account->name;
        }
        if ($expense->memo) {
            $memo = $memo ? $memo . ' - ' . $expense->memo : $expense->memo;
        }

        // Create Journal Entry
        $journalEntry = JournalEntry::create([
            'transaction_date' => $expense->transaction_date ?? now(),
            'reference_number' => $expense->reference_number,
            'description' => $description,
            'memo' => $memo,
            'total_amount' => $expense->total_amount,
        ]);

        // 1. CREDIT: Payment Account (Cash/Bank) or Accounts Payable
        $creditAccountId = null;
        if ($expense->remaining_amount > 0 && $expense->remaining_amount == $expense->total_amount) {
            // Unpaid -> Credit Accounts Payable
            $creditAccountId = Account::where('code', '2-10001')->value('id') ?? Account::where('name', 'like', '%Hutang%')->value('id');
        } else {
            // Paid or Partial -> Credit the selected payment account
            $creditAccountId = $expense->account_id;
        }

        if ($creditAccountId) {
            JournalItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $creditAccountId,
                'debit' => 0,
                'credit' => $expense->total_amount,
            ]);
        }

        // 2. DEBIT: Expense Accounts
        foreach ($items as $item) {
            if ($item->account_id) {
                JournalItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $item->account_id,
                    'debit' => $item->amount,
                    'credit' => 0,
                ]);
            }
        }

        // 3. Sync tags from expense to journal entry
        if ($expense->tags()->exists()) {
            $journalEntry->tags()->sync($expense->tags->pluck('id'));
        }
    }
}
