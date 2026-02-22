<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Fix Sales Invoices
        DB::table('sales_invoices')
            ->whereNull('warehouse_id')
            ->orWhere('warehouse_id', 0)
            ->update(['warehouse_id' => 3]); // Default to Gudang Utama

        DB::table('sales_invoices')
            ->whereNull('account_id')
            ->orWhere('account_id', 0)
            ->update(['account_id' => 9]); // Default to Piutang Usaha

        // Normalize status for Sales Invoices
        DB::table('sales_invoices')->where('status', 'Lunas')->update(['status' => 'paid']);
        DB::table('sales_invoices')->where('status', 'Belum Dibayar')->update(['status' => 'unpaid']);
        DB::table('sales_invoices')->where('status', 'Dibayar Sebagian')->update(['status' => 'partial']);

        // 2. Fix Purchase Invoices
        DB::table('purchase_invoices')
            ->whereNull('warehouse_id')
            ->orWhere('warehouse_id', 0)
            ->update(['warehouse_id' => 3]);

        DB::table('purchase_invoices')
            ->whereNull('account_id')
            ->orWhere('account_id', 0)
            ->update(['account_id' => 11]); // Hutang Usaha

        // 3. Fix Item Totals
        // Sales Items (qty, price, subtotal)
        DB::table('sales_invoice_items')->update([
            'subtotal' => DB::raw('price * qty')
        ]);

        // Purchase Items (quantity, unit_price, total_price)
        DB::table('purchase_invoice_items')->update([
            'total_price' => DB::raw('unit_price * quantity')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse for data fix
    }
};
