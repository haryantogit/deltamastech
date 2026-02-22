<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$salesCount = \App\Models\SalesInvoice::count();
echo "Sales Invoices Count: {$salesCount}\n";

$salesItems = \App\Models\SalesInvoiceItem::where('product_id', 1350)->sum('qty');
echo "Total KARUNG Sold: {$salesItems}\n";
