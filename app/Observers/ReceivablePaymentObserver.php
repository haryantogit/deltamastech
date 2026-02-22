<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\ReceivablePayment;
use App\Models\JournalEntry;
use App\Models\JournalItem;

class ReceivablePaymentObserver
{
    /**
     * Handle the ReceivablePayment "created" event.
     */
    public function created(ReceivablePayment $receivablePayment): void
    {
        $this->handleJournalEntry($receivablePayment);
        $this->syncInvoiceStatus($receivablePayment);
    }

    /**
     * Handle the ReceivablePayment "updated" event.
     */
    public function updated(ReceivablePayment $receivablePayment): void
    {
        $this->handleJournalEntry($receivablePayment);
        $this->syncInvoiceStatus($receivablePayment);
    }

    /**
     * Handle the ReceivablePayment "deleted" event.
     */
    public function deleted(ReceivablePayment $receivablePayment): void
    {
        $existingEntry = JournalEntry::where('reference_number', $receivablePayment->number)->first();
        if ($existingEntry) {
            $existingEntry->items()->delete();
            $existingEntry->delete();
        }
        $this->syncInvoiceStatus($receivablePayment);
    }

    protected function handleJournalEntry(ReceivablePayment $payment): void
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
            'description' => 'Penerimaan Penjualan ' . $payment->number,
            'total_amount' => $payment->amount,
        ]);

        // 1. DEBIT: Payment Account (Cash/Bank) - Increase Asset
        if ($payment->account_id) {
            JournalItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $payment->account_id,
                'debit' => $payment->amount,
                'credit' => 0,
            ]);
        }

        // 2. CREDIT: Accounts Receivable (Piutang Usaha) - Decrease Asset
        // Find default AR account (1-10002) or first account with 'Piutang'
        $arAccount = Account::where('code', '1-10002')->first() ?? Account::where('name', 'like', '%Piutang%')->first();

        if ($arAccount) {
            JournalItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $arAccount->id,
                'debit' => 0,
                'credit' => $payment->amount,
            ]);
        }
    }

    protected function syncInvoiceStatus(ReceivablePayment $payment): void
    {
        $receivable = $payment->receivable;
        if (!$receivable)
            return;

        $invoice = \App\Models\SalesInvoice::where('invoice_number', $receivable->invoice_number)->first();
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
