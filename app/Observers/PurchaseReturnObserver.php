<?php

namespace App\Observers;

use App\Models\PurchaseReturn;
use App\Services\StockService;

class PurchaseReturnObserver
{
    /**
     * Handle the PurchaseReturn "created" event.
     */
    public function created(PurchaseReturn $purchaseReturn): void
    {
        if ($purchaseReturn->status === 'confirmed') {
            $this->processStockOut($purchaseReturn);
        }
    }

    /**
     * Handle the PurchaseReturn "updated" event.
     */
    public function updated(PurchaseReturn $purchaseReturn): void
    {
        if ($purchaseReturn->isDirty('status') && $purchaseReturn->status === 'confirmed') {
            $this->processStockOut($purchaseReturn);
        }
    }

    /**
     * Handle the PurchaseReturn "deleting" event.
     */
    public function deleting(PurchaseReturn $purchaseReturn): void
    {
        if ($purchaseReturn->status === 'confirmed') {
            // Reverse Stock Out
            $purchaseReturn->load('items');
            foreach ($purchaseReturn->items as $item) {
                StockService::updateStock(
                    productId: $item->product_id,
                    warehouseId: $purchaseReturn->warehouse_id ?? 1,
                    quantity: abs($item->qty), // positive to reverse the negative stock out
                    type: 'purchase_return_cancel',
                    referenceType: PurchaseReturn::class,
                    referenceId: $purchaseReturn->id,
                    description: "Pembatalan Retur Pembelian #{$purchaseReturn->number}"
                );
            }

            // Delete Journal Entries
            $journalEntries = \App\Models\JournalEntry::where('reference_number', 'LIKE', $purchaseReturn->number . '%')->get();
            foreach ($journalEntries as $entry) {
                $entry->items()->delete();
                $entry->delete();
            }

            // Reverse Invoice Balance
            if ($purchaseReturn->purchase_invoice_id) {
                $invoice = \App\Models\PurchaseInvoice::find($purchaseReturn->purchase_invoice_id);
                if ($invoice) {
                    $invoice->balance_due += $purchaseReturn->total_amount;
                    if ($invoice->balance_due > 0 && $invoice->payment_status === 'paid') {
                        $invoice->payment_status = 'partial';
                    }
                    $invoice->save();

                    // Remove payment
                    $debt = \App\Models\Debt::where('reference', $invoice->number)->first();
                    if ($debt) {
                        \App\Models\DebtPayment::where('reference', $purchaseReturn->number)->delete();
                    }
                }
            }
        }
    }

    protected function processStockOut(PurchaseReturn $purchaseReturn): void
    {
        $purchaseReturn->load('items');

        foreach ($purchaseReturn->items as $item) {
            // Negative Quantity for Purchase Return (Stock Out)
            $quantity = -1 * abs($item->qty);

            StockService::updateStock(
                productId: $item->product_id,
                warehouseId: $purchaseReturn->warehouse_id ?? 1,
                quantity: $quantity,
                type: 'purchase_return',
                referenceType: PurchaseReturn::class,
                referenceId: $purchaseReturn->id,
                description: "Retur Pembelian #{$purchaseReturn->number}" . ($purchaseReturn->invoice ? " (Inv #{$purchaseReturn->invoice->number})" : "")
            );
        }

        $this->handleJournalEntry($purchaseReturn);
        $this->updateInvoiceBalance($purchaseReturn);
    }

    protected function handleJournalEntry(PurchaseReturn $return): void
    {
        // Cancel existing
        $existingEntry = \App\Models\JournalEntry::where('reference_number', $return->number)->first();
        if ($existingEntry) {
            $existingEntry->items()->delete();
            $existingEntry->delete();
        }

        if ($return->total_amount <= 0)
            return;

        // Create Journal Entry
        $journalEntry = \App\Models\JournalEntry::create([
            'transaction_date' => $return->date ?? now(),
            'reference_number' => $return->number,
            'description' => 'Retur Pembelian #' . $return->number,
            'total_amount' => $return->total_amount,
        ]);

        // Dr: Accounts Payable (Hutang Usaha) 2-10001
        // Cr: Inventory (Persediaan) 1-10003 or Purchase Account
        $apAccountId = \App\Models\Account::where('code', '2-10001')->value('id');

        $inventoryAccountId = \App\Models\Account::where('code', '1-10003')->value('id');

        if ($apAccountId) {
            \App\Models\JournalItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $apAccountId,
                'debit' => $return->total_amount,
                'credit' => 0,
            ]);
        }

        // Technically, we should reverse per item based on their inventory/purchase account
        // Since PurchaseInvoiceObserver uses $product->inventory_account_id or purchase_account_id,
        // we'll loop through items:
        foreach ($return->items as $item) {
            $product = $item->product;
            if (!$product)
                continue;

            $creditAccountId = $product->track_inventory
                ? ($product->inventory_account_id ?? $inventoryAccountId)
                : ($product->purchase_account_id ?? $inventoryAccountId);

            if ($creditAccountId) {
                \App\Models\JournalItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $creditAccountId,
                    'debit' => 0,
                    'credit' => $item->total_price ?? ($item->qty * $item->unit_price), // approximate per line
                ]);
            }
        }
    }

    protected function updateInvoiceBalance(PurchaseReturn $return): void
    {
        if ($return->purchase_invoice_id) {
            $invoice = \App\Models\PurchaseInvoice::find($return->purchase_invoice_id);
            if ($invoice) {
                // Determine new balance
                $invoice->balance_due = max(0, $invoice->balance_due - $return->total_amount);
                if ($invoice->balance_due == 0) {
                    $invoice->payment_status = 'paid';
                }
                $invoice->save();

                // Update Debt record (Payment against it)
                $debt = \App\Models\Debt::where('reference', $invoice->number)->first();
                if ($debt) {
                    \App\Models\DebtPayment::create([
                        'debt_id' => $debt->id,
                        'payment_date' => $return->date ?? now(),
                        'amount' => $return->total_amount,
                        'payment_method_id' => null,
                        'reference' => $return->number,
                        'notes' => 'Potongan Retur Pembelian',
                    ]);
                }
            }
        }
    }
}
