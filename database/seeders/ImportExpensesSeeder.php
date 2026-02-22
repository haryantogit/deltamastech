<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\Contact;
use App\Models\Account;
use Carbon\Carbon;

class ImportExpensesSeeder extends Seeder
{
    public function run()
    {
        // Clear existing expenses to prevent duplicates
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Expense::truncate();
        ExpenseItem::truncate();
        // Also clear related journal entries to be safe? 
        // Logic in Observer handles it by ref number, but if we change refs or IDs...
        // Let's rely on observer to create new ones.
        // But what if old journal entries remain?
        // We should probably delete JournalEntries where description like 'Pembayaran biaya%' or ref starts with EXP?
        // Let's assume user wants a clean import.
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $files = [
            'D:\Program Receh\kledo\data-baru\biaya_17-Feb-2026_halaman-1.csv',
            'D:\Program Receh\kledo\data-baru\biaya_17-Feb-2026_halaman-2.csv'
        ];

        // Load mappings
        $contacts = Contact::pluck('id', 'name')->toArray();
        $accounts = Account::pluck('id', 'code')->toArray();

        foreach ($files as $file) {
            if (!file_exists($file)) {
                $this->command->error("File not found: $file");
                continue;
            }

            $this->command->info("Processing file: $file");

            $handle = fopen($file, 'r');
            if ($handle === false) {
                $this->command->error("Could not open file: $file");
                continue;
            }

            // Skip header
            fgetcsv($handle);

            $expensesData = [];
            $lineCount = 0;

            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                $lineCount++;
                // 7: Nomor Biaya (*Reference)
                $refNumber = $data[7] ?? null;
                if (empty($refNumber))
                    continue;

                if (!isset($expensesData[$refNumber])) {
                    $expensesData[$refNumber] = [
                        'contact_name' => $data[0] ?? '',
                        'transaction_date' => $data[8] ?? null,
                        'due_date' => $data[9] ?? null,
                        'memo' => $data[10] ?? '',
                        'tax_inclusive' => strtolower($data[11] ?? '') === 'ya',
                        'is_paid' => strtolower($data[17] ?? '') === 'ya',
                        'payment_account_code' => $data[18] ?? null,
                        'items' => []
                    ];
                }

                $expensesData[$refNumber]['items'][] = [
                    'account_code' => $data[12] ?? null,
                    'description' => $data[14] ?? '',
                    'amount' => $this->parseNumber($data[16] ?? '0'),
                ];
            }
            fclose($handle);

            $this->command->info("Parsed $lineCount lines from $file into " . count($expensesData) . " expenses.");

            DB::beginTransaction();
            try {
                $count = 0;
                foreach ($expensesData as $ref => $data) {
                    // Find or Create Contact
                    $contactName = trim($data['contact_name']);
                    $contactId = $contacts[$contactName] ?? null;

                    if (!$contactId && !empty($contactName)) {
                        $contact = Contact::create(['name' => $contactName, 'type' => 'others']);
                        $contacts[$contactName] = $contact->id;
                        $contactId = $contact->id;
                    }

                    if (!$contactId) {
                        // Fallback for empty contact name?
                        // Skip for now if critical.
                        // Or create "Unknown"?
                        $contact = Contact::firstOrCreate(['name' => 'Tanpa Nama'], ['type' => 'others']);
                        $contactId = $contact->id;
                    }

                    $transactionDate = $this->parseDate($data['transaction_date']);
                    $dueDate = $this->parseDate($data['due_date']);

                    $paymentAccountId = null;
                    if ($data['is_paid'] && !empty($data['payment_account_code'])) {
                        $paymentAccountId = $accounts[$data['payment_account_code']] ?? null;
                    }

                    $totalAmount = 0;
                    $itemsToInsert = [];

                    foreach ($data['items'] as $item) {
                        $accountCode = $item['account_code'];
                        $accountId = $accounts[$accountCode] ?? null;

                        if (!$accountId) {
                            // Try to look up by name or something else? 
                            // Or default to an existing Suspense account?
                            // $this->command->warn("Account code not found: $accountCode for Ref: $ref");
                            continue;
                        }

                        $itemsToInsert[] = [
                            'account_id' => $accountId,
                            'description' => $item['description'],
                            'amount' => $item['amount'],
                        ];
                        $totalAmount += $item['amount'];
                    }

                    if (empty($itemsToInsert))
                        continue;

                    $expense = Expense::create([
                        'contact_id' => $contactId,
                        'account_id' => $paymentAccountId,
                        'transaction_date' => $transactionDate,
                        'due_date' => $dueDate,
                        'reference_number' => $ref,
                        'memo' => $data['memo'],
                        'is_pay_later' => !$data['is_paid'],
                        'sub_total' => $totalAmount,
                        'tax_total' => 0,
                        'total_amount' => $totalAmount,
                        'remaining_amount' => $data['is_paid'] ? 0 : $totalAmount,
                        'is_recurring' => false,
                        // Ensure required fields
                    ]);

                    foreach ($itemsToInsert as $itemData) {
                        ExpenseItem::create([
                            'expense_id' => $expense->id,
                            'account_id' => $itemData['account_id'],
                            'description' => $itemData['description'],
                            'amount' => $itemData['amount'],
                            'tax_id' => null,
                        ]);
                    }

                    // Trigger Observer to create Journal Entry
                    // We need to reload items relationship for the observer to see them
                    $expense->load('items');

                    // Call observer manually to ensure Journal Entry creation
                    // bypassing potential timing issues with touch()
                    (new \App\Observers\ExpenseObserver())->handleJournalEntry($expense);

                    //$expense->touch(); // Removed in favor of direct call

                    $count++;
                    if ($count % 50 === 0)
                        $this->command->info("Imported $count expenses...");
                }

                DB::commit();
                $this->command->info("Successfully imported $count expenses from $file.");

            } catch (\Exception $e) {
                DB::rollBack();
                $this->command->error("Error importing $file: " . $e->getMessage());
                $this->command->error("Line $count. " . $e->getTraceAsString());
            }
        }
    }

    private function parseNumber($value)
    {
        if (empty($value))
            return 0;
        // Removing 'Rp', '.', and replacing ',' with '.' for decimal
        // However, if format is 100000 (integer string), replacing . is fine.
        // If format is 1.200,50 then remove . replace , -> 1200.50
        // If format is 1,200.50 (English), checking...
        // CSV usually matches Locale. Assuming IDR format from file view (e.g. "120000")

        $cleaned = str_replace(['Rp', ' '], '', $value);
        // If no comma or dot, just float
        if (strpos($cleaned, ',') === false && strpos($cleaned, '.') === false) {
            return (float) $cleaned;
        }

        // If has comma, it might be decimal separator in ID.
        // But the example "120000" has neither.
        // Let's assume standard cleaning: remove dots (thousands), swap comma to dot (decimal)
        $cleaned = str_replace('.', '', $cleaned);
        $cleaned = str_replace(',', '.', $cleaned);
        return (float) $cleaned;
    }

    private function parseDate($value)
    {
        if (empty($value))
            return now();
        try {
            return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
        } catch (\Exception $e) {
            return now();
        }
    }
}
