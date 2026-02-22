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
        Schema::table('sales_deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_deliveries', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete()->after('customer_id');
            }
            if (!Schema::hasColumn('sales_deliveries', 'reference')) {
                $table->string('reference')->nullable()->after('number');
            }
            if (!Schema::hasColumn('sales_deliveries', 'shipping_method_id')) {
                $table->foreignId('shipping_method_id')->nullable()->constrained('shipping_methods')->nullOnDelete()->after('warehouse_id');
            }
            if (!Schema::hasColumn('sales_deliveries', 'shipping_cost')) {
                $table->decimal('shipping_cost', 15, 2)->default(0)->after('shipping_method_id');
            }
        });

        Schema::table('sales_delivery_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_delivery_items', 'description')) {
                $table->text('description')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('sales_delivery_items', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete()->after('product_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_deliveries', function (Blueprint $table) {
            $table->dropColumn(['warehouse_id', 'reference', 'shipping_method_id', 'shipping_cost']);
        });

        Schema::table('sales_delivery_items', function (Blueprint $table) {
            $table->dropColumn(['description', 'unit_id']);
        });
    }
};
