<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportExpenses extends Command
{
    protected $signature = 'app:import-expenses {file}';
    protected $description = 'Import expenses from CSV file';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Importing expenses from {$filePath}...");

        $file = fopen($filePath, 'r');
        $header = fgetcsv($file); // Read header

        $groupedRows = [];
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < 17)
                continue;
            $ref = $row[7]; // *Nomor Biaya
            if (!isset($groupedRows[$ref])) {
                $groupedRows[$ref] = [];
            }
            $groupedRows[$ref][] = $row;
        }
        fclose($file);

        $accounts = Account::pluck('id', 'code')->toArray();
        $contacts = Contact::pluck('id', 'name')->toArray();
        $tags = Tag::pluck('id', 'name')->toArray();

        $count = 0;
        DB::beginTransaction();
        try {
            foreach ($groupedRows as $ref => $rows) {
                $firstRow = $rows[0];

                // 0: *Nama Kontak
                $contactName = $firstRow[0];
                $contactId = $contacts[$contactName] ?? null;
                if (!$contactId) {
                    $contact = Contact::create(['name' => $contactName, 'type' => 'others']);
                    $contactId = $contact->id;
                    $contacts[$contactName] = $contactId;
                }

                // 18: Kode Akun Pembayaran
                $paymentAccountCode = $firstRow[18];
                $paymentAccountId = $accounts[$paymentAccountCode] ?? null;

                // 8: *Tanggal Transaksi (dd/mm/yyyy)
                $transactionDate = $this->parseDate($firstRow[8]);

                // 17: Status Dibayar (Ya / Tidak)
                $isPaid = trim(strtolower($firstRow[17])) === 'ya';

                $expense = Expense::updateOrCreate(
                    ['reference_number' => $ref],
                    [
                        'contact_id' => $contactId,
                        'account_id' => $paymentAccountId,
                        'transaction_date' => $transactionDate,
                        'memo' => $firstRow[10], // Catatan
                        'is_pay_later' => !$isPaid,
                        'sub_total' => $this->parseNumber($firstRow[23]), // Total from CSV
                        'tax_total' => 0,
                        'total_amount' => $this->parseNumber($firstRow[23]), // Total from CSV
                        'remaining_amount' => $this->parseNumber($firstRow[22]), // Sisa Tagihan
                    ]
                );

                // Clear existing items if any to avoid duplicates on re-run
                $expense->items()->delete();

                foreach ($rows as $row) {
                    // 12: *Kode Akun Biaya
                    $itemAccountCode = $row[12];
                    $itemAccountId = $accounts[$itemAccountCode] ?? null;

                    // 16: Jumlah Biaya
                    $amount = $this->parseNumber($row[16]);

                    ExpenseItem::create([
                        'expense_id' => $expense->id,
                        'account_id' => $itemAccountId,
                        'description' => $row[14], // Deskripsi Biaya
                        'amount' => $amount,
                    ]);
                }

                // 19: Tag (Beberapa Tag Dipisah Dengan Koma)
                if (!empty($firstRow[19])) {
                    $tagNames = explode(',', $firstRow[19]);
                    $tagIds = [];
                    foreach ($tagNames as $tagName) {
                        $tagName = trim($tagName);
                        if (!isset($tags[$tagName])) {
                            $tag = Tag::create(['name' => $tagName]);
                            $tags[$tagName] = $tag->id;
                        }
                        $tagIds[] = $tags[$tagName];
                    }
                    $expense->tags()->sync($tagIds);
                }

                $expense->touch(); // Trigger observer for journal entry

                $count++;
                if ($count % 50 === 0) {
                    $this->info("Imported {$count} expenses...");
                }
            }
            DB::commit();
            $this->info("Import completed! Total expenses: {$count}");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error at {$ref}: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function parseDate($date)
    {
        try {
            return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            return Carbon::now()->format('Y-m-d');
        }
    }

    private function parseNumber($value)
    {
        return (float) str_replace(',', '', $value);
    }
}
