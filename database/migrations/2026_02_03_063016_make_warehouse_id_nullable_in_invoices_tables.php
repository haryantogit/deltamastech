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
            $table->foreignId('warehouse_id')->nullable()->change();
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable(false)->change();
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable(false)->change();
        });
    }
};
