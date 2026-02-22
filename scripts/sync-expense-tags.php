<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Expense;
use App\Models\JournalEntry;

$expenses = Expense::with(['tags'])->get();
$updated = 0;

foreach ($expenses as $expense) {
    $journalEntry = JournalEntry::where('reference_number', $expense->reference_number)->first();

    if ($journalEntry) {
        // Sync tags
        if ($expense->tags()->exists()) {
            $journalEntry->tags()->sync($expense->tags->pluck('id'));
            echo "Synced tags for expense {$expense->reference_number}\n";
            $updated++;
        }

        // Update description and memo
        $items = $expense->items;
        if ($items->isNotEmpty()) {
            $itemDescriptions = $items->pluck('description')->filter()->implode(', ');

            if ($itemDescriptions) {
                // Format: "Pembayaran biaya: [item descriptions] [reference_number]"
                $description = "Pembayaran biaya: " . $itemDescriptions . " " . $expense->reference_number;
            } else {
                // Fallback if no item descriptions
                $description = 'Biaya #' . $expense->reference_number;
            }

            $memo = null;
            if ($expense->account) {
                $memo = $expense->account->name;
            }
            if ($expense->memo) {
                $memo = $memo ? $memo . ' - ' . $expense->memo : $expense->memo;
            }

            $journalEntry->update([
                'description' => $description,
                'memo' => $memo,
            ]);

            echo "Updated description and memo for expense {$expense->reference_number}\n";
        }
    }
}

echo "\nTotal updated: {$updated} journal entries\n";
