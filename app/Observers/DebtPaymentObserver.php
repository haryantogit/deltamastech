<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\DebtPayment;
use App\Models\JournalEntry;
use App\Models\JournalItem;

class DebtPaymentObserver
{
    /**
     * Handle the DebtPayment "created" event.
     */
    public function created(DebtPayment $debtPayment): void
    {
        $this->handleJournalEntry($debtPayment);
        $this->syncInvoiceStatus($debtPayment);
    }

    /**
     * Handle the DebtPayment "updated" event.
     */
    public function updated(DebtPayment $debtPayment): void
    {
        $this->handleJournalEntry($debtPayment);
        $this->syncInvoiceStatus($debtPayment);
    }

    /**
     * Handle the DebtPayment "deleted" event.
     */
    public function deleted(DebtPayment $debtPayment): void
    {
        $existingEntry = JournalEntry::where('reference_number', $debtPayment->number)->first();
        if ($existingEntry) {
            $existingEntry->items()->delete();
            $existingEntry->delete();
        }
        $this->syncInvoiceStatus($debtPayment);
    }

    protected function handleJournalEntry(DebtPayment $payment): void
    {
        // Prevent duplicate entries by removing old one for this reference
        $existingEntry = JournalEntry::where('reference_number', $payment->number)->first();
        if ($existingEntry) {
            $existingEntry->items()->delete();
            $existingEntry->delete();
        }

        // Create Journal Entry
        $journalEntry = JournalEntry::create([
            'transaction_date' => $payment->date,
            'reference_number' => $payment->number,
            'description' => 'Pembayaran Pembelian ' . $payment->number,
            'total_amount' => $payment->amount,
        ]);

        // 1. DEBIT: Accounts Payable (Hutang Usaha) - Decrease Liability
        // Find default AP account (2-10001) or first account with 'Hutang'
        $apAccount = Account::where('code', '2-10001')->first() ?? Account::where('name', 'like', '%Hutang%')->first();

        if ($apAccount) {
            JournalItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $apAccount->id,
                'debit' => $payment->amount,
                'credit' => 0,
            ]);
        }

        // 2. CREDIT: Payment Account (Cash/Bank) - Decrease Asset
        if ($payment->account_id) {
            JournalItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $payment->account_id,
                'debit' => 0,
                'credit' => $payment->amount,
            ]);
        }
    }

    protected function syncInvoiceStatus(DebtPayment $payment): void
    {
        $debt = $payment->debt;
        if (!$debt)
            return;

        $invoice = \App\Models\PurchaseInvoice::where('number', $debt->reference)->first();
        if (!$invoice)
            return;

        $balanceDue = $invoice->balance_due;
        $totalAmount = (float) $invoice->total_amount;

        $newPaymentStatus = 'unpaid';
        if ($balanceDue <= 0) {
            $newPaymentStatus = 'paid';
        } elseif ($balanceDue < $totalAmount) {
            $newPaymentStatus = 'partial';
        }

        $invoice->payment_status = $newPaymentStatus;
        if ($newPaymentStatus === 'paid') {
            $invoice->status = 'paid';
        }
        $invoice->saveQuietly();
    }
}
