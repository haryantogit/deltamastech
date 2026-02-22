<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PurchaseOrder;
use App\Models\Contact;
use App\Models\Warehouse;

$contact = Contact::first();
$warehouse = Warehouse::first();

if (!$contact || !$warehouse) {
    die("Need at least one contact and warehouse\n");
}

try {
    $po = PurchaseOrder::create([
        'number' => 'TEST-' . time(),
        'date' => date('Y-m-d'),
        'supplier_id' => $contact->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'draft',
        'total_amount' => 1000,
    ]);
    echo "Created PO ID: " . $po->id . "\n";
    echo "Current count: " . PurchaseOrder::count() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
