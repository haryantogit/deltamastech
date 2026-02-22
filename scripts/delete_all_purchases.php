<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PurchaseInvoice;
use App\Models\PurchaseDelivery;
use Illuminate\Support\Facades\DB;

// Disable foreign key checks is NOT recommended here because we rely on Eloquent events (deleting)
// But if DB has strict constraints that prevent deletion before children, we might have issues.
// Steps:
// 1. Delete Deliveries (reverses stock)
// 2. Delete Invoices (reverses direct stock, deletes JE/Debt)

echo "Starting deletion of Purchase Data...\n";

DB::transaction(function () {
    // 1. Delete All Deliveries
    $deliveries = PurchaseDelivery::all();
    $dCount = $deliveries->count();
    echo "Found {$dCount} Purchase Deliveries. Deleting...\n";

    foreach ($deliveries as $delivery) {
        // This triggers 'deleting' event -> Stock Reversal
        try {
            $delivery->delete();
            echo ".";
        } catch (\Exception $e) {
            echo "\nError deleting Delivery #{$delivery->number}: " . $e->getMessage() . "\n";
        }
    }
    echo "\nDeleted {$dCount} Deliveries.\n";

    // 2. Delete All Invoices
    $invoices = PurchaseInvoice::all();
    $iCount = $invoices->count();
    echo "Found {$iCount} Purchase Invoices. Deleting...\n";

    foreach ($invoices as $invoice) {
        // This triggers 'deleting' event -> Stock Reversal (if direct), JE/Debt deletion
        try {
            $invoice->delete();
            echo ".";
        } catch (\Exception $e) {
            echo "\nError deleting Invoice #{$invoice->number}: " . $e->getMessage() . "\n";
        }
    }
    echo "\nDeleted {$iCount} Invoices.\n";
});

echo "Deletion Complete.\n";
