<?php

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$csvPath = 'd:/Program Receh/kledo/data/akun_08-Feb-2026_halaman-1.csv';
$handle = fopen($csvPath, 'r');

if ($handle === false) {
    die("Failed to open CSV file.\n");
}

// Get headers
$headers = fgetcsv($handle);
$headerMap = array_flip($headers);

$count = 0;

// Clean up previous opening balance journals to avoid duplicates
DB::table('journal_items')->whereIn('journal_entry_id', function ($query) {
    $query->select('id')->from('journal_entries')->where('reference_number', 'LIKE', 'OB-%');
})->delete();
DB::table('journal_entries')->where('reference_number', 'LIKE', 'OB-%')->delete();

while (($row = fgetcsv($handle)) !== false) {
    $name = $row[$headerMap['*Nama Akun']];
    $code = $row[$headerMap['*Kode Akun']];
    $category = $row[$headerMap['*Kategori']];
    $description = $row[$headerMap['Deskripsi']];
    $saldo = (float) $row[$headerMap['Saldo']];

    DB::transaction(function () use ($name, $code, $category, $description, $saldo, &$count) {
        $account = Account::updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'category' => $category,
                'description' => $description,
                'is_active' => true,
            ]
        );
        $count++;
    });
}

fclose($handle);
echo "Imported $count accounts successfully.\n";
