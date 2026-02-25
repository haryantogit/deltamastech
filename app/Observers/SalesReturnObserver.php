<?php

namespace App\Observers;

use App\Models\SalesReturn;
use App\Services\StockService;

class SalesReturnObserver
{
    /**
     * Handle the SalesReturn "created" event.
     */
    public function created(SalesReturn $salesReturn): void
    {
        if ($salesReturn->status === 'confirmed') {
            $this->processStockIn($salesReturn);
        }
    }

    /**
     * Handle the SalesReturn "updated" event.
     */
    public function updated(SalesReturn $salesReturn): void
    {
        if ($salesReturn->isDirty('status') && $salesReturn->status === 'confirmed') {
            $this->processStockIn($salesReturn);
        }
    }

    /**
     * Handle the SalesReturn "deleting" event.
     */
    public function deleting(SalesReturn $salesReturn): void
    {
        if ($salesReturn->status === 'confirmed') {
            // Reverse Stock In
            $salesReturn->load('items');
            foreach ($salesReturn->items as $item) {
                StockService::updateStock(
                    productId: $item->product_id,
                    warehouseId: $salesReturn->warehouse_id ?? 1,
                    quantity: -abs($item->qty), // negative to reverse the positive stock in
                    type: 'sales_return_cancel',
                    referenceType: SalesReturn::class,
                    referenceId: $salesReturn->id,
                    description: "Pembatalan Retur Penjualan #{$salesReturn->number}"
                );
            }

            // Delete Journal Entries
            $journalEntries = \App\Models\JournalEntry::where('reference_number', 'LIKE', $salesReturn->number . '%')->get();
            foreach ($journalEntries as $entry) {
                $entry->items()->delete();
                $entry->delete();
            }

            // Reverse Invoice Balance
            if ($salesReturn->sales_invoice_id) {
                $invoice = \App\Models\SalesInvoice::find($salesReturn->sales_invoice_id);
                if ($invoice) {
                    $invoice->balance_due += $salesReturn->total_amount;
                    if ($invoice->balance_due > 0 && $invoice->payment_status === 'paid') {
                        $invoice->payment_status = 'partial';
                    }
                    $invoice->save();

                    // Remove payment
                    $receivable = \App\Models\Receivable::where('reference', $invoice->invoice_number)->first();
                    if ($receivable) {
                        \App\Models\ReceivablePayment::where('reference', $salesReturn->number)->delete();
                    }
                }
            }
        }
    }

    protected function processStockIn(SalesReturn $salesReturn): void
    {
        $salesReturn->load('items');

        foreach ($salesReturn->items as $item) {
            // Positive Quantity for Sales Return (Stock In)
            $quantity = abs($item->qty);

            StockService::updateStock(
                productId: $item->product_id,
                warehouseId: $salesReturn->warehouse_id ?? 1,
                quantity: $quantity,
                type: 'sales_return',
                referenceType: SalesReturn::class,
                referenceId: $salesReturn->id,
                description: "Retur Penjualan #{$salesReturn->number}" . ($salesReturn->invoice ? " (Inv #{$salesReturn->invoice->invoice_number})" : "")
            );
        }

        $this->handleJournalEntry($salesReturn);
        $this->updateInvoiceBalance($salesReturn);
    }

    protected function handleJournalEntry(SalesReturn $return): void
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
            'description' => 'Retur Penjualan #' . $return->number,
            'total_amount' => $return->total_amount,
        ]);

        // Dr: Sales Return / Revenue (usually 4-40000)
        // Cr: Accounts Receivable (1-10002)
        $arAccountId = \App\Models\Account::where('code', '1-10002')->value('id');
        $revenueAccountId = \App\Models\Account::where('code', '4-40000')->value('id');

        if ($revenueAccountId) {
            \App\Models\JournalItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $revenueAccountId,
                'debit' => $return->total_amount,
                'credit' => 0,
            ]);
        }

        if ($arAccountId) {
            \App\Models\JournalItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $arAccountId,
                'debit' => 0,
                'credit' => $return->total_amount,
            ]);
        }

        // COGS Reversal (Dr Inventory, Cr COGS)
        $cogsTotal = 0;
        foreach ($return->items as $item) {
            $product = $item->product;
            if (!$product || !$product->track_inventory)
                continue;

            $unitCost = $product->cost_of_goods > 0 ? $product->cost_of_goods : $product->buy_price;
            $cogsTotal += ($item->qty * $unitCost);
        }

        if ($cogsTotal > 0) {
            $cogsEntry = \App\Models\JournalEntry::create([
                'transaction_date' => $return->date ?? now(),
                'reference_number' => $return->number . '-COGS',
                'description' => 'COGS Reversal #' . $return->number,
                'total_amount' => $cogsTotal,
            ]);

            $inventoryAccountId = \App\Models\Account::where('code', '1-10003')->value('id');
            $cogsAccountId = \App\Models\Account::where('code', '5-50000')->value('id');

            if ($inventoryAccountId && $cogsAccountId) {
                // Dr Inventory
                \App\Models\JournalItem::create([
                    'journal_entry_id' => $cogsEntry->id,
                    'account_id' => $inventoryAccountId,
                    'debit' => $cogsTotal,
                    'credit' => 0,
                ]);
                // Cr COGS
                \App\Models\JournalItem::create([
                    'journal_entry_id' => $cogsEntry->id,
                    'account_id' => $cogsAccountId,
                    'debit' => 0,
                    'credit' => $cogsTotal,
                ]);
            }
        }
    }

    protected function updateInvoiceBalance(SalesReturn $return): void
    {
        if ($return->sales_invoice_id) {
            $invoice = \App\Models\SalesInvoice::find($return->sales_invoice_id);
            if ($invoice) {
                $invoice->balance_due = max(0, $invoice->balance_due - $return->total_amount);
                if ($invoice->balance_due == 0) {
                    $invoice->payment_status = 'paid';
                }
                $invoice->save();

                // Update Receivable as well
                $receivable = \App\Models\Receivable::where('reference', $invoice->invoice_number)->first();
                if ($receivable) {
                    // We can log a returned amount, but simplest is to reduce the total_amount or add a payment
                    // Since it's simple, let's just create a ReceivablePayment to show the deduction, 
                    // OR we can just reduce the total_amount. Let's create a payment record for the return so it's traceable.
                    \App\Models\ReceivablePayment::create([
                        'receivable_id' => $receivable->id,
                        'payment_date' => $return->date ?? now(),
                        'amount' => $return->total_amount,
                        'payment_method_id' => null, // Or create a generic 'Return' method
                        'reference' => $return->number,
                        'notes' => 'Potongan Retur Penjualan',
                    ]);
                }
            }
        }
    }
}
