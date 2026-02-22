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
            if (!Schema::hasColumn('sales_invoices', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoices', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_orders', 'reference')) {
                $table->string('reference')->nullable();
            }
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'reference')) {
                $table->string('reference')->nullable();
            }
        });

        Schema::table('sales_deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_deliveries', 'reference')) {
                $table->string('reference')->nullable();
            }
        });

        Schema::table('purchase_deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_deliveries', 'reference')) {
                $table->string('reference')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('reference');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('reference');
        });

        Schema::table('sales_deliveries', function (Blueprint $table) {
            $table->dropColumn('reference');
        });

        Schema::table('purchase_deliveries', function (Blueprint $table) {
            $table->dropColumn('reference');
        });
    }
};
