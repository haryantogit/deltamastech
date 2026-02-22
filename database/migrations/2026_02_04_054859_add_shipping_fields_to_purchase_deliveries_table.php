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
        Schema::table('purchase_deliveries', function (Blueprint $table) {
            $table->date('shipping_date')->nullable()->after('warehouse_id');
            $table->foreignId('shipping_method_id')->nullable()->after('shipping_date')->constrained('shipping_methods')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_deliveries', function (Blueprint $table) {
            $table->dropForeign(['shipping_method_id']);
            $table->dropColumn(['shipping_date', 'shipping_method_id']);
        });
    }
};
