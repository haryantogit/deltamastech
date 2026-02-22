<?php

use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\Contact;
use App\Models\Account;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Verifying Expense Import...\n";

$expenseCount = Expense::count();
echo "Total Expenses: $expenseCount\n";

$itemCount = ExpenseItem::count();
echo "Total Expense Items: $itemCount\n";

if ($expenseCount == 0) {
    echo "No expenses found!\n";
    exit;
}

// Sample Expense
$expense = Expense::with(['items', 'contact', 'account'])->inRandomOrder()->first();

if ($expense) {
    echo "\nSample Expense:\n";
    echo "Ref: " . $expense->reference_number . "\n";
    echo "Date: " . ($expense->transaction_date ? $expense->transaction_date->format('Y-m-d') : 'N/A') . "\n";

    $contactName = $expense->contact->name ?? 'None';
    echo "Contact: " . $contactName . "\n";

    $paymentAccountName = $expense->account->name ?? 'None';
    $paymentAccountCode = $expense->account->code ?? '';
    echo "Payment Account: " . $paymentAccountName . " (" . $paymentAccountCode . ")\n";

    echo "Is Main Paid? " . ($expense->is_pay_later ? 'No' : 'Yes') . "\n";
    echo "Total: " . number_format($expense->total_amount, 2) . "\n";
    echo "Remaining: " . number_format($expense->remaining_amount, 2) . "\n";

    echo "\nItems:\n";
    foreach ($expense->items as $item) {
        $accName = $item->account->name ?? 'Unknown Account';
        $accCode = $item->account->code ?? '';
        echo "- " . $accName . " (" . $accCode . "): " . number_format($item->amount, 2) . "\n";
    }
} else {
    echo "No expense found to sample.\n";
}

// Check for unmapped accounts
$unmappedItems = ExpenseItem::whereNull('account_id')->count();
if ($unmappedItems > 0) {
    echo "\nWARNING: $unmappedItems items have no account_id!\n";
} else {
    echo "\nAll items have account_id.\n";
}

// Check for unmapped contacts
$unmappedContacts = Expense::whereNull('contact_id')->count();
if ($unmappedContacts > 0) {
    echo "\nWARNING: $unmappedContacts expenses have no contact_id!\n";
} else {
    echo "\nAll expenses have contact_id.\n";
}
