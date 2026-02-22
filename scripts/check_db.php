<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "DB Name: " . config('database.connections.mysql.database') . "\n";
echo "PurchaseOrder count: " . \App\Models\PurchaseOrder::count() . "\n";
echo "PurchaseDelivery count: " . \App\Models\PurchaseDelivery::count() . "\n";
echo "PurchaseInvoice count: " . \App\Models\PurchaseInvoice::count() . "\n";
echo "Debt count: " . \App\Models\Debt::count() . "\n";
echo "Contact count: " . \App\Models\Contact::count() . "\n";
echo "Product count: " . \App\Models\Product::count() . "\n";
