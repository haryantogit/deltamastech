<?php
use Illuminate\Support\Facades\DB;
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Debt Schema:\n";
$cols = DB::select('DESCRIBE debts');
foreach ($cols as $col) {
    if ($col->Field === 'status') {
        echo "Status Type: " . $col->Type . "\n";
    }
}

echo "\nJournalEntry Schema:\n";
$cols = DB::select('DESCRIBE journal_entries');
foreach ($cols as $col) {
    if ($col->Field === 'status') {
        echo "Status Type: " . $col->Type . "\n";
    }
}
