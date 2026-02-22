<?php
echo 'Total Orders: ' . \App\Models\SalesOrder::count() . PHP_EOL;
echo 'Total Invoices: ' . \App\Models\SalesInvoice::count() . PHP_EOL;
echo 'Invoices with SO: ' . \App\Models\SalesInvoice::whereNotNull('sales_order_id')->count() . PHP_EOL;

$so = \App\Models\SalesOrder::whereNotNull('notes')->first();
echo 'Sample SO Note: [' . ($so ? $so->notes : 'N/A') . '] Number: [' . ($so ? $so->number : 'N/A') . ']' . PHP_EOL;

// Check specifically for a PO Ref we saw in the CSV: "PO.2025.12.00027"
$targetSo = \App\Models\SalesOrder::where('notes', 'PO.2025.12.00027')->first();
echo 'Checking specific PO PO.2025.12.00027: ' . ($targetSo ? 'Found (ID: ' . $targetSo->id . ')' : 'Not Found') . PHP_EOL;

$inv = \App\Models\SalesInvoice::where('invoice_number', 'INV/02695')->first();
echo 'Target Invoice INV/02695: ' . ($inv ? $inv->invoice_number : 'N/A') . ' SO ID: ' . ($inv ? $inv->sales_order_id : 'NULL') . PHP_EOL;
