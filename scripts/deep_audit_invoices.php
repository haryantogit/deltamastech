<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SalesInvoice;

$results = SalesInvoice::select('id', 'invoice_number', 'reference', 'notes')->get();
$hasRef = 0;
$hasNotes = 0;
foreach ($results as $r) {
    if (!empty($r->reference))
        $hasRef++;
    if (!empty($r->notes))
        $hasNotes++;
}

echo "Total Invoices: " . count($results) . "\n";
echo "Has Reference: $hasRef\n";
echo "Has Notes: $hasNotes\n";

if ($hasRef < 100) {
    echo "\nSample of first 20 records:\n";
    for ($i = 0; $i < 20; $i++) {
        $r = $results[$i] ?? null;
        if ($r) {
            echo "INV: {$r->invoice_number} | REF: " . ($r->reference ?: 'EMPTY') . " | NOTES: " . (mb_substr($r->notes, 0, 20) ?: 'EMPTY') . "\n";
        }
    }
}
