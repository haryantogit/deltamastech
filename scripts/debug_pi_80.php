<?php

use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pi = PurchaseInvoice::where('number', 'PI/00080')->first();

if (!$pi) {
    echo "PI/00080 not found.\n";
    exit;
}

echo "Invoice: " . $pi->number . "\n";
echo "ID: " . $pi->id . "\n";
echo "Total Amount: " . $pi->total_amount . "\n";
echo "Down Payment (Column): " . $pi->down_payment . "\n";
echo "Payment Status: " . $pi->payment_status . "\n";
echo "Status: " . $pi->status . "\n";
echo "Balance Due (Model Attribute): " . $pi->balance_due . "\n";

echo "--------------------------------\n";

if ($pi->purchase_order_id) {
    $po = PurchaseOrder::find($pi->purchase_order_id);
    echo "Related PO ID: " . $po->id . "\n";
    echo "PO Number: " . $po->number . "\n";
    echo "PO Down Payment: " . $po->down_payment . "\n";
} else {
    echo "No direct PO linked via purchase_order_id.\n";
}

if ($pi->reference) {
    echo "Reference: " . $pi->reference . "\n";
    $poRef = PurchaseOrder::where('number', $pi->reference)->first();
    if ($poRef) {
        echo "PO found via Reference: " . $poRef->number . "\n";
        echo "PO Ref Down Payment: " . $poRef->down_payment . "\n";
    }
}
