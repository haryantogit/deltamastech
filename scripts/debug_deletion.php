<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PurchaseDelivery;

$delivery = PurchaseDelivery::latest()->first();

if (!$delivery) {
    echo "No Deliveries found to test.\n";
    exit;
}

echo "Attempting to delete Delivery #{$delivery->number} (ID: {$delivery->id})...\n";

try {
    // Enable query log to see what's happening
    \DB::enableQueryLog();

    $delivery->delete();

    echo "Deletion successful.\n";
} catch (\Exception $e) {
    echo "Deletion Failed!\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";

    // Check SQL state if it's a PDOException
    if ($e instanceof \Illuminate\Database\QueryException) {
        echo "SQL: " . $e->getSql() . "\n";
    }
}
