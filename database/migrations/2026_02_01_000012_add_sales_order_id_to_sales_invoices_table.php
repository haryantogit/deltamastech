<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            // Check if column exists to avoid error if re-running
            if (!Schema::hasColumn('sales_invoices', 'sales_order_id')) {
                // 'invoice_number' is the column name in previous migration, so 'after' might need adjustment if 'number' doesn't exist
                // The previous migration '2026_01_31_000004_create_sales_invoices_tables.php' uses 'invoice_number'.
                // Prompt said "after('number')" but let's check what columns exist. 
                // Using 'invoice_number' as the anchor.
                $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete()->after('invoice_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropForeign(['sales_order_id']);
            $table->dropColumn('sales_order_id');
        });
    }
};
