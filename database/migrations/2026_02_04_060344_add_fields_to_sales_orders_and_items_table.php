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
        Schema::table('sales_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_orders', 'due_date')) {
                $table->date('due_date')->nullable()->after('date');
            }
            if (!Schema::hasColumn('sales_orders', 'payment_term_id')) {
                $table->foreignId('payment_term_id')->nullable()->after('customer_id')->constrained('payment_terms')->nullOnDelete();
            }
            if (!Schema::hasColumn('sales_orders', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->after('payment_term_id')->constrained('warehouses')->nullOnDelete();
            }
            if (!Schema::hasColumn('sales_orders', 'shipping_date')) {
                $table->date('shipping_date')->nullable()->after('warehouse_id');
            }
            if (!Schema::hasColumn('sales_orders', 'shipping_method_id')) {
                $table->foreignId('shipping_method_id')->nullable()->after('shipping_date')->constrained('shipping_methods')->nullOnDelete();
            }
            if (!Schema::hasColumn('sales_orders', 'tracking_number')) {
                $table->string('tracking_number')->nullable()->after('shipping_method_id');
            }
            // reference is likely added by 2026_02_01_070201_add_notes_and_reference_to_transactions_table.php
            if (!Schema::hasColumn('sales_orders', 'tax_inclusive')) {
                $table->boolean('tax_inclusive')->default(false)->after('status');
            }
            if (!Schema::hasColumn('sales_orders', 'sub_total')) {
                $table->decimal('sub_total', 15, 2)->default(0)->after('tax_inclusive');
            }
            if (!Schema::hasColumn('sales_orders', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('sub_total');
            }
            if (!Schema::hasColumn('sales_orders', 'shipping_cost')) {
                $table->decimal('shipping_cost', 15, 2)->default(0)->after('discount_amount');
            }
            if (!Schema::hasColumn('sales_orders', 'other_cost')) {
                $table->decimal('other_cost', 15, 2)->default(0)->after('shipping_cost');
            }
            if (!Schema::hasColumn('sales_orders', 'down_payment')) {
                $table->decimal('down_payment', 15, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('sales_orders', 'balance_due')) {
                $table->decimal('balance_due', 15, 2)->default(0)->after('down_payment');
            }
        });

        Schema::table('sales_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_order_items', 'description')) {
                $table->text('description')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('sales_order_items', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('description')->constrained('units')->nullOnDelete();
            }
            if (!Schema::hasColumn('sales_order_items', 'discount_percent')) {
                $table->decimal('discount_percent', 5, 2)->default(0)->after('unit_price');
            }
            if (!Schema::hasColumn('sales_order_items', 'tax_name')) {
                $table->string('tax_name')->nullable()->after('discount_percent');
            }
            if (!Schema::hasColumn('sales_order_items', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['payment_term_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['shipping_method_id']);
            $table->dropColumn([
                'due_date',
                'payment_term_id',
                'warehouse_id',
                'shipping_date',
                'shipping_method_id',
                'tracking_number',
                'reference',
                'tax_inclusive',
                'sub_total',
                'discount_amount',
                'shipping_cost',
                'other_cost',
                'down_payment',
                'balance_due'
            ]);
        });

        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['description', 'unit_id', 'discount_percent', 'tax_name', 'tax_amount']);
        });
    }
};
