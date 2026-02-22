<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_method_id');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_method_id');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_method_id');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_method_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
        });
    }
};
