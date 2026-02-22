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
            $table->foreignId('shipping_method_id')->nullable()->constrained('shipping_methods')->nullOnDelete()->after('payment_term_id');
            $table->date('shipping_date')->nullable()->after('shipping_method_id');
            $table->string('tracking_number')->nullable()->after('shipping_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_quotations', function (Blueprint $table) {
            $table->dropForeign(['shipping_method_id']);
            $table->dropColumn(['shipping_method_id', 'shipping_date', 'tracking_number']);
        });
    }
};
