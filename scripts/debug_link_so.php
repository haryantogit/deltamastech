<?php
// Known reference from Invoice CSV row 2 for INV/02695
$ref = "PO.2025.12.00027";

echo "Searching for SalesOrder with notes or number matching: " . $ref . PHP_EOL;

$so = \App\Models\SalesOrder::where('notes', $ref)->orWhere('number', $ref)->first();

if ($so) {
    echo "SUCCESS: Found SalesOrder ID " . $so->id . " with number " . $so->number . " and notes " . $so->notes . PHP_EOL;
} else {
    echo "FAILURE: No SalesOrder found matching " . $ref . PHP_EOL;

    // Check what notes ARE in the database
    $sampleNotes = \App\Models\SalesOrder::whereNotNull('notes')->where('notes', '!=', '')->limit(5)->pluck('notes');
    echo "Sample notes in DB: " . json_encode($sampleNotes) . PHP_EOL;
}

// Check an item from the Invoice to see if it's there
$inv = \App\Models\SalesInvoice::where('invoice_number', 'INV/02695')->first();
if ($inv) {
    echo "Invoice INV/02695 exists. Current sales_order_id: " . ($inv->sales_order_id ?? 'NULL') . PHP_EOL;
}
