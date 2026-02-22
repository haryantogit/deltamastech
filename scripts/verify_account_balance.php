<?php

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Verifying Account Balances...\n";

$journalEntryCount = JournalEntry::count();
echo "Total Journal Entries: $journalEntryCount\n";

$journalItemCount = JournalItem::count();
echo "Total Journal Items: $journalItemCount\n";

if ($journalItemCount == 0) {
    echo "No journal items found! Balances will be 0.\n";
    exit;
}

// Check specific accounts
$accountsToCheck = [
    '1-10001', // Kas
    '1-10002', // Bank BCA
    '2-20100', // Hutang Usaha
    '6-60102', // Upah (Expense)
];

foreach ($accountsToCheck as $code) {
    $account = Account::where('code', $code)->first();
    if (!$account) {
        echo "Account $code not found.\n";
        continue;
    }

    $items = JournalItem::where('account_id', $account->id)->get();
    $debit = $items->sum('debit');
    $credit = $items->sum('credit');
    $balance = 0;

    $debitNormalCategories = [
        'Kas & Bank',
        'Akun Piutang',
        'Persediaan',
        'Aktiva Lancar Lainnya',
        'Aktiva Tetap',
        'Depresiasi & Amortisasi',
        'Aktiva Lainnya',
        'Harga Pokok Penjualan',
        'Beban',
        'Beban Lainnya',
    ];

    if (in_array($account->category, $debitNormalCategories)) {
        $balance = $debit - $credit;
    } else {
        $balance = $credit - $debit;
    }

    echo "\nAccount: {$account->name} ($code)\n";
    echo "Category: {$account->category}\n";
    echo "Debit: " . number_format($debit, 2) . "\n";
    echo "Credit: " . number_format($credit, 2) . "\n";
    echo "Balance: " . number_format($balance, 2) . "\n";
}
