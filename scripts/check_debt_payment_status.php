<?php
use Illuminate\Support\Facades\DB;
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cols = DB::select('DESCRIBE debts');
foreach ($cols as $col) {
    if ($col->Field === 'payment_status') {
        echo "Payment Status Type: " . $col->Type . "\n";
    }
}
