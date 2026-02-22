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
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoices', 'tracking_number')) {
                $table->string('tracking_number')->nullable()->after('shipping_method_id');
            }
            if (!Schema::hasColumn('purchase_invoices', 'shipping_date')) {
                $table->date('shipping_date')->nullable()->after('tracking_number');
            }
            if (!Schema::hasColumn('purchase_invoices', 'tax_inclusive')) {
                $table->boolean('tax_inclusive')->default(false)->after('status');
            }
            if (!Schema::hasColumn('purchase_invoices', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('purchase_invoices', 'shipping_cost')) {
                $table->decimal('shipping_cost', 15, 2)->default(0)->after('discount_amount');
            }
            if (!Schema::hasColumn('purchase_invoices', 'other_cost')) {
                $table->decimal('other_cost', 15, 2)->default(0)->after('shipping_cost');
            }
            if (!Schema::hasColumn('purchase_invoices', 'down_payment')) {
                $table->decimal('down_payment', 15, 2)->default(0)->after('other_cost');
            }
        });

        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoice_items', 'description')) {
                $table->text('description')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('purchase_invoice_items', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('description')->constrained('units')->nullOnDelete();
            }
            if (!Schema::hasColumn('purchase_invoice_items', 'discount_percent')) {
                $table->decimal('discount_percent', 5, 2)->default(0)->after('unit_price');
            }
            if (!Schema::hasColumn('purchase_invoice_items', 'tax_name')) {
                $table->string('tax_name')->nullable()->after('discount_percent');
            }
            if (!Schema::hasColumn('purchase_invoice_items', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->dropColumn(['description', 'unit_id', 'discount_percent', 'tax_name', 'tax_amount']);
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'tracking_number',
                'shipping_date',
                'tax_inclusive',
                'discount_amount',
                'shipping_cost',
                'other_cost',
                'down_payment'
            ]);
        });
    }
};
