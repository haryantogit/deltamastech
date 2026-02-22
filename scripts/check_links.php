<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SalesDelivery;
use App\Models\PurchaseDelivery;

$sCount = SalesDelivery::has('salesOrder')->count();
$sTotal = SalesDelivery::count();
$pCount = PurchaseDelivery::has('purchaseOrder')->count();
$pTotal = PurchaseDelivery::count();

echo "Sales Deliveries (with existing Sales Order record): $sCount / $sTotal\n";
echo "Purchase Deliveries (with existing Purchase Order record): $pCount / $pTotal\n";

echo "\nLatest 5 Sales Deliveries:\n";
foreach (SalesDelivery::latest('id')->take(5)->get() as $sd) {
    echo "ID: {$sd->id}, No: {$sd->number}, Order No: " . ($sd->salesOrder ? $sd->salesOrder->number : 'RELATION_NULL') . "\n";
}

echo "\nLatest 5 Purchase Deliveries:\n";
foreach (PurchaseDelivery::latest('id')->take(5)->get() as $pd) {
    echo "ID: {$pd->id}, No: {$pd->number}, Order No: " . ($pd->purchaseOrder ? $pd->purchaseOrder->number : 'RELATION_NULL') . "\n";
}




