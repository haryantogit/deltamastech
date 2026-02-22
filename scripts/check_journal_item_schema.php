<?php
use Illuminate\Support\Facades\DB;
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "JournalItem Schema:\n";
$cols = DB::select('DESCRIBE journal_items');
foreach ($cols as $col) {
    echo $col->Field . " : " . $col->Type . "\n";
}
