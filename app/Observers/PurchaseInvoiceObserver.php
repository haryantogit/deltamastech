<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\PurchaseInvoice;

class PurchaseInvoiceObserver
{
    /**
     * Handle the PurchaseInvoice "created" event.
     */
    public function created(PurchaseInvoice $purchaseInvoice): void
    {
        // 1. Handle Stock Update for Direct Purchases (No PO)
        if (is_null($purchaseInvoice->purchase_order_id)) {
            $purchaseInvoice->load('items');
            foreach ($purchaseInvoice->items as $item) {
                if ($item->product && $item->product->track_inventory) {
                    \App\Services\StockService::updateStock(
                        productId: $item->product_id,
                        warehouseId: $purchaseInvoice->warehouse_id ?? 1,
                        quantity: $item->quantity,
                        type: 'purchase',
                        referenceType: PurchaseInvoice::class,
                        referenceId: $purchaseInvoice->id,
                        description: "Pembelian Langsung #{$purchaseInvoice->number}"
                    );
                }
            }
        }

        // 2. Journal Entries
        $this->handleJournalEntry($purchaseInvoice);
    }

    /**
     * Handle the PurchaseInvoice "updated" event.
     */
    public function updated(PurchaseInvoice $purchaseInvoice): void
    {
        $this->handleJournalEntry($purchaseInvoice);
    }

    /**
     * Handle the PurchaseInvoice "deleting" event.
     */
    public function deleting(PurchaseInvoice $purchaseInvoice): void
    {
        // 1. Handle Stock Reversal for Direct Purchases
        if (is_null($purchaseInvoice->purchase_order_id)) {
            $purchaseInvoice->load('items');
            foreach ($purchaseInvoice->items as $item) {
                if ($item->product && $item->product->track_inventory) {
                    \App\Services\StockService::updateStock(
                        productId: $item->product_id,
                        warehouseId: $purchaseInvoice->warehouse_id ?? 1,
                        quantity: -($item->quantity),
                        type: 'purchase',
                        referenceType: PurchaseInvoice::class,
                        referenceId: $purchaseInvoice->id,
                        description: "Pembatalan Pembelian Langsung #{$purchaseInvoice->number}"
                    );
                }
            }
        }

        // 2. Delete Journal Entry
        $journalEntry = JournalEntry::where('reference_number', $purchaseInvoice->number)->first();
        if ($journalEntry) {
            $journalEntry->items()->delete();
            $journalEntry->delete();
        }

        // 3. Delete Debt
        $debt = \App\Models\Debt::where('reference', $purchaseInvoice->number)->first();
        if ($debt) {
            $debt->delete();
        }
    }

    protected function handleJournalEntry(PurchaseInvoice $invoice): void
    {
        $this->handleDebt($invoice);

        // Only process if status is valid for journaling
        if (!in_array(strtolower($invoice->status), ['posted', 'received', 'approved'])) {
            return;
        }

        // Prevent duplicate entries by removing old one for this reference
        $existingEntry = JournalEntry::where('reference_number', $invoice->number)->first();
        if ($existingEntry) {
            // Delete associated items first (cascade usually handles this but safety first)
            $existingEntry->items()->delete();
            $existingEntry->delete();
        }

        // Calculate Total Taxes and Amounts from Items
        $items = $invoice->items()->with('product')->get();
        if ($items->isEmpty()) {
            return;
        }

        // Create Journal Entry
        $journalEntry = JournalEntry::create([
            'transaction_date' => $invoice->date ?? now(),
            'reference_number' => $invoice->number,
            'description' => 'Purchase Invoice #' . $invoice->number,
            'total_amount' => $invoice->total_amount,
        ]);

        // 1. CREDIT: Accounts Payable (Hutang Usaha)
        // Find default AP account (usually starts with 2-10001)
        $apAccount = Account::where('code', '2-10001')->first() ?? Account::where('name', 'like', '%Hutang%')->first();

        $apAccountId = $apAccount?->id;

        if ($apAccountId) {
            JournalItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $apAccountId,
                'debit' => 0,
                'credit' => $invoice->total_amount, // Total amount including tax
            ]);
        }

        // 2. DEBIT: Inventory or Expense
        foreach ($items as $item) {
            $product = $item->product;
            if (!$product)
                continue;

            // Determine Debit Account
            // If tracking inventory -> Inventory Account
            // If NOT tracking inventory -> Purchase Account (Expense)
            $debitAccountId = null;

            if ($product->track_inventory) {
                $debitAccountId = $product->inventory_account_id;
            } else {
                $debitAccountId = $product->purchase_account_id;
            }

            // Fallback if specific account not set on product
            if (!$debitAccountId) {
                // Try to find a default Purchase/Inventory account
                $debitAccountId = Account::where('code', '1-10003')->value('id'); // Default Persediaan
            }

            if ($debitAccountId) {
                // Use item total.
                JournalItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $debitAccountId,
                    'debit' => $item->total_price,
                    'credit' => 0,
                ]);
            }
        }
    }

    protected function handleDebt(PurchaseInvoice $invoice): void
    {
        // Only create/update Debt if invoice is in a valid state
        // We use 'reference' (Debt) = 'number' (Invoice) to link them

        $validStatuses = ['approved', 'unpaid', 'paid', 'partial', 'overdue'];

        // Map PI status to Debt status if needed, or just proceed if active
        // If status is draft, we might want to delete debt? For now, we only update if approved/active.
        if (in_array($invoice->payment_status ?? $invoice->status, $validStatuses)) {
            \App\Models\Debt::updateOrCreate(
                ['reference' => $invoice->number],
                [
                    'supplier_id' => $invoice->supplier_id,
                    'number' => $invoice->number, // Mirror number
                    'date' => $invoice->date,
                    'due_date' => $invoice->due_date,
                    'total_amount' => $invoice->total_amount,
                    'notes' => $invoice->notes,
                    'payment_status' => $invoice->payment_status ?? 'unpaid',
                    'status' => 'posted', // Active debt
                ]
            );
        }

        $this->updatePurchaseOrderStatus($invoice);
    }

    protected function updatePurchaseOrderStatus(PurchaseInvoice $invoice): void
    {
        $purchaseOrder = $invoice->purchaseOrder;
        if (!$purchaseOrder) {
            return;
        }

        // Refresh PO to ensure we have latest relation state if needed, 
        // though strictly we need to query the database for all sibling invoices.

        $invoices = $purchaseOrder->invoices()->where('status', '!=', 'void')->get();

        if ($invoices->isEmpty()) {
            return;
        }

        $totalInvoiced = $invoices->sum('total_amount');

        // Check if ALL valid invoices are paid
        $allPaid = $invoices->every(
            fn($inv) =>
            ($inv->payment_status === 'paid') || ($inv->status === 'paid')
        );

        $newStatus = $purchaseOrder->status;

        // Logic:
        // 1. If All Paid AND Total Invoiced >= PO Total -> Paid
        // 2. Else -> Billed (or Partial Billed)
        // Kledo simple logic: If all billable items are billed and paid -> Paid.

        if ($allPaid && $totalInvoiced >= $purchaseOrder->total_amount) {
            $newStatus = 'paid'; // Lunas
        } elseif ($totalInvoiced >= $purchaseOrder->total_amount) {
            $newStatus = 'billed'; // Tagihan
        } elseif ($totalInvoiced > 0) {
            $newStatus = 'partial_billed'; // Tagihan Sebagian
        }

        // If current status is 'closed' or 'cancelled', maybe don't touch? 
        // But user says "masih tagihan", so we assume it receives updates.

        if ($newStatus !== $purchaseOrder->status) {
            $purchaseOrder->update(['status' => $newStatus]);
        }
    }
}
