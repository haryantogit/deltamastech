<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                'code' => '1-10001',
                'name' => 'Kas',
                'category' => 'Asset',
                'is_active' => true,
            ],
            [
                'code' => '1-10002',
                'name' => 'Piutang Usaha',
                'category' => 'Asset',
                'is_active' => true,
            ],
            [
                'code' => '1-10003',
                'name' => 'Persediaan Barang',
                'category' => 'Asset',
                'is_active' => true,
            ],
            [
                'code' => '4-10000',
                'name' => 'Pendapatan Penjualan',
                'category' => 'Income',
                'is_active' => true,
            ],
            [
                'code' => '5-10000',
                'name' => 'Harga Pokok Penjualan',
                'category' => 'Expense',
                'is_active' => true,
            ],
            [
                'code' => '2-10001',
                'name' => 'Hutang Usaha',
                'category' => 'Liability',
                'is_active' => true,
            ],
            [
                'code' => '2-10001',
                'name' => 'Hutang Usaha',
                'category' => 'Liability',
                'is_active' => true,
            ],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(
                ['code' => $account['code']],
                $account
            );
        }
    }
}
