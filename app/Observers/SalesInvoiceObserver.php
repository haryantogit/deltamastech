<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\SalesInvoice;

class SalesInvoiceObserver
{
    /**
     * Handle the SalesInvoice "created" event.
     */
    public function created(SalesInvoice $salesInvoice): void
    {
        $this->handleJournalEntry($salesInvoice);
        $this->handleReceivable($salesInvoice);
    }

    /**
     * Handle the SalesInvoice "updated" event.
     */
    public function updated(SalesInvoice $salesInvoice): void
    {
        $this->handleJournalEntry($salesInvoice);
        $this->handleReceivable($salesInvoice);
    }

    protected function handleJournalEntry(SalesInvoice $invoice): void
    {
        // Only process if status is valid
        if (!in_array(strtolower($invoice->status), ['posted', 'sent', 'approved'])) {
            return;
        }

        // Prevent duplicates
        $existingEntry = JournalEntry::where('reference_number', $invoice->invoice_number)->first();
        if ($existingEntry) {
            $existingEntry->items()->delete();
            $existingEntry->delete();
        }

        $items = $invoice->items()->with('product')->get();
        if ($items->isEmpty()) {
            return;
        }

        // === 1. REVENUE ENTRY ===
        // Dr Accounts Receivable (Total)
        // Cr Sales Income (Net)

        $journalEntry = JournalEntry::create([
            'transaction_date' => $invoice->transaction_date ?? now(),
            'reference_number' => $invoice->invoice_number,
            'description' => 'Sales Invoice #' . $invoice->invoice_number,
            'total_amount' => $invoice->total_amount,
        ]);

        // 1a. DEBIT: Accounts Receivable (Piutang Usaha)
        // Default AR Account (usually 1-10002)
        $arAccount = Account::where('code', '1-10002')->first() ?? Account::where('name', 'like', '%Piutang%')->first();
        $arAccountId = $arAccount?->id;

        if ($arAccountId) {
            JournalItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $arAccountId,
                'debit' => $invoice->total_amount,
                'credit' => 0,
            ]);
        }

        // 1b. CREDIT: Sales Income (and potentially Tax)
        foreach ($items as $item) {
            $product = $item->product;
            if (!$product)
                continue;

            $creditAccountId = $product->sales_account_id;
            // Fallback
            if (!$creditAccountId) {
                $creditAccountId = Account::where('code', '4-40000')->value('id'); // Pendapatan Penjualan
            }

            if ($creditAccountId) {
                JournalItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $creditAccountId,
                    'debit' => 0,
                    'credit' => $item->total, // Assuming total line amount.
                ]);
            }
        }

        // === 2. COGS ENTRY (Cost of Goods Sold) ===
        // Dr COGS
        // Cr Inventory
        // Only for products tracking inventory

        $cogsTotal = 0;
        $cogsItems = [];

        foreach ($items as $item) {
            $product = $item->product;
            if (!$product || !$product->track_inventory)
                continue;

            // Calculate Cost Amount
            // Using 'buy_price' as proxy for cost if 'cost_of_goods' is not reliable/updating
            $unitCost = $product->cost_of_goods > 0 ? $product->cost_of_goods : $product->buy_price;
            $totalCost = $item->qty * $unitCost;

            if ($totalCost > 0) {
                $cogsTotal += $totalCost;

                $inventoryAccountId = $product->inventory_account_id ?? Account::where('code', '1-10003')->value('id');
                // HPP / COGS Account
                $cogsAccountId = Account::where('code', '5-50000')->value('id'); // HPP

                if ($inventoryAccountId && $cogsAccountId) {
                    $cogsItems[] = [
                        'cogs_account' => $cogsAccountId,
                        'inventory_account' => $inventoryAccountId,
                        'amount' => $totalCost
                    ];

                    // === UPDATE PHYSICAL STOCK ===
                    // Only deduct stock if this invoice is the primary trigger (e.g. no associated delivery yet, or as requested)
                    // For Kledo-like flow, often the Invoice OR Delivery triggers it.
                    // We will trigger it here but with a check or description to avoid double counting.
                    \App\Services\StockService::updateStock(
                        productId: $product->id,
                        warehouseId: $invoice->warehouse_id ?? 1,
                        quantity: -abs($item->qty),
                        type: 'sales',
                        referenceType: SalesInvoice::class,
                        referenceId: $invoice->id,
                        description: "Penjualan #{$invoice->invoice_number}"
                    );
                }
            }
        }

        if ($cogsTotal > 0) {
            // Create a separate Journal Entry for COGS (or separate lines if preferred, but separate entry is clearer for dates/refs)
            $cogsEntry = JournalEntry::create([
                'transaction_date' => $invoice->transaction_date ?? now(),
                'reference_number' => $invoice->invoice_number . '-COGS', // Distinct reference
                'description' => 'COGS for #' . $invoice->invoice_number,
                'total_amount' => $cogsTotal,
            ]);

            foreach ($cogsItems as $cItem) {
                // Dr COGS
                JournalItem::create([
                    'journal_entry_id' => $cogsEntry->id,
                    'account_id' => $cItem['cogs_account'],
                    'debit' => $cItem['amount'],
                    'credit' => 0,
                ]);

                // Cr Inventory
                JournalItem::create([
                    'journal_entry_id' => $cogsEntry->id,
                    'account_id' => $cItem['inventory_account'],
                    'debit' => 0,
                    'credit' => $cItem['amount'],
                ]);
            }
        }
    }
    protected function handleReceivable(SalesInvoice $invoice): void
    {
        $validStatuses = ['posted', 'sent', 'approved', 'paid', 'partial', 'overdue'];

        if (in_array($invoice->payment_status ?? $invoice->status, $validStatuses)) {
            \App\Models\Receivable::updateOrCreate(
                ['invoice_number' => $invoice->invoice_number],
                [
                    'contact_id' => $invoice->contact_id,
                    'reference' => $invoice->invoice_number,
                    'transaction_date' => $invoice->transaction_date,
                    'due_date' => $invoice->due_date,
                    'total_amount' => $invoice->total_amount,
                    'notes' => $invoice->notes,
                    'status' => 'posted',
                ]
            );
        }

        $this->updateSalesOrderStatus($invoice);
    }

    protected function updateSalesOrderStatus(SalesInvoice $invoice): void
    {
        $order = $invoice->salesOrder;
        if (!$order) {
            return;
        }

        $invoices = $order->invoices()->where('status', '!=', 'void')->get();
        if ($invoices->isEmpty()) {
            return;
        }

        $totalInvoiced = $invoices->sum('total_amount');
        $allPaid = $invoices->every(
            fn($inv) => ($inv->payment_status === 'paid') || ($inv->status === 'paid')
        );

        $newStatus = $order->status;

        if ($allPaid && $totalInvoiced >= $order->total_amount) {
            $newStatus = 'completed';
        } elseif ($totalInvoiced >= $order->total_amount) {
            $newStatus = 'invoiced';
        } elseif ($totalInvoiced > 0) {
            $newStatus = 'partial_invoiced';
        }

        if ($newStatus !== $order->status) {
            $order->update(['status' => $newStatus]);
        }
    }
}
