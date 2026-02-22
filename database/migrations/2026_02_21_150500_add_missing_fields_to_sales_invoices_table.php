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
            if (!Schema::hasColumn('sales_invoices', 'tax_inclusive')) {
                $table->boolean('tax_inclusive')->default(false)->after('status');
            }
            if (!Schema::hasColumn('sales_invoices', 'total_tax')) {
                $table->decimal('total_tax', 15, 2)->default(0)->after('tax_inclusive');
            }
            if (!Schema::hasColumn('sales_invoices', 'down_payment')) {
                $table->decimal('down_payment', 15, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('sales_invoices', 'shipping_method_id')) {
                $table->foreignId('shipping_method_id')->nullable()->constrained('shipping_methods')->nullOnDelete()->after('total_tax');
            }
            if (!Schema::hasColumn('sales_invoices', 'tracking_number')) {
                $table->string('tracking_number')->nullable()->after('shipping_method_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropForeign(['shipping_method_id']);
            $table->dropColumn(['tax_inclusive', 'total_tax', 'down_payment', 'shipping_method_id', 'tracking_number']);
        });
    }
};
