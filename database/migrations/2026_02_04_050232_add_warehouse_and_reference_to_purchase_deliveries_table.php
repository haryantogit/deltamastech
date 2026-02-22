<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_deliveries', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->after('supplier_id')->constrained('warehouses')->nullOnDelete();
            }
            if (!Schema::hasColumn('purchase_deliveries', 'reference')) {
                $table->string('reference')->nullable()->after('supplier_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_deliveries', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_deliveries', 'warehouse_id')) {
                $table->dropForeign(['warehouse_id']);
                $table->dropColumn('warehouse_id');
            }
            if (Schema::hasColumn('purchase_deliveries', 'reference')) {
                $table->dropColumn('reference');
            }
        });
    }
};
