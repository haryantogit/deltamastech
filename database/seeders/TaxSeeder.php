<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tax;

class TaxSeeder extends Seeder
{
    public function run()
    {
        // 1-10500 PPN Masukan (ID: 170)
        // 2-20500 PPN Keluaran (ID: 201)

        Tax::create([
            'name' => 'PPN',
            'rate' => 11,
            'sales_account_id' => 201,   // 2-20500 PPN Keluaran
            'purchase_account_id' => 170, // 1-10500 PPN Masukan
            'is_deduction' => false,
            'type' => 'single',
        ]);

        Tax::create([
            'name' => 'PPH',
            'rate' => 10,
            'sales_account_id' => 170,   // 1-10500 PPN Masukan (As per screenshot, swapped?)
            // Screenshot: PPH -> Akun Pajak Penjualan: 1-10500 PPN Masukan, Akun Pajak Pembelian: 2-20500 PPN Keluaran
            // Logic: PPH on Sales might be a deduction (Withholding), so it goes to "Masukan" (Asset/Expense) or a specific liability?
            // The user screenshot explicitly shows:
            // PPH: Sales Acc -> 1-10500, Purchase Acc -> 2-20500
            'purchase_account_id' => 201, // 2-20500 PPN Keluaran
            'is_deduction' => true,
            'type' => 'single',
        ]);
    }
}
