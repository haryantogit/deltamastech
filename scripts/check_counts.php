<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Deliveries Count: " . \App\Models\PurchaseDelivery::count() . "\n";
echo "Invoices Count: " . \App\Models\PurchaseInvoice::count() . "\n";
echo "Stock KARUNG: " . \App\Models\Stock::where('product_id', 1350)->value('quantity') . "\n";

// specific check for PurchaseDelivery items
$d = \App\Models\PurchaseDelivery::first();
if ($d) {
    echo "First Delivery ID: {$d->id}\n";
    echo "Items Count: " . $d->items()->count() . "\n";
} else {
    echo "No Deliveries found.\n";
}
