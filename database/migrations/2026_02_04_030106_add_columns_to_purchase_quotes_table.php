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
        Schema::table('purchase_quotes', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('date');
            $table->foreignId('payment_term_id')->nullable()->constrained()->nullOnDelete()->after('supplier_id');
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete()->after('payment_term_id');
            $table->string('reference')->nullable()->after('warehouse_id');

            $table->date('shipping_date')->nullable()->after('reference');
            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete()->after('shipping_date');
            $table->string('tracking_number')->nullable()->after('shipping_method_id');

            $table->text('notes')->nullable()->after('status');
            $table->decimal('sub_total', 15, 2)->default(0)->after('total_amount');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('sub_total');
            $table->decimal('shipping_cost', 15, 2)->default(0)->after('discount_amount');
            $table->decimal('other_cost', 15, 2)->default(0)->after('shipping_cost');
            $table->boolean('tax_inclusive')->default(false)->after('other_cost');
        });

        Schema::table('purchase_quote_items', function (Blueprint $table) {
            $table->text('description')->nullable()->after('product_id');
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete()->after('quantity');
            $table->decimal('discount_percent', 5, 2)->default(0)->after('unit_price');
            $table->string('tax_name')->nullable()->after('discount_percent');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_quotes', function (Blueprint $table) {
            $table->dropForeign(['payment_term_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['shipping_method_id']);
            $table->dropColumn([
                'due_date',
                'payment_term_id',
                'warehouse_id',
                'reference',
                'shipping_date',
                'shipping_method_id',
                'tracking_number',
                'notes',
                'sub_total',
                'discount_amount',
                'shipping_cost',
                'other_cost',
                'tax_inclusive'
            ]);
        });

        Schema::table('purchase_quote_items', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['description', 'unit_id', 'discount_percent', 'tax_name', 'tax_amount']);
        });
    }
};
