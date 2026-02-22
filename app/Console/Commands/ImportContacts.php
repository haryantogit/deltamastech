<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportContacts extends Command
{
    protected $signature = 'app:import-contacts {file}';
    protected $description = 'Import contacts from CSV file';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Importing contacts from {$filePath}...");

        $file = fopen($filePath, 'r');
        $header = fgetcsv($file); // Skip header

        // Mapping from CSV headers to our fields
        // 0: *Nama
        // 1: Sapaan
        // 2: URL Foto
        // 3: *Tipe Kontak
        // 5: Perusahaan
        // 6: Alamat Penagihan
        // 7: Negara
        // 8: Provinsi
        // 9: Kota
        // 12: Email
        // 14: Nomor Telepon
        // 15: Nomor Telepon Sekunder (Mobile)
        // 18: ID Kartu Identitas (NIK)
        // 30: NPWP
        // 31: Nama Bank
        // 34: Nomor Rekening
        // 33: Nama Pemilik Rekening
        // 26: Maksimal Hutang (Receivable Limit)
        // 28: Maksimal Piutang (Credit Limit)
        // 25: Kode Akun Hutang (Payable Account)
        // 27: Kode Akun Piutang (Receivable Account)

        $count = 0;
        $accounts = Account::pluck('id', 'code')->toArray();

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($file)) !== false) {
                if (count($row) < 5)
                    continue;

                $type = $this->mapType($row[3] ?? '');

                $contact = Contact::updateOrCreate(
                    ['name' => $row[0]],
                    [
                        'salutation' => $row[1] ?? null,
                        'photo' => $row[2] ?? null,
                        'type' => $type,
                        'company' => $row[5] ?? null,
                        'address' => $row[6] ?? null,
                        'country' => $row[7] ?? null,
                        'province' => $row[8] ?? null,
                        'city' => $row[9] ?? null,
                        'email' => $row[12] ?? null,
                        'phone' => $row[14] ?? null,
                        'mobile' => $row[15] ?? null,
                        'nik' => $row[18] ?? null,
                        'tax_id' => $row[30] ?? null,
                        'bank_name' => $row[31] ?? null,
                        'bank_account_no' => $row[34] ?? null,
                        'bank_account_holder' => $row[33] ?? null,
                        'receivable_limit' => $this->parseNumber($row[28] ?? 0), // index 28: Maksimal Piutang
                        'credit_limit' => $this->parseNumber($row[26] ?? 0),     // index 26: Maksimal Hutang
                        'payable_account_id' => $accounts[$row[25]] ?? null,
                        'receivable_account_id' => $accounts[$row[27]] ?? null,
                    ]
                );

                $count++;
                if ($count % 100 === 0) {
                    $this->info("Imported {$count} contacts...");
                }
            }
            DB::commit();
            $this->info("Import completed! Total contacts: {$count}");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        }

        fclose($file);
        return Command::SUCCESS;
    }

    private function mapType($csvType)
    {
        return match (trim($csvType)) {
            'Vendor' => 'vendor',
            'Pelanggan' => 'customer',
            'Pegawai' => 'employee',
            'Lainnya' => 'others',
            default => 'others',
        };
    }

    private function parseNumber($value)
    {
        return (float) str_replace(',', '', $value);
    }
}
