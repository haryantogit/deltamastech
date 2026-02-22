<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Contact;
use App\Models\Account;
use Illuminate\Support\Str;

class ImportContactsSeeder extends Seeder
{
    public function run()
    {
        $file = 'D:\Program Receh\kledo\data-baru\kontak_17-Feb-2026_halaman-1.csv';

        if (!file_exists($file)) {
            $this->command->error("File not found: $file");
            return;
        }

        $handle = fopen($file, 'r');
        if ($handle === false) {
            $this->command->error("Could not open file: $file");
            return;
        }

        // Get Account Map for Quick Lookup
        $accounts = Account::pluck('id', 'code')->toArray();

        // Skip header
        fgetcsv($handle);

        $row = 0;
        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                $row++;

                // Map CSV columns to variables based on header analysis
                // 0: Nama
                // 1: Sapaan
                // 3: Tipe Kontak
                // 5: Perusahaan
                // 6: Alamat Penagihan
                // 7: Negara
                // 8: Provinsi
                // 9: Kota
                // 12: Email
                // 14: Nomor Telepon
                // 15: Nomor Telepon Sekunder
                // 25: Kode Akun Hutang
                // 26: Maksimal Hutang
                // 27: Kode Akun Piutang
                // 28: Maksimal Piutang
                // 30: NPWP
                // 31: Nama Bank
                // 33: Nama Pemilik Rekening
                // 34: Nomor Rekening

                $name = $data[0];
                $salutation = $data[1];
                $typeRaw = $data[3];
                $company = $data[5];
                $address = $data[6];
                $country = $data[7];
                $province = $data[8];
                $city = $data[9];
                $email = $data[12];
                $phone = $data[14];
                $mobile = $data[15];

                $payableAccountCode = $data[25];
                $creditLimit = $this->parseNumber($data[26]);

                $receivableAccountCode = $data[27];
                $receivableLimit = $this->parseNumber($data[28]);

                $taxId = $data[30];
                $bankName = $data[31];
                $bankAccountHolder = $data[33];
                $bankAccountNo = $data[34];

                // Map Type
                $type = match (strtolower($typeRaw)) {
                    'vendor' => 'vendor',
                    'pelanggan' => 'customer',
                    'pegawai' => 'employee',
                    default => 'others',
                };

                $payableAccountId = $accounts[$payableAccountCode] ?? null;
                $receivableAccountId = $accounts[$receivableAccountCode] ?? null;

                Contact::create([
                    'name' => $name,
                    'salutation' => $salutation,
                    'type' => $type,
                    'company' => $company,
                    'address' => $address,
                    'country' => $country,
                    'province' => $province,
                    'city' => $city,
                    'email' => $email,
                    'phone' => $phone,
                    'mobile' => $mobile,
                    'payable_account_id' => $payableAccountId,
                    'receivable_account_id' => $receivableAccountId,
                    'tax_id' => $taxId,
                    'bank_name' => $bankName,
                    'bank_account_holder' => $bankAccountHolder,
                    'bank_account_no' => $bankAccountNo,
                    'credit_limit' => $creditLimit,
                    'receivable_limit' => $receivableLimit,
                ]);
            }
            DB::commit();
            $this->command->info("Imported $row contacts successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Error at row $row: " . $e->getMessage());
        } finally {
            fclose($handle);
        }
    }

    private function parseNumber($value)
    {
        if (empty($value))
            return 0;
        return (float) str_replace(',', '', $value);
    }
}
