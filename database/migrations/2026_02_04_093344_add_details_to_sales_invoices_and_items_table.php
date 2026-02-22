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
        Schema::table('sales_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_invoices', 'shipping_date')) {
                $table->date('shipping_date')->nullable();
            }
            if (!Schema::hasColumn('sales_invoices', 'discount_total')) {
                $table->decimal('discount_total', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('sales_invoices', 'shipping_cost')) {
                $table->decimal('shipping_cost', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('sales_invoices', 'other_cost')) {
                $table->decimal('other_cost', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('sales_invoices', 'balance_due')) {
                $table->decimal('balance_due', 15, 2)->default(0);
            }
        });

        Schema::table('sales_invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_invoice_items', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            }
            if (!Schema::hasColumn('sales_invoice_items', 'discount_percent')) {
                $table->decimal('discount_percent', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('sales_invoice_items', 'tax_name')) {
                $table->string('tax_name')->nullable()->default('Bebas Pajak');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn(['shipping_date', 'discount_total', 'shipping_cost', 'other_cost', 'balance_due']);
        });

        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['unit_id', 'discount_percent', 'tax_name']);
        });
    }
};
