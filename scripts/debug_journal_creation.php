<?php

use App\Models\Expense;
use App\Models\JournalEntry;
use App\Observers\ExpenseObserver;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Debugging Journal Entry Creation...\n";

// Find an expense that doesn't have a corresponding journal entry
$expenses = Expense::all();
$expenseWithoutJournal = null;

foreach ($expenses as $expense) {
    $journal = JournalEntry::where('reference_number', $expense->reference_number)->first();
    if (!$journal) {
        $expenseWithoutJournal = $expense;
        break;
    }
}

if (!$expenseWithoutJournal) {
    echo "All expenses have journal entries! (Wait, count says otherwise?)\n";
    echo "Expense Count: " . $expenses->count() . "\n";
    echo "Journal Count: " . JournalEntry::count() . "\n";
    exit;
}

echo "Found Expense without Journal: {$expenseWithoutJournal->reference_number}\n";
echo "ID: {$expenseWithoutJournal->id}\n";
echo "Items Count: {$expenseWithoutJournal->items()->count()}\n";
echo "Total Amount: {$expenseWithoutJournal->total_amount}\n";

// Try to manually trigger the observer logic
echo "Attempting to trigger observer manually...\n";

try {
    $observer = new ExpenseObserver();
    // We need to simulate 'updated' or call handleJournalEntry directly if possible (it's protected)
    // So distinct call approach:
    // Reflection to call protected method? 
    // Or just $expense->touch() and see if it works here (Observer might not be registered in cli script if handled by AppServiceProvider boot?)
    // AppServiceProvider boot IS called in $kernel->bootstrap() usually? Actually need to check.

    // Let's rely on model event first.
    $expenseWithoutJournal->touch();
    echo "Touched expense.\n";

    $journal = JournalEntry::where('reference_number', $expenseWithoutJournal->reference_number)->first();
    if ($journal) {
        echo "SUCCESS: Journal Entry created! ID: {$journal->id}\n";
    } else {
        echo "FAILURE: Still no Journal Entry.\n";

        // Let's try to debug why via Reflection on Observer
        $method = new ReflectionMethod(ExpenseObserver::class, 'handleJournalEntry');
        $method->setAccessible(true);
        $method->invoke(new ExpenseObserver(), $expenseWithoutJournal);
        echo "Invoked handleJournalEntry manually.\n";

        $journal = JournalEntry::where('reference_number', $expenseWithoutJournal->reference_number)->first();
        if ($journal) {
            echo "SUCCESS: Journal Entry created after manual invoke!\n";
        } else {
            echo "FAILURE: Still no Journal Entry after manual invoke.\n";
            // Debug conditions in Observer
            if ($expenseWithoutJournal->items->isEmpty()) {
                echo "Reason: Items are empty.\n";
            }
        }
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
