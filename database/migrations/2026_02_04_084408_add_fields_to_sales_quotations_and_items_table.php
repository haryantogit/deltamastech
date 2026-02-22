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
        Schema::table('sales_quotations', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->after('date');
            $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete()->after('contact_id');
            $table->string('reference')->nullable()->after('number');
            $table->boolean('tax_inclusive')->default(false)->after('status');
            $table->decimal('sub_total', 15, 2)->default(0)->after('status');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('sub_total');
            $table->decimal('shipping_cost', 15, 2)->default(0)->after('discount_amount');
            $table->decimal('other_cost', 15, 2)->default(0)->after('shipping_cost');
        });

        Schema::table('sales_quotation_items', function (Blueprint $table) {
            $table->text('description')->nullable()->after('product_id');
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete()->after('product_id');
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
        Schema::table('sales_quotations', function (Blueprint $table) {
            $table->dropColumn(['expiry_date', 'payment_term_id', 'reference', 'tax_inclusive', 'sub_total', 'discount_amount', 'shipping_cost', 'other_cost']);
        });

        Schema::table('sales_quotation_items', function (Blueprint $table) {
            $table->dropColumn(['description', 'unit_id', 'discount_percent', 'tax_name', 'tax_amount']);
        });
    }
};
