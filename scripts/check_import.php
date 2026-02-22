<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SalesInvoice;

echo "Sales Invoices with reference: " . SalesInvoice::whereNotNull('reference')->where('reference', '!=', '')->count() . "\n";
echo "Sales Invoices with notes: " . SalesInvoice::whereNotNull('notes')->where('notes', '!=', '')->count() . "\n";

$filePath = 'd:/Program Receh/kledo/data/tagihan_29-Jan-2026_halaman-1.csv';
$handle = fopen($filePath, 'r');
$header = fgetcsv($handle);
$colMap = array_flip($header);

echo "\nSampling references from CSV ($filePath):\n";
$found = 0;
while (($data = fgetcsv($handle)) !== FALSE) {
    if (empty($data))
        continue;
    $invNum = $data[$colMap['*Nomor Tagihan']] ?? '';
    $ref = $data[$colMap['Catatan']] ?? '';
    $notes = $data[$colMap['Pesan']] ?? '';

    if (!empty($ref) || !empty($notes)) {
        $found++;
        $db = SalesInvoice::where('invoice_number', $invNum)->first();
        echo "Row $found: Invoice=$invNum, CSV_Ref=$ref, CSV_Notes=$notes | DB_Ref=" . ($db ? $db->reference : 'MISSING') . ", DB_Notes=" . ($db ? $db->notes : 'MISSING') . "\n";
        if ($found >= 10)
            break;
    }
}
fclose($handle);
