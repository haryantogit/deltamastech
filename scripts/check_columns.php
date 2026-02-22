<?php
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = ['purchase_invoice_items', 'sales_invoice_items'];

foreach ($tables as $table) {
    echo "Columns for $table: " . implode(', ', Schema::getColumnListing($table)) . PHP_EOL;
}
