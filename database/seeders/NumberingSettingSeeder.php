<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NumberingSetting;
use Illuminate\Support\Facades\DB;

class NumberingSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing settings to avoid duplicates if re-run
        DB::table('numbering_settings')->truncate();

        $settings = [
            // Penjualan
            ['key' => 'sales_invoice', 'name' => 'Tagihan', 'module' => 'penjualan', 'format' => 'INV/[NUMBER]', 'pad_length' => 5],
            ['key' => 'sales_delivery', 'name' => 'Pengiriman', 'module' => 'penjualan', 'format' => 'SD/[NUMBER]', 'pad_length' => 5],
            ['key' => 'sales_order', 'name' => 'Pemesanan', 'module' => 'penjualan', 'format' => 'SO/[NUMBER]', 'pad_length' => 5],
            ['key' => 'sales_quotation', 'name' => 'Penawaran', 'module' => 'penjualan', 'format' => 'QU/[NUMBER]', 'pad_length' => 5],

            // Pembelian
            ['key' => 'purchase_quotation', 'name' => 'Penawaran Pembelian', 'module' => 'pembelian', 'format' => 'PQ/[NUMBER]', 'pad_length' => 5],
            ['key' => 'purchase_invoice', 'name' => 'Tagihan Pembelian', 'module' => 'pembelian', 'format' => 'PI/[NUMBER]', 'pad_length' => 5],
            ['key' => 'purchase_order', 'name' => 'Pesanan Pembelian', 'module' => 'pembelian', 'format' => 'PO/[NUMBER]', 'pad_length' => 5],
            ['key' => 'purchase_delivery', 'name' => 'Pengiriman Pembelian', 'module' => 'pembelian', 'format' => 'PD/[NUMBER]', 'pad_length' => 5],

            // Transaksi Bank
            ['key' => 'kas_bank', 'name' => 'Bank Kirim/Terima Dana', 'module' => 'transaksi bank', 'format' => 'BANK/[NUMBER]', 'pad_length' => 5],

            // Produk & Stok
            ['key' => 'stock_transfer', 'name' => 'Transfer Gudang', 'module' => 'produk & stok', 'format' => 'WT/[NUMBER]', 'pad_length' => 5],
            ['key' => 'stock_adjustment', 'name' => 'Penyesuaian Stok', 'module' => 'produk & stok', 'format' => 'SA/[NUMBER]', 'pad_length' => 5],
            ['key' => 'production_order', 'name' => 'Konversi produk', 'module' => 'produk & stok', 'format' => 'PC/[NUMBER]', 'pad_length' => 5],

            // Lainnya
            ['key' => 'expense', 'name' => 'Biaya', 'module' => 'lainnya', 'format' => 'EXP/[NUMBER]', 'pad_length' => 5],
            ['key' => 'journal', 'name' => 'Manual Journal', 'module' => 'lainnya', 'format' => 'MNJ/[NUMBER]', 'pad_length' => 5],
            ['key' => 'fixed_asset', 'name' => 'Asset Tetap', 'module' => 'lainnya', 'format' => 'FA/[NUMBER]', 'pad_length' => 5],
            ['key' => 'hutang', 'name' => 'Hutang', 'module' => 'lainnya', 'format' => 'CM/[NUMBER]', 'pad_length' => 5],
            ['key' => 'piutang', 'name' => 'Piutang', 'module' => 'lainnya', 'format' => 'DM/[NUMBER]', 'pad_length' => 5],
        ];

        foreach ($settings as $setting) {
            NumberingSetting::create(array_merge($setting, [
                'current_number' => 0,
                'reset_behavior' => 'never',
            ]));
        }
    }
}
