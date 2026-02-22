<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$columns = DB::select("DESCRIBE stock_movements");
foreach ($columns as $col) {
    if ($col->Field === 'type') {
        echo "Column: {$col->Field} | Type: {$col->Type} | Null: {$col->Null}\n";
    }
}
