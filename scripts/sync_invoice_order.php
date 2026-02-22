<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SalesInvoice;
use App\Models\SalesOrder;

echo "--- PHASE 1: Linking Orphaned Invoices ---" . PHP_EOL;

$orphans = SalesInvoice::whereNull('sales_order_id')->get();
$linkedCount = 0;

foreach ($orphans as $invoice) {
    // 1. Try exact match on reference
    $ref = $invoice->reference;
    if ($ref) {
        $order = SalesOrder::where('number', $ref)
            ->orWhere('reference', $ref)
            ->orWhere('tracking_number', $ref)
            ->first();

        if (!$order && is_numeric($ref) && str_contains($ref, 'E+')) {
            // Handle scientific notation
            $plainRef = (string) number_format((float) $ref, 0, '', '');
            $order = SalesOrder::where('reference', 'like', $plainRef . '%')->first();
        }

        if ($order) {
            $invoice->sales_order_id = $order->id;
            $invoice->save();
            $linkedCount++;
            continue;
        }
    }

    // 2. Try match on customer_id + total_amount
    $order = SalesOrder::where('customer_id', $invoice->contact_id)
        ->whereBetween('total_amount', [$invoice->total_amount - 1, $invoice->total_amount + 1])
        ->whereDoesntHave('invoices')
        ->first();

    if ($order) {
        $invoice->sales_order_id = $order->id;
        $invoice->save();
        $linkedCount++;
    }
}

echo "Linked $linkedCount previously orphaned invoices." . PHP_EOL;

echo "--- PHASE 2: Synchronizing Statuses and Balances ---" . PHP_EOL;

$invoices = SalesInvoice::whereNotNull('sales_order_id')->get();
$total = $invoices->count();
$updated = 0;

foreach ($invoices as $index => $invoice) {
    $order = $invoice->salesOrder;
    if (!$order)
        continue;

    $changed = false;

    // If invoice is paid, order balance_due should be 0 and status should be completed
    if ($invoice->status === 'paid') {
        if ($order->balance_due > 0) {
            $order->balance_due = 0;
            $changed = true;
        }
        if ($order->status !== 'completed') {
            $order->status = 'completed';
            $changed = true;
        }
    } elseif ($invoice->status === 'unpaid') {
        $expected_balance = $order->total_amount - $order->down_payment;
        if (abs($order->balance_due - $expected_balance) > 0.01) {
            $order->balance_due = $expected_balance;
            $changed = true;
        }
    }

    if ($changed) {
        $order->save();
        $updated++;
    }

    if (($index + 1) % 500 == 0) {
        echo "Processed " . ($index + 1) . "/$total records..." . PHP_EOL;
    }
}

echo "Done! Linked $linkedCount invoices and updated $updated Sales Orders." . PHP_EOL;
